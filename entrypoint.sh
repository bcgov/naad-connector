#!/bin/bash

# display the current env
printenv

# display the current contents of /vault/secrets/naad
cat /vault/secrets/naad

source /vault/secrets/naad


# display the current contents of /vault/secrets/naad again
echo 'AFTER Sourcing /vault/secrets/naad'

cat /vault/secrets/naad



# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php