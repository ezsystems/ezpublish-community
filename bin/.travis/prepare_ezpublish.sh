#!/bin/bash

# Script to prepare eZPublish installation

echo "> prefer ip4 to avoid packagist.org composer issues"
sudo sh -c "echo 'precedence ::ffff:0:0/96 100' >> /etc/gai.conf"

echo "> Setup github auth key to not reach api limit"
cp bin/.travis/composer-auth.json ~/.composer/auth.json

# Switch to another Symfony version if asked for (with composer update to not use composer.lock if present)
if [ "$SYMFONY_VERSION" != "" ] ; then
    echo "> Install dependencies through Composer (with custom Symfony version: ${SYMFONY_VERSION})"
    composer require --no-update symfony/symfony="${SYMFONY_VERSION}"
    composer update --no-progress --no-interaction --prefer-dist
else
    echo "> Install dependencies through Composer"
    composer install --no-progress --no-interaction --prefer-dist
fi

echo "> Set folder permissions"
sudo find {ezpublish/{cache,logs,config,sessions},ezpublish_legacy/{design,extension,settings,var},web} -type d | sudo xargs chmod -R 777
sudo find {ezpublish/{cache,logs,config,sessions},ezpublish_legacy/{design,extension,settings,var},web} -type f | sudo xargs chmod -R 666

echo "> Run assetic dump for behat env"
php ezpublish/console --env=behat assetic:dump

echo "> Clear and warm up caches for behat env"
php ezpublish/console cache:clear --env=behat --no-debug
