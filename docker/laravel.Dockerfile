# AIStory Laravel 11 — PHP 8.2 + Nginx Production Image
FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    nginx supervisor \
    libpng-dev libjpeg-turbo-dev libwebp-dev freetype-dev \
    libzip-dev icu-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql bcmath fileinfo mbstring exif pcntl intl zip gd opcache \
    && pecl install redis \
    && docker-php-ext-enable redis

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY laravel/composer.json laravel/composer.lock ./
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

COPY laravel/ .
RUN php artisan storage:link || true \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/nginx-laravel.conf /etc/nginx/http.d/default.conf
COPY docker/supervisor.conf /etc/supervisor.d/laravel.ini

RUN echo "memory_limit=256M" > /usr/local/etc/php/conf.d/99-aistory.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/99-aistory.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/99-aistory.ini

EXPOSE 80
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]
