# Versions baked in as build-arg defaults. Override at build time with
#   --build-arg WORDPRESS_VERSION=X --build-arg PHP_VERSION=Y
# See https://hub.docker.com/_/wordpress for valid combinations.
ARG WORDPRESS_VERSION=6.8.3
ARG PHP_VERSION=8.3

FROM wordpress:${WORDPRESS_VERSION}-php${PHP_VERSION}-apache

# Install system dependencies. libssl-dev is needed by phpredis to negotiate
# TLS with Azure Cache for Redis (6380/SSL only).
RUN apt-get update && apt-get install -y \
  unzip \
  libzip-dev \
  libssl-dev \
  git \
  nano \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

# phpredis (Redis client extension). Phase 2.1 — required by the
# wordpress/wp-content/object-cache.php drop-in. v6.x line supports PHP 8.3.
RUN pecl install redis \
  && docker-php-ext-enable redis

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp

RUN git config --global --add safe.directory /var/www/html

# Copy the current wordpress folder files to the container.
# Note: includes the object-cache.php drop-in (Phase 2.1) when present.
COPY wordpress/ /var/www/html/

# Enable Apache rewrite
RUN a2enmod rewrite

# Add custom Apache virtual host configuration
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Production OPcache settings. The base wordpress:apache image already ships
# the opcache extension; this overrides the default tuning for our setup
# (plugins baked in image → validate_timestamps=0 is safe, big win for TTFB).
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini

WORKDIR /var/www/html

