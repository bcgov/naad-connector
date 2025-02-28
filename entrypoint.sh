#!/bin/bash

# display the current contents of /vault/secrets/naad again
echo 'AFTER Sourcing /vault/secrets/naad'
printenv

# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php