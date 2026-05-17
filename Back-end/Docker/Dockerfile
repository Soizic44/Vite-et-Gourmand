#1. Base de notre application
FROM php:8.2-apache

#2. Definition du dossier de travail
# Creation du ou des dossiers + deplacement
# mkdir -p /var/www/html | cd
WORKDIR /var/www/html

#3. je copie mon projet dans le dossier du workdir
COPY . .

#4. Installation des extensions php nécessaires pour se connecter à la base de données MySQL. Les extensions mysqli, pdo et pdo_mysql sont installées et activées. 
    
RUN docker-php-ext-install mysqli pdo pdo_mysql && docker-php-ext-enable mysqli pdo pdo_mysql

# Activation mod_rewrite pour permettre la réécriture d'URL dans Apache, ce qui est souvent nécessaire pour les applications web modernes.
RUN a2enmod rewrite

#5. je dois exposer le port 80 pour que le serveur web puisse être accessible depuis l'extérieur du conteneur.
EXPOSE 80