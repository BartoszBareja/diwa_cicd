FROM php:7-apache

COPY ./app /var/www/html
COPY ./docs /var/www/docs
COPY ./database /var/www/database

RUN apt-get update && \
    apt-get install -y --only-upgrade apache2 && \
    chown www-data:www-data /var/www -R && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

