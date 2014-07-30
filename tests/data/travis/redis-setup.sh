#!/bin/sh

if (php --version | grep -i HHVM > /dev/null); then
  echo "skipping memcache on HHVM"
else
  mkdir -p ~/.phpenv/versions/$(phpenv version-name)/etc
  echo "extension=redis.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
fi