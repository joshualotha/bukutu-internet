# Buku Tu Internet — Implementation Plan

> **STATUS: NOT STARTED**
>
> This document is the source of truth. The agent MUST read this before every task and update it after every completed task. Nothing is done until it's checked below.

---

## Phase 0: Project Scaffolding

- [ ] Initialize Laravel project (`laravel new` or `composer create-project`)
- [ ] Configure `.env` (database, app name, Pesapal placeholders)
- [ ] Install Filament (`composer require filament/filament`)
- [ ] Install Filament admin panel
- [ ] Create super admin user
- [ ] Install Laravel Horizon (`composer require laravel/horizon`)
- [ ] Install Pest (`composer require pestphp/pest --dev`)
- [ ] Install Laravel Excel for exports (`composer require maatwebsite/excel`)
- [ ] Install Barryvdh Dompdf for PDF exports (`composer require barryvdh/laravel-dompdf`)
- [ ] Set up Tailwind CSS (already in Laravel, configure for portal)
- [ ] Configure `config/app.php` (timezone: Africa/Nairobi, locale: en + sw)
- [ ] Create `routes/webhook.php` and register in `RouteServiceProvider`
- [ ] Configure queue to Redis/database
- [ ] Initialize git repository
- [ ] Verify `php artisan serve` works

---

## Phase 1: Database — Migrations

- [ ] Create `routers` migration
- [ ] Create `packages` migration
- [ ] Create `users` migration (customers, NOT the auth users table — rename or handle conflict)
- [ ] Create `orders` migration
- [ ] Create `active_sessions` migration
- [ ] Create `payments` migration
- [ ] Create `pesapal_webhook_logs` migration
- [ ] Create `admin_activity_logs` migration
- [ ] Run `php artisan migrate` — verify all tables exist
- [ ] Create database indexes (mac_address, order_reference, etc.)

---

## Phase 2: Models & Enums

- [ ] Create Enum: `App\Enums\PaymentStatus`
- [ ] Create Enum: `App\Enums\SessionStatus`
- [ ] Create Enum: `App\Enums\RouterConnectionStatus`
- [ ] Create Model: `App\Models\Router` (with casts, relations)
- [ ] Create Model: `App\Models\Package` (with casts, relations)
- [ ] Create Model: `App\Models\User` (with casts, relations — customer user)
- [ ] Create Model: `App\Models\Order` (with casts, relations)
- [ ] Create Model: `App\Models\ActiveSession` (with casts, relations)
- [ ] Create Model: `App\Models\Payment` (with casts, relations)
- [ ] Create Model: `App\Models\PesapalWebhookLog`
- [ ] Create Model: `App\Models\AdminActivityLog`
- [ ] Write Pest unit tests for model relationships
- [ ] Define all Eloquent relationships (hasMany, belongsTo, etc.)

---

## Phase 3: MikroTik Integration

- [ ] Create `App\Integrations\MikroTik\MikroTikClient` class
- [ ] Implement `testConnection()` → `GET /rest/system/resource`
- [ ] Implement `createHotspotUser()` → `PUT /rest/ip/hotspot/user`
- [ ] Implement `enableHotspotUser()` → `PATCH /rest/ip/hotspot/user/{id}`
- [ ] Implement `disableHotspotUser()` → `PATCH /rest/ip/hotspot/user/{id}`
- [ ] Implement `removeHotspotUser()` → `DELETE /rest/ip/hotspot/user/{id}`
- [ ] Implement `getHotspotUser()` → `GET /rest/ip/hotspot/user?name={mac}`
- [ ] Implement `getActiveUsers()` → `GET /rest/ip/hotspot/active`
- [ ] Implement `getHotspotHost()` → `GET /rest/ip/hotspot/host?mac-address={mac}`
- [ ] Implement `disconnectSession()` → `DELETE /rest/ip/hotspot/active/{id}`
- [ ] Implement `authorizeByMac()` → bypass hotspot for MAC
- [ ] Implement `deauthorizeByMac()` → remove from bypass
- [ ] Implement `applyProfile()` → set user profile
- [ ] Implement `getSystemResources()` → `GET /rest/system/resource`
- [ ] Add error handling: timeouts, connection refused, auth failures
- [ ] Add logging for all API calls
- [ ] Write Pest unit tests with mocked HTTP responses
- [ ] Create `config/mikrotik.php` config file

---

## Phase 4: Pesapal Integration

