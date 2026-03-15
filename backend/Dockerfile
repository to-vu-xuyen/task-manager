# Stage 1: Build dependencies
FROM composer:latest AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader

# Stage 2: Production image
FROM yiisoftware/yii2-php:8.1-apache
RUN sed -i -e 's|/app/web|/app/backend/web|g' /etc/apache2/sites-available/000-default.conf
WORKDIR /app

COPY --from=vendor /app/vendor/ /app/vendor/
COPY . .
RUN mkdir -p backend/runtime backend/web/assets && \
    chown -R www-data:www-data backend/runtime backend/web/assets

HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost:80/ || exit 1
