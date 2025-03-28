#!/bin/bash

max_retries=5
retry_delay=10
attempt=1

# Try to migrate up to max_retries times.
while [ $attempt -le $max_retries ]
do
    echo "Migrating database (Attempt: $attempt/$max_retries)..."
    /usr/local/bin/php /app/vendor/bin/doctrine-migrations migrate --no-interaction --allow-no-migration
    # On successful migration, exit with 0 code.
    if [ $? -eq 0 ]; then
        exit 0
    fi

    # Otherwise, try again.
    echo "Migration failed. Retrying in $retry_delay seconds..."
    sleep $retry_delay
    attempt=$((attempt + 1))
done

echo "Max retries reached. Migration failed."
exit 1
