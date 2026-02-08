#!/bin/sh
set -e

# Ajustar Apache para escuchar en el puerto proporcionado por Cloud Run
PORT=${PORT:-8080}
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf || true

# Alinear el VirtualHost al puerto real (por defecto viene *:80)
sed -ri "s#<VirtualHost \\*:[0-9]+>#<VirtualHost *:${PORT}>#g" /etc/apache2/sites-available/*.conf || true

# Evitar warnings de ServerName
echo "ServerName localhost" > /etc/apache2/conf-enabled/servername.conf

# Cachear configuración y rutas si es posible (no fallar si falta APP_KEY)
php artisan config:cache || true
php artisan route:cache || true

# Ejecutar Apache en foreground (requerido por Cloud Run)
apache2-foreground
