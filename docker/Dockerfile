# Versions baked in as build-arg defaults. Override at build time with
#   --build-arg WORDPRESS_VERSION=X --build-arg PHP_VERSION=Y
# See https://hub.docker.com/_/wordpress for valid combinations.
ARG WORDPRESS_VERSION=6.8.3
ARG PHP_VERSION=8.3

FROM wordpress:${WORDPRESS_VERSION}-php${PHP_VERSION}-apache

# Install system dependencies.
#   - libssl-dev: required by phpredis to negotiate TLS with Azure Cache for
#     Redis (6380/SSL only).
#   - inotify-tools + rsync: required by the wp-content persistence overlay
#     (docker/persist-watcher.sh). Watches /var/www/html/wp-content and rsyncs
#     changes to /persist (Azure Files) so admin-installed plugins/themes
#     survive container restarts.
RUN apt-get update && apt-get install -y \
  unzip \
  libzip-dev \
  libssl-dev \
  git \
  nano \
  inotify-tools \
  rsync \
  apache2-utils \
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

# Add custom Apache virtual host configuration (includes HTTP Basic Auth)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# HTTP Basic Auth for staging — username + password come in via build args.
# Password is stored as a GitHub Actions ENVIRONMENT secret named
# `PUBLIC_ACCESS_PWD` under the `staging` environment in
# ReliefApplications/jti-wordpress. CI injects it as --build-arg.
# Locally:  az acr build ... --build-arg HTPASSWD_PASSWORD="$pw" .
ARG HTPASSWD_USER=jti
ARG HTPASSWD_PASSWORD
RUN if [ -z "$HTPASSWD_PASSWORD" ]; then \
      echo "ERROR: build requires --build-arg HTPASSWD_PASSWORD=... (see GitHub secret STAGING_HTPASSWD_PASSWORD)" >&2; \
      exit 1; \
    fi && \
    htpasswd -bBc /etc/apache2/.htpasswd "$HTPASSWD_USER" "$HTPASSWD_PASSWORD" && \
    chmod 644 /etc/apache2/.htpasswd

# Enable the auth modules (mod_auth_basic + mod_authn_file are usually
# enabled by default on debian:apache, but make it explicit).
RUN a2enmod auth_basic authn_file

# Production OPcache settings. The base wordpress:apache image already ships
# the opcache extension; this overrides the default tuning for our setup
# (plugins baked in image → validate_timestamps=0 is safe, big win for TTFB).
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini

# wp-content persistence overlay (single script):
#   - Mounts: AzFiles share `wp-content` at /persist (share root mirrors wp-content)
#   - Boot: symlink /wp-content/uploads → /persist/uploads (direct write); seed
#     /persist on first boot, or restore /persist → /wp-content otherwise
#   - Runtime: backgrounded inotify watcher debounce-rsyncs /wp-content → /persist
#     (excludes uploads + caches + logs)
#   - Then exec the upstream WP docker-entrypoint.sh
COPY docker/entrypoint-persist.sh /usr/local/bin/entrypoint-persist.sh
RUN chmod +x /usr/local/bin/entrypoint-persist.sh

ENTRYPOINT ["/usr/local/bin/entrypoint-persist.sh"]
CMD ["apache2-foreground"]

WORKDIR /var/www/html

