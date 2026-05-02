FROM php:8.2-fpm-alpine

RUN apk add --no-cache \
    libpng-dev libjpeg-turbo-dev libwebp-dev \
    ffmpeg \
    zip unzip git \
    && docker-php-ext-configure gd --with-jpeg --with-webp \
    && docker-php-ext-install pdo_mysql gd exif bcmath

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
