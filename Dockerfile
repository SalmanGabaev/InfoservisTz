FROM php:8-fpm-alpine
COPY ./ /var/www
WORKDIR /var/www
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
