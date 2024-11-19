# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php:8.3

# Install Linux package dependencies and PHP extensions
RUN set -ex; \
  apt update && \
  apt install -y --no-install-recommends libzip-dev && \
  docker-php-ext-configure zip && \
  docker-php-ext-install sockets zip pdo pdo_mysql && \
  docker-php-ext-enable sockets && \
  apt-get clean && \
  rm -rf /var/lib/apt/lists/*

# Set environment variables
ENV NAAD_NAME=NAAD-1 \
    NAAD_URL=streaming1.naad-adna.pelmorex.com \
    DESTINATION_URL=https://localhost/wp-json/naad/v1/alert \
    DESTINATION_USER=naadbot \
    LOG_FILE_PATH="/var/www/html/naad-socket.log"

# Add entrypoint script
COPY ./entrypoint.sh /home/entrypoint.sh
RUN chmod +x /home/entrypoint.sh

# Copy application source code (and composer config)
COPY ./ /var/www/html/

# Set up secrets for runtime
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
