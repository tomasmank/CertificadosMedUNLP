#!/usr/bin/env bash
composer install -n
php bin/console doc:mig:mig --no-interaction
 
exec "$@"