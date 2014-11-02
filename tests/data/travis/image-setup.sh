#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping APC on HHVM"
    exit 0
fi

sudo apt-get install php5-imagick

echo "extension = imagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini