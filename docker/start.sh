#!/bin/sh
set -e

# Ajustar Apache para escuchar en el puerto proporcionado por Cloud Run
PORT=${PORT:-8080}
sed -ri "s/^Listen .*/Listen ${PORT}/" /etc/apache2/ports.conf || true

# Cachear configuración y rutas si es posible (no fallar si falta APP_KEY)
php artisan config:cache || true
php artisan route:cache || true

# Ejecutar Apache en foreground (requerido por Cloud Run)
apache2-foreground
