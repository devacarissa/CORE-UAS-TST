# Gunakan PHP resmi dengan Apache
FROM php:8.2-apache

# Instal ekstensi yang dibutuhkan CodeIgniter (intl, sqlite, dll)
RUN apt-get update && apt-get install -y \
    libicu-dev \
    libsqlite3-dev \
    && docker-php-ext-install intl

# Aktifkan mod_rewrite untuk URL CodeIgniter
RUN a2enmod rewrite

# Set working directory ke folder web server
WORKDIR /var/www/html

# Copy semua file project kita ke dalam kontainer
COPY . .

# Beri izin akses ke folder writable agar database bisa ditulis
RUN chown -R www-data:www-data /var/www/html/writable

# Set document root ke folder /public sesuai standar CI4
ENV APACHE_DOCUMENT_ROOT /var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf

# Buka port 80
EXPOSE 80