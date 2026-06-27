# Multi-stage build for production
FROM php:8.3-fpm-alpine AS base

RUN apk add --no-cache \
    postgresql-dev \
    mysql-client \
    libzip-dev \
    unzip \
    git \
    curl \
    oniguruma-dev \
    nginx \
    supervisor \
    && docker-php-ext-install \
    pdo_mysql \
    mbstring \
    zip \
    bcmath

# Install Redis extension
RUN pecl install redis && docker-php-ext-enable redis

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first for better caching
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-autoloader --no-scripts

# Copy application
COPY . .

# Generate autoloader and optimize
RUN composer dump-autoload --optimize

# Remove development files
RUN rm -rf docker-compose.yml Dockerfile .dockerignore \
    && rm -rf tests/ node_modules/ resources/js/ resources/css/ \
    && rm -rf .env.example .git/ .github/ .gitattributes

# Copy nginx configuration
COPY docker/nginx/default.conf /etc/nginx/http.d/default.conf

# Copy supervisord configuration
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Create storage directories
RUN mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
