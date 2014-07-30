#!/bin/sh

# this is helpful to compile extension
sudo apt-get install autoconf

# install this version
APCU=4.0.6

# compile manually, because `pecl install apcu-beta` keep asking questions
wget http://pecl.php.net/get/apcu-$APCU.tgz
tar zxvf apcu-$APCU.tgz
cd "apcu-${APCU}"
phpize && ./configure && make install && echo "Installed ext/apcu-${APCU}"

echo "extension = apcu.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "apc.enable_cli = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini