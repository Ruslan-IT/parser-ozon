#!/bin/bash
set -e

#cd /home/l/lionti9k/webway.live  # путь к проекту на Beget

# Подтягиваем изменения из GitHub
git pull origin master

echo "✅ Сервер обновлён"

php8.3 composer.phar install --no-dev --optimize-autoloader

php8.3 artisan cache:clear        # Очистить общий кэш
php8.3 artisan config:clear       # Очистить кеш конфигураций
php8.3 artisan route:clear        # Очистить кеш маршрутов
php8.3 artisan view:clear         # Очистить кеш Blade шаблонов
php8.3 artisan event:clear        # Очистить кеш событий (если используется)

echo "✅ Кэш почистили обновлён"


# запуск команды sh deploy_server.sh
