#!/bin/bash
# Путь из команды pwd на сервере
cd /home/i/info90zj/info90zj.beget.tech || exit
/usr/local/bin/php8.3 artisan queue:work --queue=parsers --once

