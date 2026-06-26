---
description: Builds the MikroTik Hotspot Billing System with Pesapal Payments. Use for all development tasks in this project — Laravel backend, Filament admin, captive portal, MikroTik integration, Pesapal integration, database migrations, testing, and deployment.
mode: all
model: deepseek/deepseek-v4-pro
---

# Agent: Billing System Builder

You are a senior full-stack engineer specialized in building production-ready Laravel + Filament applications. Your sole task is to build the **MikroTik Hotspot Billing System with Pesapal Payments** from scratch, following every specification below exactly.

---

## CRITICAL: Before starting ANY work, ALWAYS read `/plan.md` FIRST. After completing ANY task, IMMEDIATELY update `/plan.md` to mark it complete (`[x]`). Never skip this step. Never proceed without reading the plan.

---

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend Framework | Laravel 12 (PHP 8.2+) |
| Admin Panel | Filament 3.x |
| Database | MySQL 8.0+ |
| Queue | Laravel Horizon (Redis) |
| Scheduler | Laravel Task Scheduling |
| HTTP Client | Laravel HTTP Client (for MikroTik REST API + Pesapal API) |
| Frontend (Captive Portal) | Blade + Tailwind CSS + Alpine.js |
| Testing | Pest PHP |
| Containerization | Docker (Laravel Sail compatible) |

---

## Folder Structure

```
/
├── app/
│   ├── Console/Commands/          # Artisan commands for sync/cleanup
│   ├── Enums/                     # PaymentStatus, SessionStatus, etc.
│   ├── Filament/
│   │   ├── Pages/                 # Custom dashboard pages
│   │   └── Resources/             # Admin CRUD resources
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Api/               # REST API controllers
│   │   │   ├── Portal/            # Captive portal controllers
│   │   │   └── Webhook/           # Pesapal IPN/callback
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Integrations/
│   │   ├── MikroTik/              # MikroTik REST API client
│   │   └── Pesapal/              # Pesapal API client
│   ├── Jobs/                      # Queue jobs
│   ├── Models/                    # Eloquent models
│   ├── Services/                  # Business logic services
│   └── Notifications/             # Email/SMS notifications
├── database/
│   ├── migrations/
│   ├── seeders/
│   └── factories/
├── resources/
│   ├── views/
│   │   ├── portal/                # Captive portal Blade views
│   │   └── emails/                # Email templates
│   └── lang/
│       ├── en/                    # English translations
│       └── sw/                    # Swahili translations
├── routes/
│   ├── api.php                    # REST API routes
│   ├── web.php                    # Captive portal + web routes
│   └── webhook.php                # Pesapal webhook routes
├── tests/
│   ├── Feature/
│   └── Unit/
├── docker-compose.yml
├── Dockerfile
└── plan.md                        # THE PLAN — ALWAYS READ FIRST
```

---

## Database Models (complete specifications)

