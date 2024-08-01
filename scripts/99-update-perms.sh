#!/bin/bash

echo "-- Update Permission --"

mkdir -p /var/www/html/var/cache
mkdir -p /var/www/html/var/log

chown -Rf nginx.nginx /var/www/html/var

