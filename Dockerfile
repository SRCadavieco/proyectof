# Imagen sencilla para Laravel en Cloud Run usando Apache + PHP (mod_php)
FROM php:8.4-apache

# Dependencias del sistema y extensiones PHP necesarias
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    libpng-dev \
    libjpeg-dev \
    libwebp-dev \
    curl \
 && docker-php-ext-configure gd --with-jpeg --with-webp \
 && docker-php-ext-install zip pdo pdo_mysql gd \
 && a2enmod rewrite \
 && rm -rf /var/lib/apt/lists/*

# Instalar Node.js 20.x
RUN curl -fsSL https://deb.nodesource.com/setup_20.x | bash - \
    && apt-get install -y nodejs \
    && rm -rf /var/lib/apt/lists/*

# Instalar Composer desde imagen oficial
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copiar archivos del proyecto
COPY . .

# Instalar dependencias de Node.js y compilar assets (Tailwind/Vite)
RUN npm install && npm run build

# Verificar que los assets se compilaron correctamente
RUN ls -la public/build/ && \
    test -f public/build/manifest.json || (echo "ERROR: manifest.json no generado" && exit 1)

# Configurar DocumentRoot a public/
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf \
    && sed -ri -e 's!Directory /var/www/!Directory ${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf

# Permitir .htaccess en public/ y establecer DirectoryIndex
RUN printf "%s\n" "<Directory ${APACHE_DOCUMENT_ROOT}>" \
    "    AllowOverride All" \
    "    Require all granted" \
    "</Directory>" > /etc/apache2/conf-enabled/laravel.conf \
    && echo "DirectoryIndex index.php index.html" > /etc/apache2/conf-enabled/dirindex.conf

# Instalar dependencias PHP (sin dev) y preparar permisos
RUN composer install --no-interaction --no-dev --prefer-dist --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache

# Crear directorios de cache/sesiones/vistas y logs para Laravel
RUN mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs \
    && chown -R www-data:www-data storage

# Script de arranque que ajusta el puerto y cachea configuración
COPY docker/start.sh /start.sh
RUN chmod +x /start.sh

# Cloud Run inyecta PORT; exponemos por compatibilidad
ENV PORT=8080

# Comando de arranque: ajusta Listen al PORT y lanza Apache en foreground
CMD ["/start.sh"]
