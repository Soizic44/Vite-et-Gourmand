# 1. Image de base de notre application
FROM php:8.2-apache

# 2. Installation des dépendances système
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    zip \
    curl \
    wget \
    gnupg \
    libzip-dev

# 3. Installation des extensions php nécessaires pour se connecter à la base de données MySQL. Les extensions mysqli, pdo et pdo_mysql sont instal-lées et activées.
RUN docker-php-ext-install pdo pdo_mysql mysqli zip

# 4. Activation Apache : Activation mod_rewrite pour permettre la réécriture d'URL dans Apache.
RUN a2enmod rewrite

# 5. Configuration Apache
COPY apache.conf /etc/apache2/conf-available/custom.conf
COPY vhost.conf /etc/apache2/sites-available/000-default.conf

RUN a2enconf custom

# 6. Installation Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# 7. Installation Symfony CLI
RUN curl -sS https://get.symfony.com/cli/installer | bash

RUN mv /root/.symfony*/bin/symfony /usr/local/bin/symfony

# 8. Dossier de travail
WORKDIR /var/www/html

# 9. Permissions
RUN chown -R www-data:www-data /var/www/html

# 10. Port Apache : exposition du port 80 pour que le serveur web puisse être ac-cessible depuis l'extérieur du conteneur.
EXPOSE 80