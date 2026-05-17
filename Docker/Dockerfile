#1. Base de notre application
FROM php:8.2-apache

COPY apache.conf /etc/apache2/conf-available/custom.conf

#2. Installation des extensions php nécessaires pour se connecter à la base de données MySQL. Les extensions mysqli, pdo et pdo_mysql sont installées et activées. 
RUN docker-php-ext-install mysqli pdo pdo_mysql

#3 Activation mod_rewrite pour permettre la réécriture d'URL dans Apache, ce qui est souvent nécessaire pour les applications web modernes.
RUN a2enmod rewrite
RUN a2enconf custom

#4. je dois exposer le port 80 pour que le serveur web puisse être accessible depuis l'extérieur du conteneur.
EXPOSE 80