# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php:8.3

# Install linux package dependencies and PHP extensions
RUN set -ex; \
  apt update && apt install -y libzip-dev && \
  docker-php-ext-configure zip && \
  docker-php-ext-install sockets zip pdo pdo_mysql && \
  docker-php-ext-enable sockets && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# Set environment variables
ENV LOG_FILE_PATH="/var/www/html/naad-socket.log"

# Copy the entrypoint script and set permissions
COPY ./entrypoint.sh /home/entrypoint.sh
RUN chmod +x /home/entrypoint.sh

# Copy application source code and composer config
COPY ./ /var/www/html/

# Create log file and set permissions
RUN touch $LOG_FILE_PATH && chown 1001:1001 $LOG_FILE_PATH

# Set working directory
WORKDIR /var/www/html/

# Copy Composer binary from the official image to our working directory
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Switch to non-root user
USER 1001

# Install PHP dependencies using Composer
RUN /usr/local/bin/composer install && \
/usr/local/bin/composer dump-autoload

# Set the entrypoint script for this image
ENTRYPOINT ["/home/entrypoint.sh"]
