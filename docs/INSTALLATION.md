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
git clone https://github.com/your-org/buku-tu-internet.git
cd buku-tu-internet
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
