#!/bin/bash
# Asegúrate de que se detengan en caso de error
set -e

# Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# Borrar cache de configuraciones
echo "Borrando cache de configuraciones..."
php artisan config:cache

# Borrar cache de rua
echo "Borrando cache de rutas..."
php artisan route:cache

# Listar rutas para verificar que todo está en orden
echo "Listando rutas..."
php artisan route:list
chmod -R 775 /var/www/html/database
php artisan serve --host=0.0.0.0 --port=10000