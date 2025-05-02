FROM php:8.2-apache

# Instala extensões necessárias para CodeIgniter + MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

RUN apt-get update && apt-get install -y \
    libicu-dev \
    && docker-php-ext-install intl

# Instala o Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Ativa o mod_rewrite do Apache
RUN a2enmod rewrite

# Copia configuração customizada do Apache 
COPY ./apache/000-default.conf /etc/apache2/sites-available/000-default.conf

# Define o diretório padrão
WORKDIR /var/www/html
