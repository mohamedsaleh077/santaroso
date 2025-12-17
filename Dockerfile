FROM 8ct8pus/apache-php-fpm-alpine:2.5.2

WORKDIR /sites/localhost/html/public

COPY docker/etc/ /docker/etc/

RUN chown -R apache:apache /sites/localhost || true

EXPOSE 80 443 8025
