# Imagen sencilla para Laravel en Cloud Run usando Apache + PHP (mod_php)
FROM php:8.3-apache

# Dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
 && docker-php-ext-install zip pdo pdo_mysql \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Instalar Composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Configurar DocumentRoot a public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!Directory /var/www/!Directory ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Instalar dependencias PHP (sin dev) y preparar permisos
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

# Script de arranque que ajusta el puerto y cachea configuración
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Cloud Run inyecta PORT; exponemos por compatibilidad
ENV PORT=8080

# Comando de arranque: ajusta Listen al PORT y lanza Apache en foreground
CMD ["/start.sh"]
