FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql curl mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

COPY . /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend/api
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

# Render assigns the listen port at runtime via $PORT (default 10000); Apache's
# config is baked at build time, so rebind it when the container actually starts.
CMD sh -c '\
    sed -ri "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && \
    sed -ri "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/*.conf && \
    apache2-foreground'
