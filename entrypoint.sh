#!/bin/bash

# grab our secrets from the vault
source /vault/secrets/naad

# display the current contents of /vault/secrets/naad before updating
echo 'Sourcing /vault/secrets/naad'
cat /vault/secrets/naad

# display the current contents of /vault/secrets/naad again
echo 'AFTER Sourcing /vault/secrets/naad'
printenv

# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php