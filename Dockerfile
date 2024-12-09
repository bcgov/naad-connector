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
RUN chmod +x /app/entrypoint.sh

# Create non-root user and set permissions
RUN useradd -ms /bin/bash appuser \
    && mkdir /app/vendor \
    && touch /app/heartbeat.log \
    && chown 1001:1001 heartbeat.log \
    && chown -R appuser:appuser /app

# Switch to non-root user
USER appuser

# Install Composer dependencies
RUN composer install --no-dev --optimize-autoloader \
    && composer clear-cache

# Set entrypoint script
ENTRYPOINT ["/app/entrypoint.sh"]
