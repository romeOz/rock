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


# install this version
GMAGICK=1.1.7RC2

wget http://pecl.php.net/get/gmagick-1.1.7RC2.tgz
tar zxvf gmagick-${GMAGICK}.tgz
cd "gmagick-${GMAGICK}"
phpize && ./configure && make install && echo "Installed ext/gmagick-${GMAGICK}"

echo "extension = gmagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini