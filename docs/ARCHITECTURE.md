# System Architecture

Architectural overview of the Buku Tu Internet hotspot billing system.

---

## High-Level Architecture

```
┌─────────────────────────────────────────────────────────┐
│                   Internet Users                         │
│                    (Customers)                           │
└──────────────────────┬──────────────────────────────────┘
                       │
                       ▼
┌─────────────────────────────────────────────────────────┐
│              MikroTik Hotspot Router                     │
│  ┌──────────┐  ┌──────────┐  ┌──────────────────────┐  │
│  │ DHCP     │  │ Hotspot  │  │ REST API (port 8728) │  │
│  │ Server   │  │ Server   │  │ hotspot/user         │  │
│  └──────────┘  └──────────┘  │ hotspot/active       │  │
│                               │ walled-garden        │  │
│                               └──────────┬───────────┘  │
└──────────────────────────────────────────┼──────────────┘
                                           │
                                           ▼
┌──────────────────────────────────────────────────────────┐
│                   Billing Server                          │
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │            Laravel Application                    │    │
│  │                                                   │    │
│  │  ┌───────┐ ┌──────────┐ ┌──────────────────┐    │    │
│  │  │Admin  │ │Captive   │ │    API Layer     │    │    │
│  │  │Panel  │ │Portal    │ │ Portal/Customer  │    │    │
│  │  │(Filam)│ │(Blade)   │ │ /Admin/Webhook   │    │    │
│  │  └───────┘ └──────────┘ └──────────────────┘    │    │
│  │                                                   │    │
│  │  ┌──────────┐ ┌──────────┐ ┌──────────────────┐    │    │
│  │  │MikroTik  │ │Pesapal  │ │   Services       │    │    │
│  │  │Client    │ │Client   │ │ Order/Session/    │    │    │
│  │  │          │ │          │ │ Router/Report    │    │    │
│  │  └──────────┘ └──────────┘ └──────────────────┘    │    │
│  │                                                   │    │
│  │  ┌───────────────────────────────────────────┐    │    │
│  │  │           Jobs & Scheduler                  │    │    │
│  │  │ ExpireSessions / DisconnectExpired          │    │    │
│  │  │ RetryPayments / CollectStats / Cleanup      │    │    │
│  │  └───────────────────────────────────────────┘    │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │              MySQL Database                       │    │
│  │  customers / orders / packages / payments         │    │
│  │  active_sessions / routers / webhook_logs         │    │
│  └─────────────────────────────────────────────────┘    │
│                                                          │
│  ┌─────────────────────────────────────────────────┐    │
│  │              Redis / Queue                       │    │
│  │  Job queue / Cache / Sessions                    │    │
│  └─────────────────────────────────────────────────┘    │
└──────────────────────────────────────────────────────────┘
                           │
                           ▼
┌──────────────────────────────────────────────────────────┐
│                    Pesapal Gateway                       │
│              (https://pay.pesapal.com/v3)                │
│  ┌────────────────┐  ┌─────────────────────────────┐   │
│  │  Payment Page  │  │  IPN Webhook Callback        │   │
│  │  (User pays)   │  │  → POST /webhook/pesapal/ipn│   │
│  └────────────────┘  └─────────────────────────────┘   │
└──────────────────────────────────────────────────────────┘
```

---

## Key Architectural Decisions

### 1. Customer vs User Models

