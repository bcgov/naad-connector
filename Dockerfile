# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php:8.3
RUN set -ex; apt update && apt install -y libzip-dev;
RUN set -ex; \
  docker-php-ext-configure zip; \
  docker-php-ext-install sockets zip pdo pdo_mysql; \
  docker-php-ext-enable sockets;

ENV LOG_FILE_PATH="/var/www/html/naad-socket.log"

# Copy application code
COPY ./ /var/www/html/
COPY ./entrypoint.sh /home/


# Use Docker secrets (via BuildKit)
# RUN --mount=type=secret,id=destination_password \
#   destination_password=$(cat /run/secrets/destination_password) && \
#   echo "destination_password=$destination_password" > /var/www/html/.env

RUN touch $LOG_FILE_PATH && chown 1001 $LOG_FILE_PATH
RUN chmod +x /home/entrypoint.sh

WORKDIR /var/www/html/

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
USER 1001
RUN /usr/local/bin/composer install
RUN /usr/local/bin/composer dump-autoload

ENTRYPOINT ["/home/entrypoint.sh"]
