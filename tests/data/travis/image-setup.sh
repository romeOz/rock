#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping APC on HHVM"
    exit 0
fi

# this is helpful to compile extension
sudo apt-get install autoconf

# install this version
IMAGICK=3.1.2

wget http://pecl.php.net/get/imagick-${IMAGICK}.tgz
tar zxvf imagick-${IMAGICK}.tgz
cd "imagick-${IMAGICK}"
phpize && ./configure && make install && echo "Installed ext/imagick-${IMAGICK}"

echo "extension = imagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini