FROM php:8.3

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

# Copy entrypoint script into /app and set permissions
COPY ./entrypoint.sh /app/entrypoint.sh
RUN chmod +x /app/entrypoint.sh /app/src/alert-cleanup.php

# Create non-root user, set permissions, and prepare the environment
RUN useradd -ms /bin/bash appuser \
    && mkdir /app/vendor \
    && chown appuser:appuser /app/heartbeat.log \
    && touch /app/heartbeat.log \
    && chmod 664 /app/heartbeat.log \
    && chown -R appuser:appuser /app

# Switch to non-root user
USER appuser

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache

# Set entrypoint script
ENTRYPOINT ["/app/entrypoint.sh"]
