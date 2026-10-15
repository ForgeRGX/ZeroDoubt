FROM php:8.3-apache

RUN docker-php-ext-install pdo_mysql mysqli \
 && a2enmod headers \
 && a2dissite 000-default \
 && cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"

COPY docker/vhost.conf /etc/apache2/sites-available/app.conf
RUN a2ensite app

ARG APP_VERSION=dev
ARG APP_COMMIT=unknown

ENV APP_VERSION=${APP_VERSION}
ENV APP_COMMIT=${APP_COMMIT}

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
