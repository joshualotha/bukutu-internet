# Buku Tu Internet — MikroTik Hotspot Billing System with Pesapal Payments

A production-ready hotspot billing system built with **Laravel 11**, **Filament 3**, and **Pesapal v3 API**. Manage MikroTik hotspot users, sell internet packages, and accept mobile money/card payments — all from a beautiful admin dashboard.

## Features

- **MikroTik Integration** — Create, enable, disable hotspot users, authorize by MAC address, apply bandwidth profiles via REST API
- **Pesapal Payments** — Accept mobile money (M-Pesa, Airtel Money) and card payments via Pesapal v3 API with IPN webhook verification
- **Captive Portal** — Mobile-first, dual-language (English/Swahili) self-service portal for customers to buy packages
- **Admin Dashboard** — Filament 3 panel with real-time stats, revenue charts, session management, router monitoring
- **Package Management** — Configure time-based internet packages with bandwidth limits
- **Session Management** — Automatic session expiry, suspend/resume/extend, disconnect users
- **Scheduled Jobs** — Expire sessions, collect usage stats, retry failed payments, cleanup old logs
- **Reporting** — Export sales, customer, and payment reports to CSV, Excel, PDF
- **Notifications** — Email alerts for payment confirmation, expiring packages, failed payments, router offline

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Backend | Laravel 11 (PHP 8.2+) |
| Admin Panel | Filament 3.x |
| Database | MySQL 8.0+ |
| Queue | Redis (Horizon) / Database fallback |
| HTTP Client | Laravel HTTP Client |
| Frontend (Portal) | Blade + Tailwind CSS + Alpine.js |
| Reporting | Laravel Excel, Dompdf |
| Testing | Pest PHP |

## Quick Start

### Prerequisites

- PHP 8.2+
- Composer
- MySQL 8.0+
- Redis (optional, can use `database` queue driver)

### Installation

```bash
# Clone the repository
git clone https://github.com/joshualotha/bukutu-internet.git
cd buku-tu-internet

# Install dependencies
composer install

# Copy environment file
cp .env.example .env
php artisan key:generate

# Configure your .env file (database, pesapal, mail settings)
# Edit .env with your database credentials

# Run migrations
php artisan migrate

# Seed demo data
php artisan db:seed --class=DatabaseSeeder

# Start the development server
php artisan serve
```

### Default Admin Access

- **URL:** `http://localhost:8000/admin`
- **Email:** `admin@bukutu.co.tz`
- **Password:** `password`

## Configuration

### Pesapal Setup

1. Register at [Pesapal Developer Portal](https://developer.pesapal.com)
2. Get your Consumer Key and Consumer Secret
3. Add to `.env`:

```env
PESAPAL_CONSUMER_KEY=your_consumer_key
PESAPAL_CONSUMER_SECRET=your_consumer_secret
PESAPAL_ENVIRONMENT=sandbox
PESAPAL_IPN_ID=your_ipn_id
```

4. Register your IPN URL: `https://your-domain.com/webhook/pesapal/ipn`

### MikroTik Router Setup

1. Ensure your MikroTik router has the REST API enabled:
   ```
   /ip service set www-ssl disabled=no
   /ip service set www disabled=no
   /user add name=api-user group=full password=secure-password
   ```
2. Add the router via the admin dashboard under **Network → Routers**
3. Test the connection from the router detail page

### Queue Worker

For production, run the queue worker:

```bash
php artisan horizon
# or for database driver:
php artisan queue:work
```

### Scheduler

Add the following cron entry:

```bash
* * * * * cd /path-to-project && php artisan schedule:run >> /dev/null 2>&1
```

## Docker Deployment

```bash
# Build and start containers
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate

# Seed database
docker compose exec app php artisan db:seed --class=DatabaseSeeder
```

## Project Structure

```
app/
├── Console/Commands/        # Artisan commands
├── Enums/                   # PHP enums (PaymentStatus, SessionStatus, etc.)
├── Exports/                 # Laravel Excel export classes
├── Filament/
│   ├── Pages/               # Custom dashboard & reports pages
│   ├── Resources/           # CRUD admin resources
│   └── Widgets/             # Dashboard widgets
├── Http/
│   ├── Controllers/Api/     # REST API (Portal, Customer, Admin)
│   ├── Controllers/Webhook/ # Pesapal IPN handler
│   └── Resources/           # API Resource classes
├── Integrations/
│   ├── MikroTik/            # MikroTik REST API client
│   └── Pesapal/             # Pesapal v3 API client
├── Jobs/                    # Queue jobs (sessions, payments, cleanup)
├── Models/                  # Eloquent models
├── Notifications/           # Email & SMS notifications
└── Services/                # Business logic (Order, Session, Router, Report)
```

## API Endpoints

### Captive Portal (Public)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/portal/packages` | List active packages |
| GET | `/api/portal/packages/{id}` | Package details |
| POST | `/api/portal/orders` | Create order (mac, package_id, phone) |
| GET | `/api/portal/orders/{reference}` | Check order status |
| GET | `/api/portal/session/{mac}` | Get active session |
| POST | `/api/portal/auth/check` | Check MAC authorization |
| GET | `/api/portal/user/{mac}` | Get user by MAC |
| POST | `/api/portal/user/update` | Update user profile |

### Admin (Requires Auth)

| Method | Endpoint | Description |
|--------|----------|-------------|
| GET | `/api/admin/dashboard/metrics` | Dashboard stats |
| GET | `/api/admin/dashboard/charts` | Chart data |
| POST | `/api/admin/routers/{id}/test` | Test router connection |
| POST | `/api/admin/sessions/{id}/suspend` | Suspend session |
| POST | `/api/admin/sessions/{id}/extend` | Extend session |
| POST | `/api/admin/payments/{id}/refund` | Refund payment |

## Testing

```bash
# Run all tests
php artisan test

# Run specific test suites
php artisan test --testsuite=Feature
php artisan test --testsuite=Unit

# Run with coverage (requires Xdebug)
php artisan test --coverage
```

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
