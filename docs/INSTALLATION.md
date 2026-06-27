# Installation Guide

Step-by-step instructions to get Buku Tu Internet running on your server.

## Prerequisites

| Requirement | Version | Notes |
|-------------|---------|-------|
| PHP | 8.2+ | 8.3 recommended |
| Composer | 2.x | PHP dependency manager |
| MySQL | 8.0+ | MariaDB 10.5+ also works |
| Redis | 6.x+ | Optional (can use `database` queue driver) |
| Node.js | 18+ | Only needed if modifying Vite/CSS assets |
| NPM | 9+ | Only needed if modifying frontend assets |

## Quick Install (5 minutes)

### 1. Clone the Repository

```bash
git clone https://github.com/joshualotha/bukutu-internet.git
cd bukutu
```

### 2. Install PHP Dependencies

```bash
composer install --no-interaction
```

### 3. Environment Configuration

```bash
cp .env.example .env
php artisan key:generate
```

Edit `.env` with your database and service credentials:

```env
APP_URL=http://localhost:8000
APP_ENV=local
APP_DEBUG=true

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=bukutu
DB_USERNAME=root
DB_PASSWORD=your_password

# Queue — use 'database' if you don't have Redis
QUEUE_CONNECTION=database

# Cache — use 'file' if you don't have Redis
CACHE_STORE=file

# Session — use 'file' if you don't have Redis
SESSION_DRIVER=file

# Pesapal (get these from https://developer.pesapal.com)
PESAPAL_CONSUMER_KEY=
PESAPAL_CONSUMER_SECRET=
PESAPAL_ENVIRONMENT=sandbox
```

### 4. Create Database

```bash
mysql -u root -p -e "CREATE DATABASE IF NOT EXISTS bukutu CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
```

### 5. Run Migrations & Seed

```bash
php artisan migrate
php artisan db:seed --class=DatabaseSeeder
```

### 6. Start the Server

```bash
php artisan serve
```

Visit **http://localhost:8000/admin** and log in with:

- **Email:** `admin@bukutu.co.tz`
- **Password:** `password`

### 7. Run Queue Worker (Required for Payments)

```bash
# If using database driver:
php artisan queue:work

# If using Redis (Horizon):
php artisan horizon
```

### 8. Set Up Scheduler (Required for Session Expiry)

Add this to your crontab (`crontab -e`):

```bash
* * * * * cd /path/to/buku-tu-internet && php artisan schedule:run >> /dev/null 2>&1
```

---

## Development Setup

### Installing with Mailhog

For email testing:

```bash
# Install Mailhog
brew install mailhog  # macOS
# or download from https://github.com/mailhog/Mailhog

# Run Mailhog
mailhog

# Configure .env
MAIL_MAILER=smtp
MAIL_HOST=127.0.0.1
MAIL_PORT=1025
MAIL_USERNAME=null
MAIL_PASSWORD=null
```

### Installing Redis

```bash
# macOS
brew install redis && brew services start redis

# Ubuntu/Debian
sudo apt install redis-server && sudo systemctl start redis

# Update .env for Redis:
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis
REDIS_CLIENT=phpredis
```

---

## Production Deployment

### Server Requirements

- PHP 8.2+ with extensions: `pdo_mysql`, `mbstring`, `bcmath`, `zip`, `openssl`, `redis`
- MySQL 8.0+
- Redis 6.x+
- Nginx or Apache
- Supervisor (for queue workers)
- SSL certificate (HTTPS required for Pesapal)

### Environment Settings

```env
APP_ENV=production
APP_DEBUG=false
APP_FORCE_HTTPS=true
```

### Nginx Configuration

Use the included nginx config in `docker/nginx/default.conf` as a reference, or deploy with Docker:

```bash
docker compose up -d
```

### Supervisor Configuration

The Docker setup uses Supervisor to manage:
- **nginx** — web server
- **php-fpm** — PHP processor
- **horizon** — queue worker
- **scheduler** — Laravel scheduler

