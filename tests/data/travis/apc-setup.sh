#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping APC on HHVM"
    exit 0
fi

# this is helpful to compile extension
sudo apt-get install autoconf

# install this version
APCU_VERSION=4.0.6

# compile manually, because `pecl install apcu-beta` keep asking questions
wget http://pecl.php.net/get/apcu-${APCU_VERSION}.tgz
tar zxvf apcu-${APCU_VERSION}.tgz
cd "apcu-${APCU_VERSION}"
phpize && ./configure && make install && echo "Installed ext/apcu-${APCU_VERSION}"

echo "extension = apcu.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "apc.enable_cli = 1" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
