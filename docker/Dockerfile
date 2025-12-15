# Define build arguments for WordPress and PHP versions
ARG WORDPRESS_VERSION
ARG PHP_VERSION

# Use versioned tag if WORDPRESS_VERSION is set, otherwise use 'apache' tag
ARG TAG=${WORDPRESS_VERSION:+${WORDPRESS_VERSION}-php${PHP_VERSION}-}apache

FROM wordpress:${TAG}

# Install dependencies needed for Composer
RUN apt-get update && apt-get install -y \
  unzip \
  libzip-dev \
  git \
  nano \
  && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install zip

# Additional PHP configuration
RUN echo "memory_limit=512M" >> /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize=64M" >> /usr/local/etc/php/conf.d/upload-max-filesize.ini \
    && echo "post_max_size=64M" >> /usr/local/etc/php/conf.d/post-max-size.ini \
    && echo "max_execution_time=300" >> /usr/local/etc/php/conf.d/max-execution-time.ini

# Install Xdebug (version depends on PHP version)
# PHP 7.4: Xdebug 3.1.x
# PHP 8.0-8.2: Xdebug 3.2.x
# PHP 8.3+: Xdebug 3.3.x
RUN PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;") && \
    if [ "$PHP_VERSION" = "7.4" ]; then \
        pecl install xdebug-3.1.6; \
    elif [ "$PHP_VERSION" = "8.3" ]; then \
        pecl install xdebug-3.3.2; \
    else \
        pecl install xdebug-3.2.2; \
    fi && \
    docker-php-ext-enable xdebug

# Configure Xdebug
RUN echo "xdebug.mode=debug,develop,coverage" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.start_with_request=trigger" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
    && echo "xdebug.discover_client_host=1" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp

RUN git config --global --add safe.directory /var/www/html

# Enable Apache rewrite (required for Bedrock)
RUN a2enmod rewrite


# Rename the docker-entrypoint.sh to prevent WordPress from copying core files
# /usr/local/bin/docker-entrypoint.sh
RUN mv /usr/local/bin/docker-entrypoint.sh /usr/local/bin/wordpress-docker-entrypoint.sh

# Copy custom entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Copy custom development script
COPY docker/dev-setup.sh /usr/local/bin/dev-setup.sh
RUN chmod +x /usr/local/bin/dev-setup.sh

# Add custom Apache virtual host for Bedrock's /web root
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html