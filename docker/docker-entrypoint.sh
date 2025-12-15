#!/bin/bash
set -e

echo "HERE"
echo "${DEV_ENV:-}"
echo "HERE"

if [ "${DEV_ENV:-}" = "true" ]; then
  echo "Running development setup..."
  su -s /bin/bash www-data -c "/usr/local/bin/dev-setup.sh"
fi

exec /usr/local/bin/wordpress-docker-entrypoint.sh "$@"
