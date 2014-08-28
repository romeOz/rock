#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Redis on HHVM"
    exit 0
fi

mkdir -p ~/.phpenv/versions/$(phpenv version-name)/etc
echo "extension=redis.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
