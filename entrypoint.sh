#!/bin/bash

# grab our secrets from the vault
echo 'Checking for vault secrets: '
cat /vault/secrets/naad


# display the current contents of /vault/secrets/naad again
echo 'Show current environment:'
printenv
echo '\n'
printenv | grep PASSWORD

# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php