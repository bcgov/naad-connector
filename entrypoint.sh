#!/bin/bash

# Print the current user
echo "Running as user: $(whoami) UID: $(id -u) GID: $(id -g)"
# All environment variables will be extracted from the .env inside start.php using getenv().
/usr/local/bin/php /app/src/start.php