We use a **separate `Customer` model** (not Laravel's `User` model) for hotspot users. This avoids conflict with Filament's authentication system which uses the `User` model for admin login.

- `User` — System administrators (Filament login)
- `Customer` — Hotspot internet users (API identification via MAC address)

### 2. Two-Phase Session Expiry

Session expiration is a two-step process:

```
Phase 1: ExpireSessionsJob (every minute)
  → Find sessions where expiry_time <= now()
  → Update status to 'expired'
  → Set disconnected_at = now()

Phase 2: DisconnectExpiredUsersJob (every minute)
  → Find recently expired sessions
  → Call MikroTik API to deauthorize the MAC address
  → Clean up hotspot user records
```

This separation ensures that even if MikroTik API calls fail (timeout, network issue), the session is still marked as expired in the database.

### 3. Payment Verification Flow

Payments are verified via the **Pesapal v3 API** in a verification chain:

```
Customer pays on Pesapal
       │
       ▼
Pesapal sends IPN to /webhook/pesapal/ipn
       │
       ▼
Handler logs payload → looks up order → calls getTransactionStatus()
       │
       ├── Completed → processSuccessfulPayment()
       │       │
       │       └── Mark order paid → activate MikroTik session
       │
       ├── Failed → mark order as failed
       │
       └── Pending → leave as-is (will retry later)
```

Additionally, the `RetryPaymentVerificationJob` runs every 5 minutes to re-check stale pending orders.

### 4. API Resource Layer

All API responses use **Laravel API Resources** (`App\Http\Resources\*`) for consistent data transformation:

- Phone numbers masked for privacy (`mask_phone()` helper)
- Monetary values cast to float
- Nested relations loaded only when requested
- Computed fields (e.g., `time_remaining_formatted`, `duration_label`)
- Conditional relation loading via `whenLoaded()`

### 5. Language Support

The captive portal supports **English** and **Swahili** via Laravel's localization system:

```
resources/lang/
├── en/
│   └── portal.php     # English strings
└── sw/
    └── portal.php     # Swahili strings
```

Users can switch languages via the `/lang/{locale}` route.

---

## Data Flow Sequences

### Customer Purchase Flow

```
1. Customer connects to WiFi hotspot
2. MikroTik redirects → GET /?mac=AA:BB:CC&ip=192.168.88.x
3. System shows package selection page
4. Customer selects package → POST /api/portal/orders
5. System:
   a. Creates/updates Customer record (by MAC)
   b. Creates Order (status: pending)
   c. Calls Pesapal::submitOrder()
   d. Saves pesapal_tracking_id to order
   e. Returns redirect_url
6. Browser redirects to Pesapal payment page
7. Customer pays via mobile money or card
8. Pesapal → POST /webhook/pesapal/ipn
9. System:
   a. Logs IPN to pesapal_webhook_logs
   b. Calls Pesapal::getTransactionStatus()
   c. If completed: marks order paid, activates MikroTik session
10. Customer's browser polls GET /api/portal/orders/{ref}
11. When status = 'paid', redirects to /success/{ref}
12. Customer has internet access
```

### Session Expiry Flow

```
Clock tick (every minute)
       │
       ▼
ExpireSessionsJob:
  → SELECT * FROM active_sessions WHERE status='active' AND expiry_time <= NOW()
  → UPDATE status = 'expired', disconnected_at = NOW()
       │
       ▼
DisconnectExpiredUsersJob:
  → SELECT * FROM active_sessions WHERE status='expired' AND disconnected_at > 30min ago
  → For each: MikroTikClient::deauthorizeByMac(mac)
  → For each: MikroTikClient::removeHotspotUser(mac)
```

---

## Database Schema

```
customers         
├── id (PK)
├── mac_address (UNIQUE INDEX)
├── phone_number (INDEX)
├── full_name
├── email
├── ip_address
├── device_name
├── router_id (FK → routers)
└── timestamps

orders            
├── id (PK)
├── order_reference (UNIQUE INDEX)
├── customer_id (FK → customers)
├── package_id (FK → packages)
├── router_id (FK → routers)
├── amount
├── status (INDEX)
├── payment_method
├── pesapal_tracking_id (INDEX)
├── pesapal_merchant_ref
├── transaction_reference
├── paid_at
├── expired_at
└── timestamps

packages          
├── id (PK)
├── name
├── price
├── duration_minutes
├── upload_speed
├── download_speed
├── mikrotik_profile
├── is_active
├── sort_order
└── timestamps

active_sessions   
├── id (PK)
├── customer_id (FK → customers)
├── order_id (FK → orders)
├── package_id (FK → packages)
├── router_id (FK → routers)
├── mac_address (INDEX)
├── mikrotik_username
├── mikrotik_profile
├── start_time
├── expiry_time (INDEX)
├── status (INDEX)
├── disconnected_at
└── timestamps

payments          
├── id (PK)
├── order_id (FK → orders)
├── amount
├── provider
├── provider_tracking_id
├── provider_reference
├── status (INDEX)
├── confirmation_code
├── response_payload (JSON)
└── timestamps

routers           
├── id (PK)
├── name
├── ip_address
├── api_port
├── username
├── password (ENCRYPTED)
├── connection_status
├── last_seen_at
├── is_active
└── timestamps
```

---

## Security Architecture

### Password Encryption
- Router passwords are encrypted using Laravel's `encrypt()` (AES-256-CBC via APP_KEY)
- Decrypted only at runtime when making MikroTik API calls

### API Security
- All sensitive endpoints rate-limited
- Admin endpoints gated by `auth:sanctum` + `can:admin-access`
- Portal API has strict rate limiting on order creation (anti-abuse)

### Webhook Security
- IPN endpoint excluded from CSRF (required for external webhooks)
- IPN verified by calling Pesapal's `getTransactionStatus()` API
- Duplicate IPN detection prevents double-activation

### Data Privacy
- Phone numbers masked in API responses (`mask_phone()` helper)
- Passwords never returned in API responses
- Payment payloads stored encrypted in database

---

## Deployment Options

### Option 1: Docker (Recommended)
```
1 container (app) = nginx + php-fpm + horizon + scheduler
1 container (mysql)
1 container (redis)
```

### Option 2: Manual Server
```
nginx → PHP-FPM → Laravel
Supervisor manages: horizon, scheduler
MySQL on localhost or separate server
Redis for queue/cache/sessions
```
