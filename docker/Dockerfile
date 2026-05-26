# Versions baked in as build-arg defaults. Override at build time with
#   --build-arg WORDPRESS_VERSION=X --build-arg PHP_VERSION=Y
# See https://hub.docker.com/_/wordpress for valid combinations.
ARG WORDPRESS_VERSION=6.8.3
ARG PHP_VERSION=8.3

FROM wordpress:${WORDPRESS_VERSION}-php${PHP_VERSION}-apache

# Install system dependencies.
#   - libssl-dev: required by phpredis to negotiate TLS with Azure Cache for
#     Redis (6380/SSL only).
#   - rsync: required by the wp-content persistence overlay
#     (docker/entrypoint-persist.sh). Polls bidirectional sync between
#     /var/www/html/wp-content and /persist (Azure Files) every 30 s so
#     admin-installed plugins/themes survive container restarts and
#     Storage Explorer uploads land in the running site.
#   - apache2-utils: needed for htpasswd (HTTP Basic Auth on staging).
RUN apt-get update && apt-get install -y \
  unzip \
  libzip-dev \
  libssl-dev \
  git \
  nano \
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

# Backdate image-baseline mtimes to 1970-01-02 so the persistence overlay's
# bidirectional rsync (`-au`, see docker/entrypoint-persist.sh) always prefers
# anything on the /persist share — every real edit (admin update, Storage
# Explorer drop, file edit) carries a real mtime which is by definition newer.
# Without this, `:latest` image rebuilds (mtime ≈ build time, always "newer"
# than older admin updates on the share) silently clobber admin work via the
# wp-content → /persist leg of the poller.
# `uploads/` is the symlink target — exclude from the touch (it's pruned).
RUN find /var/www/html/wp-content \
      -path /var/www/html/wp-content/uploads -prune -o \
      -exec touch -t 197001020000 {} +

# Enable Apache rewrite
RUN a2enmod rewrite

# Add custom Apache virtual host configuration (includes HTTP Basic Auth)
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# HTTP Basic Auth credentials (OPTIONAL).
# - Staging: pass --build-arg HTPASSWD_PASSWORD="$PUBLIC_ACCESS_PWD" AND set
#   JTI_BASIC_AUTH=true on the App Service so the entrypoint engages auth
#   (`apache2-foreground -DBASIC_AUTH`). Both are needed.
# - Production: omit the build-arg. No .htpasswd is baked. Even if someone
#   sets JTI_BASIC_AUTH=true at runtime, Apache fails to load (no htpasswd
#   file) — fail-closed against misconfiguration.
ARG HTPASSWD_USER=jti
ARG HTPASSWD_PASSWORD=
RUN if [ -n "$HTPASSWD_PASSWORD" ]; then \
      htpasswd -bBc /etc/apache2/.htpasswd "$HTPASSWD_USER" "$HTPASSWD_PASSWORD" && \
      chmod 644 /etc/apache2/.htpasswd && \
      echo "baked .htpasswd for user '$HTPASSWD_USER'"; \
    else \
      echo "no HTPASSWD_PASSWORD provided — image built without Basic Auth credentials"; \
    fi

# Enable the auth modules (mod_auth_basic + mod_authn_file are usually
# enabled by default on debian:apache, but make it explicit).
RUN a2enmod auth_basic authn_file

# Production PHP runtime settings baked into the image.
RUN echo "memory_limit=512M" > /usr/local/etc/php/conf.d/memory_limit.ini

# Production OPcache settings. The base wordpress:apache image already ships
# the opcache extension; this overrides the default tuning for our setup
# (plugins baked in image → validate_timestamps=0 is safe, big win for TTFB).
COPY docker/php-opcache.ini /usr/local/etc/php/conf.d/zz-opcache-prod.ini

# wp-content persistence overlay (single script):
#   - Mount: AzFiles share `wp-content` at /persist (share root mirrors wp-content)
#   - Boot: symlink wp-content/uploads → /persist/uploads; rsync /persist → wp-content
#     (-au, no --delete; image-only files survive)
#   - Runtime: backgrounded 30s poller, bidirectional rsync wp-content ↔ /persist
#     (excludes uploads only; everything else syncs both ways)
#   - Then exec the upstream WP docker-entrypoint.sh
COPY docker/entrypoint-persist.sh /usr/local/bin/entrypoint-persist.sh
RUN chmod +x /usr/local/bin/entrypoint-persist.sh

ENTRYPOINT ["/usr/local/bin/entrypoint-persist.sh"]
CMD ["apache2-foreground"]

WORKDIR /var/www/html

