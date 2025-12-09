#!/bin/bash
set -e

# If no composer.json → create Bedrock in temp and sync only missing files
if [ ! -f composer.json ]; then
  echo "📁 Creating Bedrock project in temp folder..."
  TMP_DIR="/tmp/bedrock-setup"
  rm -rf "$TMP_DIR"
  mkdir -p "$TMP_DIR"

  composer create-project roots/bedrock "$TMP_DIR" --no-interaction

  echo "📁 Syncing Bedrock files (without overwriting binds)..."
  rsync -a --ignore-existing "$TMP_DIR"/ ./ 
fi

echo "🔧 Installing and updating dependencies..."
composer install --no-interaction --prefer-dist
composer update --no-interaction

exec "$@"