FROM php:8.2-apache

# Install ekstensi PHP untuk koneksi MySQL (mysqli & pdo_mysql)
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Aktifkan mod_rewrite Apache (berguna jika memakai .htaccess)
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Salin seluruh file proyek ke dalam container
COPY . /var/www/html/

# Atur permission agar Apache bisa membaca/menulis (misal untuk folder upload)
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

EXPOSE 80
