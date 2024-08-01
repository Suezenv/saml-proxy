#!/bin/bash

# reinstall with composer because /var/www/htlm is mounted by docker(-compose)
if [ "$APP_ENV" == "dev" ]; then
    
    if [[ ! -d "/var/www/html/vendor" ]]; then
      echo '-- install vendor via composer --'
      composer install
    else
       echo '-- no vendor installation. WARNING check in DEV mode to delete "vendor" dir re-install --' 
    fi
    
else
    echo '-- vendor must be already exist --'
fi