#!/usr/bin/env bash
#
# deploy.sh — Actualiza una instalación existente de SIGAV con los últimos cambios.
# Ejecutar desde /var/www/sigav dentro de la VM:
#   sudo bash deploy/deploy.sh
#
set -euo pipefail

APP_DIR=/var/www/sigav
cd "${APP_DIR}"

echo "==> Poniendo la app en modo mantenimiento..."
sudo -u www-data php artisan down || true

echo "==> Trayendo los últimos cambios del repositorio..."
sudo -u www-data git pull origin main

echo "==> Instalando dependencias de producción..."
sudo -u www-data composer install --no-dev --optimize-autoloader
sudo -u www-data npm install
sudo -u www-data npm run prod

echo "==> Ejecutando migraciones de base de datos..."
sudo -u www-data php artisan migrate --force

echo "==> Refrescando cachés..."
sudo -u www-data php artisan config:cache
sudo -u www-data php artisan route:cache
sudo -u www-data php artisan view:cache

echo "==> Ajustando permisos..."
sudo chown -R www-data:www-data storage bootstrap/cache public
sudo chmod -R 775 storage bootstrap/cache

echo "==> Reiniciando PHP-FPM..."
sudo systemctl reload php7.4-fpm

echo "==> Saliendo de modo mantenimiento..."
sudo -u www-data php artisan up

echo "============================================================"
echo " Despliegue completado."
echo "============================================================"
