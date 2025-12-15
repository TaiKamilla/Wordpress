#!/bin/bash
set -e

WP_VERSION="${WORDPRESS_VERSION:-}"

# If no composer.json → create Bedrock in temp and sync only missing files
if [ ! -f composer.json ]; then
  echo "📁 Creating Bedrock project in temp folder..."
  TMP_DIR="/tmp/bedrock-setup"
  rm -rf "$TMP_DIR"
  mkdir -p "$TMP_DIR"

  composer create-project roots/bedrock "$TMP_DIR" --no-interaction --no-install

  if [ -n "$WP_VERSION" ]; then
    echo "🧩 Setting WordPress version to $WP_VERSION..."
    composer --working-dir="$TMP_DIR" require roots/wordpress-core:"$WP_VERSION" --no-interaction --no-update
  fi

  echo "📁 Syncing Bedrock files (without overwriting binds)..."
  rsync -a --ignore-existing "$TMP_DIR"/ ./
fi

echo "🔧 Installing dependencies..."
composer install --no-interaction --prefer-dist

exec "$@"
