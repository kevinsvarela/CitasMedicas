FROM php:8.5-apache
WORKDIR /var/www/html

RUN a2enmod rewrite
RUN a2enmod actions
RUN docker-php-ext-install pdo pdo_mysql mysqli

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" && \
php -r "if (hash_file('sha384', 'composer-setup.php') === 'c8b085408188070d5f52bcfe4ecfbee5f727afa458b2573b8eaaf77b3419b0bf2768dc67c86944da1544f06fa544fd47') { echo 'Installer verified'.PHP_EOL; } else { echo 'Installer corrupt'.PHP_EOL; unlink('composer-setup.php'); exit(1); }" && \
php composer-setup.php && \
php -r "unlink('composer-setup.php');" && \
mv composer.phar /usr/local/bin/composer

COPY ./Data/composer.json .
COPY ./Data/.env .

RUN apt update && apt install -y zip
RUN apt update && apt install -y git

RUN composer install

RUN composer dump-autoload


RUN sed -i 's/DocumentRoot.*$/DocumentRoot \/var\/www\/html\/public/' /etc/apache2/sites-enabled/000-default.conf
