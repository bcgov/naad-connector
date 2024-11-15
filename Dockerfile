# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php:8.3

# install linux package deps and clean up
RUN set -ex; \
  apt update && \
  apt install -y libzip-dev && \
  docker-php-ext-configure zip && \
  docker-php-ext-install sockets zip pdo pdo_mysql && \
  docker-php-ext-enable sockets && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

ENV LOG_FILE_PATH="/var/www/html/naad-socket.log"

# Copy entrypoint script and set permissions
COPY ./entrypoint.sh /home/entrypoint.sh
RUN chmod +x /home/entrypoint.sh

# Copy application source code (and composer config)
COPY ./ /var/www/html/

# Use Docker secret extracted from your .env file.
RUN --mount=type=secret,id=destination_password \
  destination_password=$(cat /run/secrets/destination_password) && \
  echo "destination_password=$destination_password" > /var/www/html/.env

# Create log file and set ownership for non-root group and user
RUN touch $LOG_FILE_PATH && chown 1001:1001 $LOG_FILE_PATH

# Set the working directory
WORKDIR /var/www/html/

# Copy the composer executable from the composer:latest image into the new Docker image being built
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Switch to non-root user
USER 1001

# Install PHP dependencies using Composer
RUN /usr/local/bin/composer install && \
/usr/local/bin/composer dump-autoload

# Set the entrypoint
ENTRYPOINT ["/home/entrypoint.sh"]
