#!/bin/bash
cd ~/info90zj.beget.tech

# Логируем начало работы
echo "=== Starting parser worker at $(date) ===" >> ~/parser-worker.log

# Запускаем воркер
/usr/local/bin/php8.3 artisan queue:work \
  --queue=parsers \
  --sleep=3 \
  --tries=3 \
  --timeout=180 \
  --memory=128 \
  --stop-when-empty

# Логируем завершение
echo "=== Parser worker finished at $(date) ===" >> ~/parser-worker.log

