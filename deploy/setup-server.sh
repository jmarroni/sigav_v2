#!/usr/bin/env bash
#
# setup-server.sh — Provisiona una VM Ubuntu 22.04 para correr SIGAV (Laravel 7).
# Instala: Nginx, PHP 7.4 + extensiones, MySQL 8, Composer, Node.js, Certbot.
#
# Uso (dentro de la VM, como root o con sudo):
#   sudo bash setup-server.sh
#
set -euo pipefail

echo "==> Pedimos la contraseña para el usuario de base de datos 'sigav'"
read -rsp "Contraseña para la BD (usuario sigav): " DB_PASS
echo
read -rp "Nombre del dominio (ej: miempresa.com): " DOMAIN

echo "==> Actualizando el sistema..."
export DEBIAN_FRONTEND=noninteractive
apt-get update -y
apt-get upgrade -y

echo "==> Instalando dependencias base..."
apt-get install -y software-properties-common curl git unzip nginx mysql-server

echo "==> Agregando repositorio de PHP 7.4 (ondrej/php)..."
add-apt-repository -y ppa:ondrej/php
apt-get update -y

echo "==> Instalando PHP 7.4 y extensiones requeridas por Laravel 7..."
apt-get install -y \
  php7.4-fpm php7.4-cli php7.4-mysql php7.4-mbstring php7.4-xml \
  php7.4-bcmath php7.4-curl php7.4-gd php7.4-zip php7.4-intl php7.4-tokenizer

echo "==> Instalando Composer..."
curl -sS https://getcomposer.org/installer | php
mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer

echo "==> Instalando Node.js 16 (para compilar assets con Laravel Mix)..."
curl -fsSL https://deb.nodesource.com/setup_16.x | bash -
apt-get install -y nodejs

echo "==> Instalando Certbot (SSL)..."
apt-get install -y certbot python3-certbot-nginx

echo "==> Configurando la base de datos MySQL..."
mysql --execute="CREATE DATABASE IF NOT EXISTS sigav CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;"
mysql --execute="CREATE USER IF NOT EXISTS 'sigav'@'localhost' IDENTIFIED BY '${DB_PASS}';"
mysql --execute="GRANT ALL PRIVILEGES ON sigav.* TO 'sigav'@'localhost';"
mysql --execute="FLUSH PRIVILEGES;"

echo "==> Configurando Nginx..."
SITE_CONF=/etc/nginx/sites-available/sigav
sed "s/__DOMAIN__/${DOMAIN}/g" /var/www/sigav/deploy/nginx-sigav.conf > "${SITE_CONF}" 2>/dev/null || {
  # Si el repo todavía no está clonado, generamos una config mínima.
  cat > "${SITE_CONF}" <<NGINX
server {
    listen 80;
    server_name ${DOMAIN} www.${DOMAIN};
    root /var/www/sigav/public;
    index index.php index.html;

    location / {
        try_files \$uri \$uri/ /index.php?\$query_string;
    }

    location ~ \.php\$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:/run/php/php7.4-fpm.sock;
    }

    location ~ /\.(?!well-known).* { deny all; }
    client_max_body_size 50M;
}
NGINX
}

ln -sf "${SITE_CONF}" /etc/nginx/sites-enabled/sigav
rm -f /etc/nginx/sites-enabled/default
nginx -t && systemctl reload nginx
systemctl enable --now php7.4-fpm nginx mysql

echo
echo "============================================================"
echo " Servidor listo."
echo " Siguiente paso: subir el código a /var/www/sigav y"
echo " configurar el archivo .env (ver deploy/DEPLOY_GCP.md)."
echo "============================================================"
