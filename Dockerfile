# Don't change the FROM, updated by the OpenShift BuildConfig.
FROM php

RUN set -ex; \
        docker-php-ext-install sockets; \
        docker-php-ext-enable sockets;

COPY ./ /var/www/html/
COPY ./entrypoint.sh /home/
RUN chmod +x /home/entrypoint.sh

WORKDIR /var/www/html/

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
RUN /usr/local/bin/composer install
RUN /usr/local/bin/composer dump-autoload

ENV NAAD_NAME=NAAD-1
ENV NAAD_URL=streaming1.naad-adna.pelmorex.com
ENV DESTINATION_URL=https://localhost/eibc/wp-json/naad/v1/alert
ENV DESTINATION_USER=naadbot
ENV DESTINATION_PASSWORD='AAAA AAAA AAAA AAAA'

ENTRYPOINT ["/home/entrypoint.sh"]
