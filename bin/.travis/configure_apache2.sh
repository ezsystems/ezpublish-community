#!/bin/sh

# vhost & phpenv
sed s?%basedir%?$TRAVIS_BUILD_DIR? bin/.travis/apache2/behat_vhost | sudo tee /etc/apache2/sites-available/behat > /dev/null


# modules enabling
sudo a2enmod rewrite
sudo a2enmod actions
sudo a2enmod php5

# sites disabling & enabling
sudo a2dissite default
sudo a2ensite behat

# restart
sudo service apache2 restart
