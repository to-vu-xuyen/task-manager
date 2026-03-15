# Stage 1: Build dependencies
FROM composer:latest AS vendor
WORKDIR /app
COPY composer.json composer.lock* ./
RUN composer install --no-dev --no-scripts --prefer-dist --optimize-autoloader
# Stage 2: Production image
FROM yiisoftware/yii2-php:8.1-apache
# Change document root for Apache tới thư mục web của API
RUN sed -i -e 's|/app/web|/app/api/web|g' /etc/apache2/sites-available/000-default.conf
WORKDIR /app
# Copy thư viện từ builder stage (tránh vác theo cache sinh ra dung lượng file rác dư thừa)
COPY --from=vendor /app/vendor/ /app/vendor/
# Copy TOÀN BỘ APPLICATION CODE (Bao gồm common/, console/...)
COPY . .
# Chuyển quyền folder để ghi log Cache
RUN mkdir -p api/runtime api/web/assets && \
    chown -R www-data:www-data api/runtime api/web/assets
# Basic healthcheck for Apache
HEALTHCHECK --interval=30s --timeout=5s \
    CMD curl -f http://localhost:80/ || exit 1