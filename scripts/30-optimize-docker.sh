#!/bin/bash

if [ "$APP_ENV" == "dev" ]; then
    echo "optimization OFF"
else
    echo "optimization ON"
    echo "add opcache optimization"
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/docker-vars.ini
    echo "opcache.max_accelerated_files=20000" >> /usr/local/etc/php/conf.d/docker-vars.ini
    echo "opcache.validate_timestamps=0" >> /usr/local/etc/php/conf.d/docker-vars.ini
    echo "realpath_cache_size=4096K" >> /usr/local/etc/php/conf.d/docker-vars.ini
    echo "realpath_cache_ttl=600" >> /usr/local/etc/php/conf.d/docker-vars.ini
fi