- [ ] Create `App\Integrations\Pesapal\PesapalClient` class
- [ ] Implement OAuth2 token acquisition (`getAccessToken()`)
- [ ] Implement token caching (cache until expiry, refresh on 401)
- [ ] Implement `registerIpn()` → `POST /api/URLSetup/RegisterIPN`
- [ ] Implement `getRegisteredIpns()` → `GET /api/URLSetup/GetIpnList`
- [ ] Implement `submitOrder()` → `POST /api/Transactions/SubmitOrderRequest`
- [ ] Implement `getTransactionStatus()` → `GET /api/Transactions/GetTransactionStatus`
- [ ] Implement `verifyIpnRequest()` → validate IPN signature
- [ ] Create `config/pesapal.php` config file
- [ ] Write Pest unit tests with mocked HTTP responses
- [ ] Test with Pesapal sandbox environment

---

## Phase 5: Services (Business Logic)

- [ ] Create `App\Services\OrderService`
  - `createOrder()` — creates order, submits to Pesapal
  - `checkOrderStatus()` — polls or verifies
  - `processSuccessfulPayment()` — activates user on MikroTik
- [ ] Create `App\Services\SessionService`
  - `activateSession()` — creates ActiveSession + authorizes on MikroTik
  - `expireSession()` — marks expired + deauthorizes on MikroTik
  - `suspendSession()` — suspends access
  - `extendSession()` — extends expiry time
- [ ] Create `App\Services\RouterService`
  - `getClientForRouter()` — returns MikroTikClient for a given router
  - `testAllRouters()` — tests connectivity for all routers
- [ ] Create `App\Services\ReportService`
  - `dailyRevenue()`
  - `monthlyRevenue()`
  - `popularPackages()`
  - `customerRetention()`
  - `activeUsersByDay()`
  - `failedPayments()`
  - `deviceUsage()`
- [ ] Create `App\Services\DashboardService`
  - Aggregate metrics for admin dashboard
- [ ] Write Pest feature tests for OrderService
- [ ] Write Pest feature tests for SessionService

---

## Phase 6: REST API (Captive Portal + Customer + Admin API)

- [ ] Create `app/Http/Controllers/Api/Portal/PackageController`
  - `index()` — list active packages
  - `show($id)` — package details
- [ ] Create `app/Http/Controllers/Api/Portal/OrderController`
  - `store()` — create order (mac, package_id, phone, name)
  - `show($reference)` — check order status
- [ ] Create `app/Http/Controllers/Api/Portal/SessionController`
  - `showByMac($mac)` — get active session
  - `checkAuth($mac)` — check if authorized
- [ ] Create `app/Http/Controllers/Api/Portal/UserController`
  - `showByMac($mac)` — get user info
  - `update()` — update profile
- [ ] Create `app/Http/Controllers/Api/Customer/ProfileController`
- [ ] Create `app/Http/Controllers/Api/Customer/SessionController`
- [ ] Create `app/Http/Controllers/Api/Customer/OrderController`
- [ ] Create `app/Http/Controllers/Api/Customer/DeviceController`
- [ ] Create `app/Http/Controllers/Api/Admin/DashboardController`
- [ ] Create `app/Http/Controllers/Api/Admin/RouterController`
- [ ] Create `app/Http/Controllers/Api/Admin/SessionController` (suspend/extend)
- [ ] Create `app/Http/Controllers/Api/Admin/PaymentController` (refund)
- [ ] Create Form Requests for all POST/PUT endpoints
- [ ] Create API Resource classes for all models
- [ ] Define all routes in `routes/api.php`
- [ ] Configure rate limiting for API
- [ ] Write Pest feature tests for all API endpoints

---

## Phase 7: Pesapal Webhook/IPN Handler

- [ ] Create `app/Http/Controllers/Webhook/PesapalIpnController`
  - `handle()` — receives IPN, verifies, processes
- [ ] Define route in `routes/webhook.php`
- [ ] Exclude webhook route from CSRF protection (`VerifyCsrfToken`)
- [ ] Implement IPN verification (signature check, IP whitelist)
- [ ] Implement duplicate IPN detection (check for existing tracking ID)
- [ ] Implement payment status check and session activation flow
- [ ] Handle all IPN types (payment complete, failed, pending, cancelled)
- [ ] Log all webhook payloads
- [ ] Return 200 quickly, queue heavy work
- [ ] Write Pest feature tests for IPN handler (mock Pesapal responses)
- [ ] Write test for: valid IPN activates session
- [ ] Write test for: duplicate IPN does not double-activate
- [ ] Write test for: failed payment does not activate
- [ ] Write test for: invalid IPN signature is rejected

---

## Phase 8: Scheduler Jobs

- [ ] Create `App\Jobs\ExpireSessionsJob`
  - Find sessions past expiry → mark expired
  - Write Pest test
- [ ] Create `App\Jobs\DisconnectExpiredUsersJob`
  - Find expired sessions → deauthorize on MikroTik
  - Write Pest test
- [ ] Create `App\Jobs\RetryPaymentVerificationJob`
  - Find stale pending orders → re-verify with Pesapal
  - Write Pest test
