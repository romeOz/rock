#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Memcached on HHVM"
    exit 0
fi

mkdir -p ~/.phpenv/versions/$(phpenv version-name)/etc
echo "extension=memcache.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
echo "extension=memcached.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini