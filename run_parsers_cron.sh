#!/bin/bash

cd /home/i/info90zj/info90zj.beget.tech || exit 1

/usr/local/bin/php8.3 artisan parsers:run-all
/usr/local/bin/php8.3 artisan queue:work --queue=parsers --sleep=3 --stop-when-empty