- [ ] Create `App\Jobs\CollectUsageStatisticsJob`
  - Collect stats from routers
  - Write Pest test
- [ ] Create `App\Jobs\CleanupOldLogsJob`
  - Delete old logs
  - Write Pest test
- [ ] Create `App\Jobs\TestRouterConnectionsJob`
  - Ping all routers, update status
  - Write Pest test
- [ ] Register all jobs in `routes/console.php` scheduler
- [ ] Verify schedule is registered in cron: `* * * * * php artisan schedule:run`

---

## Phase 9: Captive Portal Frontend (Blade + Tailwind + Alpine)

- [ ] Create layout: `resources/views/portal/layouts/app.blade.php`
  - Mobile-first, Tailwind CSS
  - Dark mode support
  - Language switcher (EN/SW)
  - Branding config (logo, colors from env/config)
- [ ] Create `resources/views/portal/landing.blade.php`
  - Welcome message, branding
  - "Connect to Internet" button
  - Auto-detect MAC/IP from query params (parse and store)
  - Terms of service link (modal or page)
- [ ] Create `resources/views/portal/packages.blade.php`
  - Package cards grid (mobile-first, 1 col mobile, 3 col desktop)
  - Each card: name, price, duration, speeds, "Buy" button
  - Alpine.js for selection
  - Loading state on purchase
- [ ] Create `resources/views/portal/checkout.blade.php`
  - Selected package summary
  - Phone number input (required)
  - Name input (optional)
  - Order summary (amount)
  - "Pay with Pesapal" action button
  - Terms & conditions checkbox
  - Alpine.js form handling
- [ ] Create `resources/views/portal/processing.blade.php`
  - Payment redirect/polling logic
  - Loading spinner
  - Polling via Alpine.js fetch to check order status
  - Auto-redirect to success on confirmation
  - Timeout handling
- [ ] Create `resources/views/portal/success.blade.php`
  - Success icon/celebration
  - Session details (package name, expiry time, speed)
  - Time remaining countdown (Alpine.js timer)
  - "Start Browsing" button
  - "Buy More" link
- [ ] Create `resources/views/portal/status.blade.php`
  - Current session info
  - Countdown timer to expiry
  - Data usage (placeholder)
  - Purchase history link
  - "Extend/Upgrade" buttons
- [ ] Create `resources/views/portal/error.blade.php`
  - Friendly error message
  - "Try Again" button
  - Support contact
- [ ] Create `resources/views/portal/terms.blade.php` — Terms & Conditions page
- [ ] Create portal web routes in `routes/web.php`
- [ ] Implement language files (English + Swahili) in `resources/lang/`
  - All portal strings, error messages, labels

---

## Phase 10: Admin Dashboard (Filament)

- [ ] Configure Filament theme (brand colors, dark mode)
- [ ] Create custom Filament Dashboard page
  - Replace default with `App\Filament\Pages\Dashboard`
- [ ] Create Dashboard Widget: `StatsOverviewWidget`
  - Total users, Active sessions, Revenue today, Revenue this month, Expired users, Pending payments
- [ ] Create Dashboard Widget: `RevenueChartWidget` (line chart, 30 days)
- [ ] Create Dashboard Widget: `ActiveSessionsChartWidget` (line chart by hour)
- [ ] Create Dashboard Widget: `PopularPackagesChartWidget` (bar chart)
- [ ] Create Dashboard Widget: `RecentOrdersWidget` (table, last 10)
- [ ] Create Dashboard Widget: `RouterStatusWidget` (status cards per router)
- [ ] Create Dashboard Widget: `FailedPaymentsWidget` (count + list)
- [ ] Create `App\Filament\Resources\UserResource`
  - List, View, Edit
  - Filters: date range, router
  - Actions: View Sessions, Suspend User
- [ ] Create `App\Filament\Resources\PackageResource`
  - List, Create, Edit, Delete
  - Sortable order
  - Toggle active status
- [ ] Create `App\Filament\Resources\OrderResource`
  - List, View (read-only)
  - Filters: status, date range, payment method
  - Actions: View Payment, Manual Verify
- [ ] Create `App\Filament\Resources\PaymentResource`
  - List, View
  - Filters: status, provider, date
  - Actions: Export, View Payload
- [ ] Create `App\Filament\Resources\ActiveSessionResource`
  - List, View
  - Filters: status, router
  - Actions: Suspend, Extend, Disconnect
- [ ] Create `App\Filament\Resources\RouterResource`
  - List, Create, Edit, Delete
  - Form: encrypt password on save
  - Actions: Test Connection, View Sessions
  - Status indicator
