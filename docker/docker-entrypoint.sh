#!/bin/bash
set -e


if [ ! -f composer.json ]; then
  echo "📁 Creating Bedrock project..."
  composer create-project roots/bedrock . --no-interaction --prefer-dist
fi

composer update --no-interaction --prefer-dist

echo "📦 Installing PHP dependencies..."
composer install --no-interaction --prefer-dist

exec "$@"