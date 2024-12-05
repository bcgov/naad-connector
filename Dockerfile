FROM php:8.3

# Install dependencies and PHP extensions
RUN apt update && apt install -y libzip-dev \
  && docker-php-ext-install sockets zip pdo pdo_mysql \
  && apt-get clean && rm -rf /var/lib/apt/lists/*

# Set environment variable
ENV LOG_FILE_PATH="/var/www/html/naad-socket.log"

# Copy application source code and config
COPY ./ /var/www/html/

# Create log file and set permissions
RUN touch $LOG_FILE_PATH && chown 1001:1001 $LOG_FILE_PATH

# Set working directory and install Composer
WORKDIR /var/www/html/
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Copy entrypoint script and set permissions as root
COPY ./entrypoint.sh /home/entrypoint.sh
RUN chmod +x /home/entrypoint.sh

# Switch to non-root user
USER 1001

# Install Composer dependencies
RUN composer install && composer dump-autoload

# Set entrypoint script
ENTRYPOINT ["/home/entrypoint.sh"]

