# All environment variables will be extracted from the .env inside start.php using getenv().

#!/bin/sh
echo "Loading vault secrets for naad-app"
while [ ! -f /vault/secrets/naad ]; do
  echo "Waiting for /vault/secrets/naad to be available..."
  sleep 2
done
echo "Vault secrets are available."
. /vault/secrets/naad && /usr/local/bin/php /app/src/start.php