FROM richarvey/nginx-php-fpm:1.6.0

RUN apk add --no-cache --virtual .build-deps \
    g++ make autoconf yaml-dev \
    && pecl channel-update pecl.php.net \
    && pecl install apcu \
    && docker-php-ext-enable apcu

COPY . /var/www/html
WORKDIR /var/www/html

ENV SKIP_CHOWN=true
ENV RUN_SCRIPTS=1
ENV SKIP_COMPOSER=true

# Install Composer
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer

RUN cd /var/www/html \
    && chmod a+w -R var \
    && composer install --no-interaction --no-scripts -n \
    && composer clearcache -n

COPY external-conf/api.conf /etc/nginx/sites-enabled/api.conf

RUN echo "default_socket_timeout=3600" >> /usr/local/etc/php/conf.d/100-php-custom.ini
