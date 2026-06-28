#!/bin/bash
cd /var/www/sabor-de-mae
php8.2 artisan migrate:fresh --env=testing 2>&1
php8.2 vendor/bin/pest 2>&1
