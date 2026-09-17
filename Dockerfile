FROM php:8.2-apache

RUN apt-get update \
    && apt-get install -y --no-install-recommends libpq-dev libcurl4-openssl-dev libonig-dev \
    && docker-php-ext-install pdo pdo_pgsql curl mbstring \
    && apt-get clean && rm -rf /var/lib/apt/lists/* \
    && a2enmod rewrite

COPY . /var/www/html

ENV APACHE_DOCUMENT_ROOT=/var/www/html/backend
RUN sed -ri -e "s!/var/www/html!${APACHE_DOCUMENT_ROOT}!g" /etc/apache2/sites-available/*.conf

# Apache strips the Authorization header from PHP by default; the API's
# Bearer-token auth needs it forwarded. CGIPassAuth is only valid inside a
# directory-scoped context, not bare in apache2.conf.
#
# index.php is a front-controller router written for `php -S`, which passes
# every request through it automatically. Apache has no equivalent for a
# plain file request, so without FallbackResource it 404s on every /api/*
# path directly (index.php is never even invoked) instead of routing to it.
RUN { \
    echo "<Directory ${APACHE_DOCUMENT_ROOT}>"; \
    echo "    CGIPassAuth On"; \
    echo "    FallbackResource /index.php"; \
    echo "</Directory>"; \
    } >> /etc/apache2/apache2.conf

# Render assigns the listen port at runtime via $PORT (default 10000); Apache's
# config is baked at build time, so rebind it when the container actually starts.
CMD sh -c '\
    sed -ri "s/Listen 80/Listen ${PORT:-80}/" /etc/apache2/ports.conf && \
    sed -ri "s/:80>/:${PORT:-80}>/" /etc/apache2/sites-available/*.conf && \
    apache2-foreground'
