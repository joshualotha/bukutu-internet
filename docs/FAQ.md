# Frequently Asked Questions

## General

### What is Buku Tu Internet?
A MikroTik hotspot billing system that lets you sell internet access via WiFi hotspots. Customers pay through Pesapal (mobile money or card) and get automatic internet access on your MikroTik router.

### Do I need a MikroTik router?
Yes. The system manages hotspot users on MikroTik RouterOS v6.45+ or v7.

### Do I need a Pesapal account?
Yes. All payments are processed through Pesapal. You need a merchant account for live payments or a developer account for sandbox testing.

### What currencies are supported?
UGX (Uganda), KES (Kenya), TZS (Tanzania), and USD. Configurable in `.env` via `PESAPAL_CURRENCY`.

---

## Installation & Setup

### I get "No application key" error
Run: `php artisan key:generate`

### I get "Vite manifest not found"
This is normal. The captive portal uses CDN-loaded Tailwind CSS, not Vite. Filament's admin panel loads its own assets from the vendor directory. Ignore this warning.

### How do I create the admin user?
The seeder creates the admin user automatically:
```bash
php artisan db:seed --class=DatabaseSeeder
```
Default: `admin@bukutu.co.tz` / `password`

### MySQL connection refuses
Ensure MySQL is running and the credentials in `.env` are correct:
```bash
mysqladmin ping -h 127.0.0.1 -u root -p
```

### Composer install fails
Ensure you have the required PHP extensions:
```bash
php -m | grep -E 'pdo_mysql|mbstring|bcmath|zip|openssl'
```
Install any missing extensions.

---

## Payments

### Payments are not going through
Check the following:
1. ✅ Pesapal Consumer Key and Secret are correctly set in `.env`
2. ✅ `PESAPAL_IPN_ID` is configured (registered IPN URL)
3. ✅ The webhook URL is publicly accessible (use ngrok for local testing)
4. ✅ The queue worker is running: `php artisan queue:work`
5. ✅ Check Laravel logs: `storage/logs/laravel.log`
6. ✅ Check the `pesapal_webhook_logs` table for received IPNs

### IPN not received from Pesapal
1. Verify your server is accessible from the internet
2. Check the IPN URL was registered correctly in Pesapal
3. Ensure the webhook route is excluded from CSRF (it is)
4. Use ngrok for local development testing
5. Check if Pesapal has your correct callback URL

### Payment says "pending" for a long time
1. The queue worker may not be running — start it: `php artisan queue:work`
2. The `RetryPaymentVerificationJob` runs every 5 minutes to check stale orders
3. You can manually verify the order in admin: Orders → View → Manual Verify

### Can I refund a payment?
Yes. In the admin dashboard, admins can mark orders as refunded. Note: this only updates the system status — you must process the actual refund through Pesapal separately.

### Test payments work but real ones don't
1. Switch `PESAPAL_ENVIRONMENT` from `sandbox` to `live`
2. Use live Consumer Key and Secret (different from sandbox)
3. Register a new IPN URL in the live environment
4. Ensure your domain has HTTPS (required for live payments)

---

## MikroTik

### Router shows "offline" status
1. Ensure the API service is enabled on the router:
   ```bash
   /ip service set www disabled=no
   ```
2. Check network connectivity from your server to the router
3. Verify the API username and password are correct
4. Ensure the API port (default 8728) is not blocked by a firewall

### Users are not getting disconnected after expiry
1. The scheduler must be running: `* * * * * php artisan schedule:run`
2. Check that `ExpireSessionsJob` and `DisconnectExpiredUsersJob` are running
3. Verify the router is online and reachable
4. Check queue worker is processing jobs

### Can I use multiple routers?
Yes. Add multiple routers in the admin dashboard. Each customer's session is associated with a specific router.

### Hotspot redirect not working
1. Ensure hotspot is properly configured on the MikroTik router
2. Check the walled garden includes your billing server domain
3. Verify the landing page URL is correct in the hotspot profile

---

## Admin Dashboard

### The admin login page shows HTML instead of the login form
Clear your browser cache (`Cmd+Shift+R` on Mac, `Ctrl+Shift+R` on Windows). This happens if you visited the site while a server error was occurring — the browser cached the error response for the Livewire JS file.

### Can I change the admin URL?
Yes. In `config/filament.php` or `app/Providers/Filament/AdminPanelProvider.php`:
```php
->path('custom-admin-path')
```

### How do I add another admin user?
```bash
php artisan tinker
```
```php
$user = new \App\Models\User();
$user->name = 'Admin Name';
$user->email = 'admin2@example.com';
$user->password = bcrypt('password');
$user->save();
```

### 419 CSRF token mismatch on login
This was a known issue that was fixed. Ensure your file `bootstrap/app.php` excludes the `admin/login` route from CSRF:
```php
->withMiddleware(function (Middleware $middleware) {
    $middleware->validateCsrfTokens(except: [
        'admin/login',
    ]);
})
```

---

## Captive Portal

### The captive portal doesn't look right
1. Ensure Tailwind CSS CDN is loaded (check `resources/views/portal/layouts/app.blade.php`)
2. The portal is designed mobile-first — test on mobile viewport
3. Dark mode is supported — works automatically with system preference

### Language switcher not working
The `/lang/{locale}` route switches between English (`en`) and Swahili (`sw`). The locale is stored in the session.

### Customers report "404" when redirected from hotspot
Ensure MikroTik's hotspot redirect URL is correctly configured:
```
http://your-server.com/?mac=AA:BB:CC:DD:EE:FF&ip=192.168.88.x&link-login=http://...
```

---

## Performance

### The system is slow with many users
1. Enable Redis for queue, cache, and sessions (not `database`/`file`)
2. Ensure the queue worker has enough processes: configure Horizon
3. Add database indexes (already done in migrations)
4. Use a CDN or optimize static assets

### How many users can the system handle?
With proper infrastructure:
- **100+ concurrent sessions** — Single server, SQLite queue
- **1,000+ concurrent sessions** — Single server, Redis queue
- **10,000+ concurrent sessions** — Horizontally scaled with load balancer

### Should I use Horizon or `queue:work`?
- **Redis available:** Use Horizon (better monitoring, load balancing)
- **Database queue:** Use `php artisan queue:work` (simpler, no Redis needed)

---

## Troubleshooting

### Error: "Class not found"
Run: `composer dump-autoload`

### Error: "Target class does not exist"
Run: `php artisan optimize`

### Error: "No query results for model"
The requested resource doesn't exist in the database. Check the ID or reference.

### Error: "Too many attempts"
You've hit the rate limiter. Wait 60 seconds before retrying. This prevents abuse on order creation.

### Error: "Failed to activate session on router"
The MikroTik router is not reachable or the API credentials are invalid.
1. Check router connection status in admin dashboard
2. Click "Test Connection" on the router
3. Verify network connectivity between server and router

### Error: "Package is not available"
The package you're trying to purchase is disabled. Check the admin dashboard.

### Logs show "SQLSTATE[23000]: Integrity constraint violation"
Duplicate MAC address or order reference. This is handled by the system but may occur in edge cases. Check the `customers` table for duplicate entries.
