#!/bin/bash

# Asegúrate de que se detengan en caso de error
set -e

# Instalar Faker si no está instalado (opcional, si usas Faker en tu proyecto)
echo "Instalando Faker..."
composer require fakerphp/faker --dev

# Instalar dependencias de Composer
echo "Instalando dependencias de Composer..."
composer install --no-dev --optimize-autoloader

# Ejecutar migraciones




# Cachear configuraciones
echo "Cacheando configuraciones..."
php artisan config:cache

# Cachear rutas
echo "Cacheando rutas..."
php artisan route:cache

# Cachear vistas
echo "Cacheando vistas..."
php artisan view:cache


# Asegurar que los permisos son correctos
echo "Configurando permisos..."
chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Ejecutar el servidor PHP-FPM (en un contenedor con Nginx o Apache, esto será manejado por el servicio web)
# No es necesario ejecutar 'php artisan serve' si estás utilizando Nginx o Apache, pero si lo necesitas, puedes descomentar la siguiente línea:
php artisan serve --host=0.0.0.0 --port=10000

# Iniciar el servidor PHP-FPM y Nginx si no está en ejecución
php-fpm &
nginx -g "daemon off;"