For non-Docker deployments, create Supervisor configs:

```ini
; /etc/supervisor/conf.d/horizon.conf
[program:horizon]
command=php /var/www/html/artisan horizon
user=www-data
autostart=true
autorestart=true
```

```ini
; /etc/supervisor/conf.d/scheduler.conf
[program:scheduler]
command=php /var/www/html/artisan schedule:work
user=www-data
autostart=true
autorestart=true
```

---

## Docker Deployment

### Build and Run

```bash
# Build the image
docker compose build

# Start containers
docker compose up -d

# Run migrations
docker compose exec app php artisan migrate

# Seed database
docker compose exec app php artisan db:seed --class=DatabaseSeeder
```

The app will be available at **http://localhost:80**.

### Docker Environment Variables

Copy the example env file and adjust:

```bash
cp .env.example .env
# Edit .env with your production values
docker compose up -d
```

---

---

## cPanel Deployment

### 1. Clone the Repository

SSH into your cPanel and clone directly into `public_html`:

```bash
cd ~/public_html
git clone https://github.com/joshualotha/bukutu-internet.git .
```

> **Important:** The `public/` folder is the web root. Configure your cPanel to point the domain to `public/`, or use a `.htaccess` rewrite to serve from `public/`.

### 2. Create Database

- Go to **cPanel → MySQL Databases**
- Create a database and a user with all privileges

### 3. Set Up Environment

```bash
cp .env.example .env
# Edit .env with your values (use cPanel File Manager or nano/vim if available)
```

### 4. Install Dependencies

```bash
composer install --no-dev --optimize-autoloader
# Skip npm/vite — not needed for production
```

### 5. Generate App Key & Run Migrations

```bash
php artisan key:generate
php artisan migrate --seed
```

### 6. Cache for Performance

```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 7. Set Up Cron Job (Critical!)

In **cPanel → Cron Jobs**, add this single line:

```
* * * * * /usr/local/bin/php ~/public_html/artisan schedule:run >> /dev/null 2>&1
```

This one cron entry handles **everything**:
- Expiring user sessions every minute
- Disconnecting expired users from MikroTik
- Processing the payment queue
- Retrying stuck payments every 5 minutes
- Collecting router stats hourly
- Cleaning up old logs daily

### 8. The .env Checklist for cPanel

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://yourdomain.com
APP_FORCE_HTTPS=true

QUEUE_CONNECTION=database    # No Redis needed
CACHE_STORE=database         # No Redis needed
SESSION_DRIVER=database      # No Redis needed
```

> **Horizon note:** Laravel Horizon is installed but not used on cPanel (requires Redis + supervisor). The scheduler's `queue:work --stop-when-empty` command handles queue processing automatically every minute.

### 9. Pesapal — Switch to Live

After testing with sandbox:
1. Get Pesapal live API credentials
2. Set `PESAPAL_ENVIRONMENT=live` in `.env`
3. Register the IPN URL with Pesapal using your production domain
4. Update `PESAPAL_IPN_ID` in `.env`

### 10. File Permissions

```bash
chmod -R 775 storage bootstrap/cache
chmod -R 775 public
```

### 11. Updating Later

When you make changes locally and push to GitHub, update the live site with:

```bash
cd ~/public_html
git pull
composer install --no-dev --optimize-autoloader
php artisan migrate
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Troubleshooting Installation

**"Class not found" errors after cloning:**
```bash
composer dump-autoload
```

**"No application key" error:**
```bash
php artisan key:generate
```

**"Connection refused" for MySQL:**
```bash
# Ensure MySQL is running
mysqladmin ping -h 127.0.0.1 -u root -p
```

**"Vite manifest not found":**
This is expected if you're not using Vite. The app uses CDN-loaded Tailwind CSS for the captive portal. Filament assets are served from `vendor/`.

**Livewire JS returns HTML instead of JS:**
Clear your browser cache (Cmd+Shift+R). This can happen if you visited the app while a 500 error was occurring — the browser cached the error response.
