#!/bin/sh

# this is helpful to compile extension
sudo apt-get install autoconf

# install this version
VERSION=1.2.2

cd /tmp/

# compile manually, because `pecl install apcu-beta` keep asking questions
wget http://pecl.php.net/get/couchbase-$VERSION.tgz
tar zxvf couchbase-$VERSION.tgz
cd "couchbase-${VERSION}"
phpize && ./configure && make install && echo "Installed ext/couchbase-${VERSION}"

echo "extension = couchbase.so" >> /etc/php5/fpm/conf.d/couchbase.ini
echo "extension = couchbase.so" >> /etc/php5/cli/conf.d/couchbase.ini

# Create test bucket
/opt/couchbase/bin/couchbase-cli bucket-create -c 127.0.0.1:8091 --bucket=default --bucket-type=memcached --bucket-ramsize=200 --enable-flush=1