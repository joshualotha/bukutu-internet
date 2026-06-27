# Pesapal Payment Gateway Setup Guide

Step-by-step guide to configure Pesapal v3 API for accepting payments.

## Overview

The billing system uses Pesapal v3 API to process payments. The flow is:

1. Customer selects a package → system creates an order
2. System calls `PesapalClient::submitOrder()` → gets a redirect URL
3. Customer is redirected to Pesapal's secure payment page
4. Customer pays via Mobile Money (M-Pesa, Airtel Money) or Card
5. Pesapal sends an IPN (Instant Payment Notification) to your webhook
6. System verifies the transaction status and activates the customer

---

## Step 1: Create a Pesapal Developer Account

### Sandbox Account (for testing)

1. Go to **[Pesapal Developer Portal](https://developer.pesapal.com)**
2. Click **Sign Up** and create an account
3. Verify your email address
4. Once logged in, go to **My Keys**
5. Note your **Consumer Key** and **Consumer Secret**
6. The sandbox environment URL is: `https://pay.pesapal.com/v3`

### Live Account (for production)

1. Go to **[Pesapal](https://www.pesapal.com)** and sign up for a merchant account
2. Complete the KYC (Know Your Customer) process
3. Once approved, log in to the merchant dashboard
4. Go to **Settings → API Keys**
5. Generate your **Consumer Key** and **Consumer Secret**
6. The live environment URL is: `https://pay.pesapal.com/v3`

---

## Step 2: Configure Environment Variables

Add these to your `.env` file:

```env
PESAPAL_CONSUMER_KEY=your_consumer_key_here
PESAPAL_CONSUMER_SECRET=your_consumer_secret_here
PESAPAL_ENVIRONMENT=sandbox
PESAPAL_BASE_URL=https://pay.pesapal.com/v3
PESAPAL_IPN_ID=
PESAPAL_CALLBACK_URL=https://your-domain.com/webhook/pesapal/ipn
PESAPAL_CURRENCY=UGX
```

### Available Currencies

| Currency | Code | Countries |
|----------|------|-----------|
| Ugandan Shilling | UGX | Uganda |
| Kenyan Shilling | KES | Kenya |
| Tanzanian Shilling | TZS | Tanzania |
| US Dollar | USD | International |

---

## Step 3: Register an IPN URL

The IPN (Instant Payment Notification) URL is where Pesapal sends payment confirmations. You need to register it before processing payments.

### Option A: Register via the System (Recommended)

Start the Laravel development server:

```bash
php artisan serve
```

Run the registration command. The system provides an Artisan command to register your IPN URL with Pesapal:

```bash
php artisan pesapal:register-ipn
```

If this command is not available, you can register via the API:

```bash
curl -X POST https://pay.pesapal.com/v3/api/Auth/RequestToken \
  -H "Content-Type: application/json" \
  -d '{"consumer_key": "YOUR_KEY", "consumer_secret": "YOUR_SECRET"}'
```

Then use the token to register your IPN:

```bash
curl -X POST https://pay.pesapal.com/v3/api/URLSetup/RegisterIPN \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"url": "https://your-domain.com/webhook/pesapal/ipn", "ipn_notification_type": "POST"}'
```

Save the returned `ipn_id` and add it to your `.env`:

```env
PESAPAL_IPN_ID=your_ipn_id_here
```

### Option B: Register via Pesapal Developer Dashboard

1. Log in to the **Pesapal Developer Portal**
2. Go to **IPN Setup**
3. Click **Register IPN**
4. Enter your webhook URL: `https://your-domain.com/webhook/pesapal/ipn`
5. Select notification type: **POST**
6. Copy the generated **IPN ID** and add it to your `.env`

---

## Step 4: Configure Webhook Endpoint

The IPN endpoint is already implemented at:

```
POST /webhook/pesapal/ipn
```

This route is defined in `routes/webhook.php` and is **excluded from CSRF protection** as required for external webhooks.

### IPN Handler Flow

1. Receives POST request from Pesapal
2. Logs the full payload to `pesapal_webhook_logs` table
3. Extracts `OrderTrackingId` and `OrderMerchantReference`
4. Finds the matching order in the database
5. Calls `PesapalClient::getTransactionStatus()` to verify payment
6. If payment is completed → marks order as paid → activates session on MikroTik
7. If payment failed → marks order as failed
8. Always returns HTTP 200 (Pesapal expects fast response)

### Make the Webhook Publicly Accessible

For Pesapal to reach your webhook, your server must be publicly accessible:

- **Production:** Use your domain with HTTPS
- **Testing / Sandbox:** Use a tool like **ngrok** to expose your local server:
  ```bash
  ngrok http 8000
  ```
  Then update your `.env`:
  ```env
  PESAPAL_CALLBACK_URL=https://your-ngrok-id.ngrok.io/webhook/pesapal/ipn
  APP_URL=https://your-ngrok-id.ngrok.io
  ```

---

## Step 5: Testing with Sandbox

### Sandbox Test Cards

| Payment Method | Details |
|---------------|---------|
| Mobile Money | Use any phone number (no real charge) |
| Card (Success) | `4000 0000 0000 0000` | Exp: `12/25` | CVV: `123` |
| Card (Failure) | `4000 0000 0000 0002` | Exp: `12/25` | CVV: `123` |

### Test the Full Flow

1. Set `PESAPAL_ENVIRONMENT=sandbox` in your `.env`
2. Open the captive portal: `http://localhost:8000/?mac=AA:BB:CC:DD:EE:FF&ip=192.168.88.100`
3. Select a package and proceed to checkout
4. Enter a phone number and click "Pay with Pesapal"
5. You'll be redirected to Pesapal's sandbox payment page
6. Use the test card `4000 0000 0000 0000`
7. After payment, you'll be redirected back
8. Check the admin dashboard for the order status

### Verify IPN Delivery

1. Make a test payment in sandbox
2. Check the `pesapal_webhook_logs` table:
   ```sql
   SELECT * FROM pesapal_webhook_logs ORDER BY created_at DESC LIMIT 5;
   ```
3. Verify the IPN was received and `processed` is `true`

---

## Step 6: Go Live

### Prerequisites for Going Live

- ✅ Production Pesapal merchant account (KYC approved)
- ✅ Domain with HTTPS (SSL certificate)
- ✅ Publicly accessible server
- ✅ IPN URL registered with production keys
- ✅ Webhook endpoint tested and verified

### Switch to Live

1. Update your `.env`:
   ```env
   PESAPAL_ENVIRONMENT=live
   PESAPAL_CONSUMER_KEY=your_live_key
   PESAPAL_CONSUMER_SECRET=your_live_secret
   PESAPAL_IPN_ID=your_live_ipn_id
   PESAPAL_CALLBACK_URL=https://your-domain.com/webhook/pesapal/ipn
   ```

2. Re-register your IPN URL in the live environment (the IPN ID is different from sandbox)

3. Test with a real payment of a small amount

---

## API Endpoints Reference

| Method | Endpoint | Description |
|--------|----------|-------------|
| POST | `/api/Auth/RequestToken` | Get OAuth2 access token |
| POST | `/api/URLSetup/RegisterIPN` | Register IPN URL |
| GET | `/api/URLSetup/GetIpnList` | List registered IPNs |
| POST | `/api/Transactions/SubmitOrderRequest` | Submit a payment order |
| GET | `/api/Transactions/GetTransactionStatus?orderTrackingId={id}` | Check payment status |

---

## Troubleshooting

**"Invalid consumer key/secret":**
- Double-check your credentials in the Pesapal developer dashboard
- Ensure you're using the correct environment (sandbox vs live)
- Keys are different for sandbox and live

**"IPN not received":**
- Verify your server is publicly accessible
- Check that the webhook route is excluded from CSRF
- Check Laravel logs: `storage/logs/laravel.log`
- Re-register your IPN URL in the Pesapal dashboard

**"Payment not confirming":**
- Check the `pesapal_webhook_logs` table for received IPNs
- Check the `orders` table for the matching order
- Verify the `PESAPAL_IPN_ID` in your `.env` matches the registered IPN
- Manually verify the transaction status using `PesapalClient::getTransactionStatus()`

**"SSL certificate error":**
- Pesapal's live environment requires HTTPS
- Use Let's Encrypt for free SSL certificates
- Ensure your server's time is correct (NTP synced)

**"CURL timeout":**
- Ensure your server can reach `https://pay.pesapal.com/v3`
- Check firewall rules
- Increase timeout in `config/pesapal.php` if needed