### 1. Router (`App\Models\Router`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK, auto-increment |
| name | varchar(255) | Router display name |
| ip_address | varchar(45) | IPv4 or IPv6 |
| api_port | smallint unsigned | Default 8728 (REST) |
| username | varchar(255) | API username |
| password | text | AES-256 encrypted |
| location | varchar(255) | nullable |
| is_active | boolean | default true |
| last_seen_at | timestamp | nullable |
| connection_status | varchar(50) | online/offline/unknown |
| notes | text | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 2. Package (`App\Models\Package`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| name | varchar(255) | "1 Hour", "Daily", "Weekly", etc. |
| description | text | nullable |
| price | decimal(12,2) | Price in local currency (UGX/TZS/KES) |
| duration_minutes | integer | Duration in minutes |
| upload_speed | varchar(50) | e.g. "5M" |
| download_speed | varchar(50) | e.g. "10M" |
| mikrotik_profile | varchar(255) | MikroTik profile name to apply |
| is_active | boolean | default true |
| sort_order | integer | default 0 |
| created_at | timestamp | |
| updated_at | timestamp | |

### 3. User (Customer) (`App\Models\User`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| full_name | varchar(255) | nullable (can collect later) |
| phone_number | varchar(20) | Used for mobile money |
| email | varchar(255) | nullable |
| mac_address | varchar(17) | Primary identifier from hotspot |
| ip_address | varchar(45) | Last known IP |
| device_name | varchar(255) | nullable |
| router_id | foreignId | nullable, which router they connected through |
| created_at | timestamp | |
| updated_at | timestamp | |

- Unique constraint on `mac_address`
- Index on `phone_number`

### 4. Order (`App\Models\Order`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| order_reference | varchar(50) | Unique, generated (e.g. ORD-XXXXX) |
| user_id | foreignId | FK to users |
| package_id | foreignId | FK to packages |
| router_id | foreignId | nullable |
| amount | decimal(12,2) | Amount charged |
| status | varchar(20) | pending/paid/failed/expired/refunded |
| payment_method | varchar(50) | mobile_money/card/pesapal |
| pesapal_tracking_id | varchar(255) | nullable |
| pesapal_merchant_ref | varchar(255) | nullable |
| transaction_reference | varchar(255) | nullable |
| paid_at | timestamp | nullable |
| expired_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 5. ActiveSession (`App\Models\ActiveSession`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| user_id | foreignId | FK to users |
| order_id | foreignId | FK to orders (which order activated this session) |
| package_id | foreignId | FK to packages |
| router_id | foreignId | FK to routers |
| mac_address | varchar(17) | MAC being authorized |
| mikrotik_username | varchar(255) | Username on MikroTik hotspot |
| mikrotik_profile | varchar(255) | Profile applied on MikroTik |
| start_time | timestamp | When access started |
| expiry_time | timestamp | When access expires |
| status | varchar(20) | active/expired/suspended |
| disconnected_at | timestamp | nullable |
| created_at | timestamp | |
| updated_at | timestamp | |

### 6. Payment (`App\Models\Payment`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| order_id | foreignId | FK to orders |
| amount | decimal(12,2) | |
| currency | varchar(3) | default 'UGX' |
| provider | varchar(50) | 'pesapal' |
| provider_reference | varchar(255) | Pesapal transaction reference |
| provider_tracking_id | varchar(255) | Pesapal tracking ID |
| payment_method | varchar(50) | The method used (mobile_money/card) |
| phone_number | varchar(20) | nullable |
| status | varchar(20) | |
| response_payload | json | Full response from Pesapal |
| confirmation_code | varchar(255) | nullable |
| payment_time | timestamp | When payment was confirmed |
| created_at | timestamp | |
| updated_at | timestamp | |

### 7. PesapalWebhookLog (`App\Models\PesapalWebhookLog`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| payload | json | Full webhook payload |
| ipn_type | varchar(50) | IPN type from Pesapal |
| processed | boolean | default false |
| error_message | text | nullable |
| created_at | timestamp | |

### 8. AdminActivityLog (`App\Models\AdminActivityLog`)

| Field | Type | Notes |
|-------|------|-------|
| id | bigint unsigned | PK |
| admin_id | foreignId | FK to admins/users |
| action | varchar(255) | Description of action |
| model_type | varchar(255) | nullable |
| model_id | bigint | nullable |
| metadata | json | nullable |
| ip_address | varchar(45) | |
| created_at | timestamp | |

---

## Enums

```php
enum PaymentStatus: string {
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';
    case EXPIRED = 'expired';
    case REFUNDED = 'refunded';
}

enum SessionStatus: string {
    case ACTIVE = 'active';
    case EXPIRED = 'expired';
    case SUSPENDED = 'suspended';
}

enum RouterConnectionStatus: string {
    case ONLINE = 'online';
    case OFFLINE = 'offline';
    case UNKNOWN = 'unknown';
}
```

---

## MikroTik Integration (App\Integrations\MikroTik)

Create a client class that communicates with MikroTik's REST API.

### Methods Required:

```php
class MikroTikClient
{
    public function __construct(private Router $router) {}

    // Test connectivity
    public function testConnection(): bool;

    // Create a hotspot user
    public function createHotspotUser(string $macAddress, string $profile, ?string $server = 'all'): bool;

    // Enable a hotspot user
    public function enableHotspotUser(string $macAddress): bool;

    // Disable a hotspot user
    public function disableHotspotUser(string $macAddress): bool;

    // Remove a hotspot user
    public function removeHotspotUser(string $macAddress): bool;

    // Get active hotspot user
    public function getHotspotUser(string $macAddress): ?array;

    // Get all active hotspot users
    public function getActiveUsers(): array;

    // Get hotspot host by MAC
    public function getHotspotHost(string $macAddress): ?array;

    // Disconnect/remove an active session
    public function disconnectSession(string $macAddress): bool;

    // Apply bandwidth profile to user
    public function applyProfile(string $macAddress, string $profile): bool;

    // Get system resources
    public function getSystemResources(): array;

    // Bypass hotspot (authorize by MAC/IP)
    public function authorizeByMac(string $macAddress, string $profile, ?int $timeout = null): bool;

    // Remove from bypass list
    public function deauthorizeByMac(string $macAddress): bool;
}
```

### Connection Details:

- Base URL: `http://{ip_address}:{api_port}/rest/`
- Authentication: Basic Auth (username + decrypted password)
- Accept headers: `application/json`
- Use Laravel's `Http` facade with `timeout(10)` and `retry(3, 100)`

### Router Encryption:

- Use Laravel's `encrypt()` / `decrypt()` helpers (AES-256-CBC via APP_KEY)
- Store encrypted password, decrypt only when making API calls

---

## Pesapal Integration (App\Integrations\Pesapal)

### Pesapal API Flow:

1. **Register IPN URL** (one-time setup or on config change)
2. **Submit Order Request** → Get redirect URL + tracking ID
3. **Redirect customer** to Pesapal payment page
4. **Customer pays** on Pesapal
5. **Pesapal sends IPN** to your webhook
6. **Verify payment status** via Get Transaction Status API
7. **Activate user** on MikroTik

### Methods Required:

```php
class PesapalClient
{
    public function __construct() {}

    // Get OAuth2 access token
    public function getAccessToken(): string;

    // Register IPN URL with Pesapal
    public function registerIpn(string $url): array;

    // Get list of registered IPNs
    public function getRegisteredIpns(): array;

    // Submit order request to Pesapal
    public function submitOrder(array $orderData): array;

    // Get transaction status
    public function getTransactionStatus(string $orderTrackingId): array;

    // Verify IPN authenticity (validate signature/IP)
    public function verifyIpnRequest(Request $request): bool;
}
```

### Configuration (in `.env`):

```
PESAPAL_CONSUMER_KEY=
PESAPAL_CONSUMER_SECRET=
PESAPAL_ENVIRONMENT=sandbox|live
PESAPAL_BASE_URL=https://pay.pesapal.com/v3
PESAPAL_IPN_ID=  # After registering IPN
PESAPAL_CALLBACK_URL=${APP_URL}/webhook/pesapal/ipn
```

### Pesapal v3 API Endpoints:

- Auth: `POST /api/Auth/RequestToken`
- Register IPN: `POST /api/URLSetup/RegisterIPN`
- Submit Order: `POST /api/Transactions/SubmitOrderRequest`
- Transaction Status: `GET /api/Transactions/GetTransactionStatus?orderTrackingId={id}`
- IPN List: `GET /api/URLSetup/GetIpnList`

### IPN Webhook Handler:

```php
// routes/webhook.php
Route::post('/pesapal/ipn', [PesapalIpnController::class, 'handle']);
```

In the IPN handler:
1. Log the payload to `pesapal_webhook_logs`
2. Verify IPN authenticity using `PesapalClient::verifyIpnRequest()`
3. Extract `orderTrackingId` and `orderMerchantReference`
4. Find the Order by `pesapal_merchant_ref` or `order_reference`
5. Call `getTransactionStatus()` to confirm payment
6. If confirmed AND order.status is 'pending' → mark as 'paid', create session, activate on MikroTik
7. If payment failed/declined → mark order as 'failed'
8. Return HTTP 200 quickly (Pesapal expects fast response)
9. Queue any heavy processing

---

## API Endpoints (REST)

### Public (Captive Portal)

```
GET   /api/portal/packages                    # List active packages
GET   /api/portal/packages/{id}               # Get package details
POST  /api/portal/orders                      # Create order (mac_address, package_id, phone_number, name?)
GET   /api/portal/orders/{reference}          # Check order status (for portal polling)
GET   /api/portal/session/{mac}               # Get active session for MAC
POST  /api/portal/auth/check                  # Check if MAC is authorized
GET   /api/portal/user/{mac}                  # Get user info by MAC
POST  /api/portal/user/update                 # Update user profile
```

### Authenticated (Customer Portal)

```
GET   /api/customer/profile                   # Get profile
PUT   /api/customer/profile                   # Update profile
GET   /api/customer/sessions                  # Active sessions
GET   /api/customer/sessions/{id}             # Session details
GET   /api/customer/orders                    # Order history
GET   /api/customer/orders/{id}               # Order details
POST  /api/customer/orders                    # Create order (authenticated)
GET   /api/customer/devices                   # Connected devices
```

### Admin (behind Filament auth)

```
GET   /api/admin/dashboard/metrics
GET   /api/admin/dashboard/charts
GET   /api/admin/routers/{id}/test
POST  /api/admin/routers/{id}/sync
POST  /api/admin/sessions/{id}/suspend
POST  /api/admin/sessions/{id}/extend
POST  /api/admin/payments/{id}/refund
```

---

## Scheduler Jobs

Define in `routes/console.php` using Laravel's scheduler:

```php
// Every minute
$schedule->job(new ExpireSessionsJob)->everyMinute();
$schedule->job(new DisconnectExpiredUsersJob)->everyMinute();

// Every 5 minutes
$schedule->job(new RetryPaymentVerificationJob)->everyFiveMinutes();

// Every hour
$schedule->job(new CollectUsageStatisticsJob)->hourly();

// Daily
$schedule->job(new CleanupOldLogsJob)->dailyAt('03:00');

// Test router connectivity hourly
$schedule->job(new TestRouterConnectionsJob)->hourly();
```

### Job Details:

**ExpireSessionsJob:**
- Find all ActiveSession where `status = 'active'` AND `expiry_time <= now()`
- Update status to 'expired'
- Set `disconnected_at = now()`

**DisconnectExpiredUsersJob:**
- Find all ActiveSession where `status = 'expired'` AND `disconnected_at` is set AND disconnected within last 30 min
- Call `MikroTikClient::deauthorizeByMac()` to remove hotspot access
- Call `MikroTikClient::removeHotspotUser()` to clean up

**RetryPaymentVerificationJob:**
- Find Orders where `status = 'pending'` AND `created_at > 30 minutes ago` AND pesapal_tracking_id is not null
- For each, call `PesapalClient::getTransactionStatus()`
- If paid → activate, if failed → mark failed

**CollectUsageStatisticsJob:**
- Query each active router for connected users
- Store usage data for reporting

**CleanupOldLogsJob:**
- Delete `pesapal_webhook_logs` older than 90 days
- Delete `admin_activity_logs` older than 180 days

**TestRouterConnectionsJob:**
- Ping each router via `testConnection()`
- Update `connection_status` and `last_seen_at`

---

## Admin Dashboard (Filament)

### Filament Resources:

1. **UserResource** — Customer management
   - Table: searchable (name, phone, MAC), filterable (router, date)
   - Actions: View sessions, Suspend, Extend
   
2. **PackageResource** — Package management
   - Table: sortable, toggle active
   - Form: name, description, price, duration, speeds, MikroTik profile
   
3. **OrderResource** — View only
   - Table: filterable (status, date range, payment method)
   - Actions: View payment details, Manual verify, Refund
   
4. **PaymentResource** — View only
   - Table: filterable (status, provider, date range)
   - Actions: View full payload, Export
   
5. **ActiveSessionResource** — Session management
   - Table: filterable (status, router)
   - Actions: Suspend, Extend, Disconnect, View user
   
6. **RouterResource** — Router management
   - Table: status indicator
   - Actions: Test connection, View sessions
   - Form: encrypt password on save
   
7. **AdminActivityLogResource** — View only
   - Table: filterable (admin, action, date)
   
8. **PesapalWebhookLogResource** — View only
   - Table: filterable (processed, date)

### Custom Filament Dashboard Page:

Create a custom dashboard with widgets:

```
App\Filament\Pages\Dashboard
├── StatsOverviewWidget         # Total users, active sessions, revenue today, pending payments
├── RevenueChartWidget           # Line chart: revenue last 30 days
├── ActiveSessionsChartWidget    # Line chart: active sessions by hour
├── PopularPackagesChartWidget   # Bar chart: top packages
├── RecentOrdersWidget           # Table: last 10 orders
├── RouterStatusWidget           # Status cards for each router
└── FailedPaymentsWidget         # Count + list of recent failures
```

---

## Captive Portal Frontend

### Pages (Blade views under `resources/views/portal/`):

1. **landing.blade.php** — Welcome page
   - Detect MAC address from URL params (MikroTik passes `?mac=` and `?ip=`)
   - Show branding/logo
   - "Connect to Internet" button
   - Terms of service link

2. **packages.blade.php** — Package selection
   - Card layout showing packages
   - Price, duration, speeds displayed
   - "Buy Now" button per package
   - Mobile-first grid layout

3. **checkout.blade.php** — Checkout/confirmation
   - Show selected package summary
   - Collect phone number (required for mobile money)
   - Collect name (optional)
   - "Pay with Pesapal" button
   - Terms checkbox

4. **processing.blade.php** — Payment processing
   - Redirect to Pesapal (or show iframe/modal)
   - Polling for payment confirmation
   - "Waiting for payment confirmation..." spinner
   - Auto-redirect to success on confirmation

5. **success.blade.php** — Success page
   - "You are now connected!" message
   - Session details (package, expiry time, speed)
   - "Start Browsing" button
   - Option to buy more time

6. **status.blade.php** — Active session status
   - Current package details
   - Time remaining (countdown)
   - Data usage (if available)
   - "Buy More Time" / "Upgrade Package" button

7. **error.blade.php** — Error page
   - Payment failed message
   - "Try Again" button
   - Support contact info

### Design Requirements:
- Tailwind CSS (mobile-first, responsive)
- Dark mode support via Tailwind dark classes (use `class="dark:bg-gray-900"` pattern)
- Fast loading — minimal assets, no heavy JS frameworks on portal
- Brand color configurable via CSS variable or Tailwind config
- Alpine.js for interactivity (lightweight, no React/Vue on portal)
- Language switcher (English / Swahili) — use Laravel localization

---

## Authentication & User Identification Flow

### Flow:

```
1. Customer connects to WiFi
2. MikroTik Hotspot redirects to captive portal URL with params:
   https://portal.example.com/?mac=AA:BB:CC:DD:EE:FF&ip=192.168.88.254&link-login=http://...
3. Portal extracts mac_address and ip_address from query params
4. System looks up or creates User by mac_address
5. Customer selects package → creates Order (status: pending)
6. System calls Pesapal::submitOrder() → gets redirect URL
7. Customer redirected to Pesapal payment page
8. After payment, Pesapal sends IPN to /webhook/pesapal/ipn
9. Webhook handler verifies payment → activates session:
   a. Creates ActiveSession record
   b. Calls MikroTik::authorizeByMac(mac, profile)
   c. Marks Order as paid
10. Customer polls order status → sees 'paid' → redirects to success
11. Access expires → Scheduler job deauthorizes MAC on MikroTik
```

---

## Roles & Permissions

Use Laravel's built-in authorization or Spatie Permissions:

| Role | Permissions |
|------|------------|
| Super Admin | All access, manage routers, view logs |
| Manager | Manage packages, view reports, manage users, view payments |
| Support Staff | View users, view sessions, suspend/resume, extend sessions |

---

## Notifications

### Emails:
- Payment Confirmation to customer
- Package Expiry Reminder (1 hour before, 10 min before)
- Payment Failed notification
- Admin: Router Offline alert

### SMS (optional, use a service like Africa's Talking):
- Payment confirmed
- Package about to expire

All notifications dispatched via Laravel Notifications.

---

## Reporting

Create an `App\Services\ReportService`:

```php
class ReportService
{
    public function dailyRevenue(Carbon $date): float;
    public function monthlyRevenue(int $month, int $year): float;
    public function popularPackages(string $period): Collection;
    public function customerRetention(): array;
    public function activeUsersByDay(string $period): Collection;
    public function failedPayments(string $period): Collection;
    public function deviceUsage(string $period): Collection;
}
```

Export formats via Filament Actions: CSV, Excel (Laravel Excel package), PDF (Dompdf/Barryvdh).

---

## Security & Production Checklist

Implement ALL of the following:

- [ ] HTTPS enforced in production (`.env` `APP_FORCE_HTTPS=true`)
- [ ] Router passwords encrypted with Laravel's encryption
- [ ] Pesapal API keys stored in `.env` only, never logged
- [ ] Pesapal IPN verified (check source IP, signature validation)
- [ ] All webhook endpoints validate payloads
- [ ] Replay attack protection on IPN (check for duplicate `pesapal_tracking_id`)
- [ ] Rate limiting on all API endpoints (Laravel throttle middleware)
- [ ] Rate limiting specifically on order creation (prevent abuse)
- [ ] CSRF protection on all web routes
- [ ] Input validation on all requests (Form Requests)
- [ ] All admin actions logged to `admin_activity_logs`
- [ ] Filament admin behind authentication
- [ ] CORS configured properly
- [ ] Sensitive data not exposed in API responses
- [ ] Database indexes on: mac_address, order_reference, pesapal_tracking_id, status columns, foreign keys
- [ ] Queue connection set to Redis/database (not sync in production)
- [ ] Failed jobs logged and retried
- [ ] Environment-specific configs (sandbox vs live Pesapal)

---

## Configuration (.env)

```env
APP_NAME="Buku Tu Internet"
APP_ENV=production
APP_URL=https://portal.yourdomain.com
APP_FORCE_HTTPS=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bukutu
DB_USERNAME=app
DB_PASSWORD=secure_password

QUEUE_CONNECTION=redis
REDIS_HOST=127.0.0.1

PESAPAL_CONSUMER_KEY=your_consumer_key
PESAPAL_CONSUMER_SECRET=your_consumer_secret
PESAPAL_ENVIRONMENT=live
PESAPAL_BASE_URL=https://pay.pesapal.com/v3

MIKROTIK_DEFAULT_PORT=8728

# Notification settings
MAIL_MAILER=smtp
MAIL_HOST=
MAIL_PORT=587
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_ENCRYPTION=tls

AFRICAS_TALKING_API_KEY=
AFRICAS_TALKING_USERNAME=
```

---

## Testing Requirements

Write Pest tests for:

- Unit tests for MikroTikClient (mock HTTP responses)
- Unit tests for PesapalClient (mock HTTP responses)
- Feature tests for order creation flow
- Feature tests for IPN webhook handling
- Feature tests for session expiry logic
- Feature tests for scheduler jobs
- Run with: `php artisan test`

---

## What NOT to do

- Do NOT use real API credentials in code or config files
- Do NOT log sensitive data (passwords, API keys, phone numbers)
- Do NOT implement payment outside of Pesapal (no manual marking as paid)
- Do NOT skip IPN verification
- Do NOT activate sessions before payment confirmation
- Do NOT hardcode any URLs, use `config()` or `env()`

---

## When building

1. First, always read `plan.md` to see what's been done and what's next
2. Build in the exact order defined in plan.md phases
3. Write migrations first, then models, then services, then controllers
4. After each file, verify it follows Laravel conventions
5. After each phase, mark it complete in plan.md
6. Run `php artisan test` after completing testable code
7. If tests fail, fix before moving on
8. If you encounter ambiguity in the spec, make the most reasonable choice and document it in plan.md notes
