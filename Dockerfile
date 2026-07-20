FROM php:8.2-apache

RUN apt-get update && apt-get install -y default-mysql-client libpng-dev libjpeg-dev && \
    apt-get clean && rm -rf /var/lib/apt/lists/*

RUN a2enmod rewrite headers

RUN docker-php-ext-configure gd --with-jpeg && docker-php-ext-install pdo pdo_mysql mysqli gd

COPY . /var/www/html/

RUN mkdir -p /var/www/html/tmp /var/www/html/backups /var/www/html/foto_perfil /var/www/html/adjuntos_justificaciones && \
    chown -R www-data:www-data /var/www/html/tmp && \
    chown -R www-data:www-data /var/www/html/foto_perfil && \
    chown -R www-data:www-data /var/www/html/adjuntos_justificaciones && \
    chown -R www-data:www-data /var/www/html/backups

COPY docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["apache2-foreground"]
