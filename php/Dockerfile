FROM php:8.2-fpm-alpine

RUN docker-php-ext-install pdo pdo_mysql

RUN set -ex \
    && apk add --no-cache \
        freetype \
        libjpeg-turbo \
        libpng \
        freetype-dev \
        libjpeg-turbo-dev \
        libpng-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd \
    && apk del --no-cache freetype-dev libjpeg-turbo-dev libpng-dev

RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && sed -i 's/upload_max_filesize = 2M/upload_max_filesize = 100M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/post_max_size = 8M/post_max_size = 100M/' "$PHP_INI_DIR/php.ini" \
    && sed -i 's/max_execution_time = 30/max_execution_time = 300/' "$PHP_INI_DIR/php.ini"
