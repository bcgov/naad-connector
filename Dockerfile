FROM php:8.3

# Set working directory
WORKDIR /app

# Install dependencies and PHP extensions
RUN apt update && apt install -y libzip-dev \
  && docker-php-ext-install sockets zip pdo pdo_mysql \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Copy application source code and config
COPY ./ ./

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy entrypoint script into /app and set permissions
COPY ./entrypoint.sh ./entrypoint.sh
RUN chmod +x ./entrypoint.sh

# Create log file and set permissions
RUN touch heartbeat.log && chown 1001:1001 heartbeat.log

# Switch to non-root user
USER 1001

# Install Composer dependencies
RUN composer install && composer dump-autoload

# Set entrypoint script
ENTRYPOINT ["./entrypoint.sh"]
