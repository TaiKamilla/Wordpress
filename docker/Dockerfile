FROM wordpress:6.8.3-php8.4-apache

# Install dependencies needed for Composer
RUN apt-get update && apt-get install -y \
  unzip \
  libzip-dev \
  git \
  nano \
  && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install zip

# Install WP-CLI
RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar \
  && chmod +x wp-cli.phar \
  && mv wp-cli.phar /usr/local/bin/wp

# Enable Apache rewrite
RUN a2enmod rewrite

# Rename the docker-entrypoint.sh to prevent WordPress from copying core files
# /usr/local/bin/docker-entrypoint.sh
RUN mv /usr/local/bin/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh.bak

# Copy custom entrypoint script
COPY docker/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Add custom Apache virtual host for Bedrock's /web root
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf

# Install Composer
COPY --from=composer:2.6.6 /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html