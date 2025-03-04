#!/bin/sh
echo 'Loading naad-app secrets'
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

# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php