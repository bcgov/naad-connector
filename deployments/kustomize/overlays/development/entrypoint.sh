#!/bin/sh
echo 'Loading database secrets...' >&2
if [ -f /vault/secrets/naad ]; then
  . /vault/secrets/naad
  cat /vault/secrets/naad
  # Map Vault vars to MariaDB expected vars
  # export MARIADB_ROOT_PASSWORD="$VAULT_MARIADB_ROOT_PASSWORD"
  # export MARIADB_SERVICE_PORT="$VAULT_MARIADB_SERVICE_PORT"
  # export MARIADB_SERVICE_HOST="$VAULT_MARIADB_SERVICE_HOST"
else
  echo "Secrets file not found" >&2
  exit 1
fi

# Call the default entrypoint to handle initialization
exec /usr/local/bin/docker-entrypoint.sh mariadbd "$@"