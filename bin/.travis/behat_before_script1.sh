#!/bin/sh

# Script to do tasks before script, step 1 of 2
## Step 1 install vendors + db + legacy + apache + selenium, but does not run scripts
## So you can swap out a vendor for testing between step 1 and 2

mysql -e "CREATE DATABASE IF NOT EXISTS behattestdb;" -uroot
composer install --dev --prefer-dist --no-scripts
./bin/.travis/prepare_legacy.sh

# Http Server
./bin/.travis/configure_apache2.sh

# X & Selenium
export DISPLAY=:99.0
sh -e /etc/init.d/xvfb start
#wget http://selenium-release.storage.googleapis.com/2.41/selenium-server-standalone-2.41.0.jar
#java -jar selenium-server-standalone-2.41.0.jar > /dev/null &
wget http://selenium.googlecode.com/files/selenium-server-standalone-2.35.0.jar
java -jar selenium-server-standalone-2.35.0.jar > /dev/null &

sleep 5
