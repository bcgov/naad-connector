#!/bin/bash
set -e
set -x
if [-f /vault/secrets/naad]; then
    source /vault/secrets/naad
fi
# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php