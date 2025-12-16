#!/bin/bash
# Скрипт для запуска Laravel queue на Beget

# Переходим в директорию проекта
cd /home/info90zj/info90zj.beget.tech || exit

# Запускаем очередь на 1 раз, только для нужной очереди
/usr/local/bin/php8.3 artisan queue:work --queue=parsers --once
