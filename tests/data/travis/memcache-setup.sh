#!/bin/sh

if (php --version | grep -i HHVM > /dev/null); then
  echo "skipping memcached on HHVM"
else
  mkdir -p ~/.phpenv/versions/$(phpenv version-name)/etc
  echo "extension=memcache.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
  echo "extension=memcached.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi