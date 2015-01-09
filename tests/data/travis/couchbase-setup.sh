#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Couchbase on HHVM"
    exit 0
fi

# Download and uncompress Couchbase Server
wget http://packages.couchbase.com/releases/3.0.1/couchbase-server-community_3.0.1-ubuntu12.04_amd64.deb
dpkg -i couchbase-server-community_3.0.1-ubuntu12.04_amd64.deb

# Add couchbase.list to sources.list
wget -O/etc/apt/sources.list.d/couchbase.list http://packages.couchbase.com/ubuntu/couchbase-ubuntu1204.list
wget -O- http://packages.couchbase.com/ubuntu/couchbase.key | apt-key add -

# update list
apt-get update

# install C Client Library
apt-get install libcouchbase2-libevent
apt-get install libcouchbase-dev

# this is helpful to compile extension
apt-get install autoconf

# install this version
VERSION=1.2.2

#cd /tmp/

# compile manually, because `pecl install` keep asking questions
wget http://pecl.php.net/get/couchbase-$VERSION.tgz
tar zxvf couchbase-$VERSION.tgz
cd "couchbase-${VERSION}"
phpize && ./configure && make install && echo "Installed ext/couchbase-${VERSION}"

#echo "extension = couchbase.so" >> /etc/php5/fpm/conf.d/couchbase.ini
#echo "extension = couchbase.so" >> /etc/php5/cli/conf.d/couchbase.ini
echo "extension = couchbase.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

# Create test bucket
/opt/couchbase/bin/couchbase-cli bucket-create -c 127.0.0.1:8091 --bucket=default --bucket-type=memcached --bucket-ramsize=64 --enable-flush=1 -u demo -p demo