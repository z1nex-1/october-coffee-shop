#!/bin/sh
set -e
cd /var/www/html/shop

if [ -z "$APP_KEY" ]; then
    [ -s /data/app.key ] || php -r 'echo "base64:".base64_encode(random_bytes(32));' > /data/app.key
    export APP_KEY="$(cat /data/app.key)"
fi

# фото товаров лежат в system_files, поэтому живут на том же томе, что и база
mkdir -p /data/uploads
if [ ! -L storage/app/uploads ]; then
    rm -rf storage/app/uploads
    ln -s /data/uploads storage/app/uploads
fi

touch "$DB_DATABASE"
php artisan october:up

# без этого остаётся пароль admin из сида October
if [ ! -f /data/.admin-password-set ]; then
    password="${ADMIN_PASSWORD:-$(php -r 'echo bin2hex(random_bytes(12));')}"
    php artisan october:passwd admin "$password" >/dev/null
    [ -n "$ADMIN_PASSWORD" ] || echo "Пароль admin в бэкенде: $password"
    touch /data/.admin-password-set
fi

chown -R www-data:www-data storage /data

(
    while true; do
        su www-data -s /bin/sh -c 'php artisan schedule:run' >/dev/null 2>&1 || true
        sleep 60
    done
) &

exec apache2-foreground
