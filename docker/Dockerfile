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

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp

RUN git config --global --add safe.directory /var/www/html

# Enable Apache rewrite (required for Bedrock)
RUN a2enmod rewrite

# Add custom Apache virtual host for Bedrock's /web root
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

WORKDIR /var/www/html

