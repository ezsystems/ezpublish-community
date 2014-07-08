#!/bin/bash

# Script to prepare eZPublish installation

echo "> Install dependencies through composer"
composer install --dev --prefer-dist

echo "> Set folder permissions"
sudo chown -R www-data:www-data ezpublish/{cache,logs,config,sessions} \
    ezpublish_legacy/{design,extension,settings,var} web
sudo chmod -R og+rwX ezpublish/{cache,logs,config,sessions} \
    ezpublish_legacy/{design,extension,settings,var} web

echo "> Run assetic dump for behat env"
php ezpublish/console --env=behat assetic:dump
