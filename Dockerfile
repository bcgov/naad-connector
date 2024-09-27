# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php:8.3
RUN set -ex; apt update && apt install -y libzip-dev;
RUN set -ex; \
  docker-php-ext-configure zip; \
  docker-php-ext-install sockets zip pdo pdo_mysql; \
  docker-php-ext-enable sockets;

ENV NAAD_NAME=NAAD-1
ENV NAAD_URL=streaming1.naad-adna.pelmorex.com
ENV DESTINATION_URL=https://localhost/wp-json/naad/v1/alert
ENV DESTINATION_USER=naadbot
ENV DESTINATION_PASSWORD="AAAA AAAA AAAA AAAA"
ENV LOG_FILE_PATH="/var/www/html/naad-socket.log"
ENV DATABASE_NAME="naad_connector"

COPY ./ /var/www/html/
COPY ./entrypoint.sh /home/
RUN touch $LOG_FILE_PATH && chown 1001 $LOG_FILE_PATH
RUN chmod +x /home/entrypoint.sh

WORKDIR /var/www/html/

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
USER 1001
RUN /usr/local/bin/composer install
RUN /usr/local/bin/composer dump-autoload

ENTRYPOINT ["/home/entrypoint.sh"]
