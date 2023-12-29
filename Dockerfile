FROM php

RUN set -ex; \
        docker-php-ext-install sockets; \
        docker-php-ext-enable sockets;

COPY ./src /var/www/html/
COPY ./entrypoint.sh /home/
RUN chmod +x /home/entrypoint.sh

ENV NAAD_NAME=NAAD-1
ENV NAAD_URL=streaming1.naad-adna.pelmorex.com

ENTRYPOINT ["/home/entrypoint.sh"]
