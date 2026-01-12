FROM php:8.4

# Set working directory
WORKDIR /app

# Install dependencies and PHP extensions
RUN apt update \
    # install, accepting prompts and avoiding unnecessary packages
    && apt install -y --no-install-recommends \
    libzip-dev \
    && docker-php-ext-install sockets zip pdo pdo_mysql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy application source code (.dockerignore masks out all the unneeded files from the root directory)
COPY ./ ./

# Copy entrypoint and migration scripts into /app
COPY ./entrypoint.sh /app/entrypoint.sh
COPY ./run-migrations.sh /app/run-migrations.sh

# Create non-root user, set permissions, and prepare the environment
RUN chmod +x /app/entrypoint.sh /app/src/alert-cleanup.php /app/run-migrations.sh && \
    useradd -m -u 1001 appuser && \
    mkdir -p /app/vendor && \
    touch /app/heartbeat.log && \
    chown -R 1001:0 /app && \
    chmod -R g+rw /app/heartbeat.log

# Switch to non-root user
USER appuser

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache

# Set entrypoint script
ENTRYPOINT ["/app/entrypoint.sh"]