- [ ] Create `App\Filament\Resources\AdminActivityLogResource` (read-only, filterable)
- [ ] Create `App\Filament\Resources\PesapalWebhookLogResource` (read-only, filterable)
- [ ] Configure Filament navigation groups
- [ ] Set up role-based permissions for Filament resources
- [ ] Create Manager role with limited access
- [ ] Create Support Staff role with limited access
- [ ] Create Reports page(s) in Filament with export buttons (CSV, Excel, PDF)

---

## Phase 11: Notifications

- [ ] Create `App\Notifications\PaymentConfirmed` notification
  - Email template
  - Database notification
- [ ] Create `App\Notifications\PackageExpiringSoon` notification
  - 1 hour before
  - 10 minutes before
- [ ] Create `App\Notifications\PaymentFailed` notification
- [ ] Create `App\Notifications\RouterOffline` notification (admin only)
- [ ] Create email templates in `resources/views/emails/`
- [ ] Configure mail settings in `.env`
- [ ] (Optional) Set up Africa's Talking SMS integration
- [ ] (Optional) Create SMS notification channel

---

## Phase 12: Security Hardening

- [ ] Verify HTTPS enforcement (`APP_FORCE_HTTPS=true` in production)
- [ ] Verify router passwords are encrypted in DB and decrypted only at call time
- [ ] Verify Pesapal API keys only in `.env`, never logged
- [ ] Implement IPN source validation
- [ ] Implement IPN replay attack protection
- [ ] Add rate limiting middleware to all API routes
- [ ] Add stricter rate limiting on order creation
- [ ] Configure CORS (`config/cors.php`)
- [ ] Verify CSRF protection on all web routes
- [ ] Audit all API responses for sensitive data exposure
- [ ] Implement proper error handling (no stack traces in production)
- [ ] Add `APP_DEBUG=false` for production
- [ ] Log all admin actions
- [ ] Verify all Form Requests validate input properly
- [ ] Security scan: check `.env` is in `.gitignore`
- [ ] Security scan: check no hardcoded secrets in code

---

## Phase 13: Testing — Comprehensive

- [ ] Run all unit tests: `php artisan test --testsuite=Unit`
- [ ] Run all feature tests: `php artisan test --testsuite=Feature`
- [ ] Write integration test for full flow: connect → order → pay → IPN → activate → expire
- [ ] Test MikroTikClient with all error scenarios (timeout, bad auth, connection refused)
- [ ] Test PesapalClient with all error scenarios
- [ ] Test IPN handler with various payloads
- [ ] Test session expiry logic
- [ ] Test scheduler jobs
- [ ] Test rate limiting on API
- [ ] Test admin permissions/roles
- [ ] Ensure test coverage ≥ 80% for critical paths
- [ ] Run `php artisan test --coverage` to verify

---

## Phase 14: Docker & Deployment

- [ ] Create `Dockerfile` (multi-stage, optimized for production)
- [ ] Create `docker-compose.yml` (app + mysql + redis + horizon)
- [ ] Add `.dockerignore`
- [ ] Create `docker/nginx/default.conf` (or use Laravel Octane)
- [ ] Create entrypoint script for Docker (run migrations, cache config, etc.)
- [ ] Verify Docker build and containers start correctly
- [ ] Test `docker compose up` with all services

---

## Phase 15: Documentation

- [ ] Write `docs/INSTALLATION.md` — step-by-step setup guide
- [ ] Write `docs/MIKROTIK_SETUP.md` — MikroTik hotspot configuration guide
- [ ] Write `docs/PESAPAL_SETUP.md` — Pesapal sandbox + live setup
- [ ] Write `docs/API.md` — API endpoint documentation
- [ ] Write `docs/ADMIN_GUIDE.md` — Admin dashboard user guide
- [ ] Write `docs/ARCHITECTURE.md` — System architecture overview
- [ ] Write `docs/FAQ.md` — Common troubleshooting

---

## Phase 16: Final Checklist

- [ ] All phases 0-15 complete
- [ ] All tests passing
- [ ] No debug code or comments remaining
- [ ] `.env.example` created with all required variables
- [ ] Environment configs for sandbox and production
- [ ] Database seeders for: demo packages, test router
- [ ] README.md created
- [ ] Code review pass: consistency, naming, conventions
- [ ] Verify all Filament pages load without errors
- [ ] Verify captive portal flow works end-to-end
- [ ] Verify IPN flow works end-to-end
- [ ] Load test: 1000 concurrent users simulation (k6 or similar)
- [ ] Production-ready declaration

---

## Notes

- Target completion: one phase at a time, in order
- Each checked box means: DONE, TESTED, WORKING
- If a decision is made that differs from the spec, document it here:
  - _No deviations yet_
- If a bug is found during testing, note it here:
  - _No bugs yet_
- If a future feature consideration affects current design, note it here:
  - _No design notes yet_

---

**Last updated:** 2026-06-26
**Current phase:** Phase 0 (not started)
