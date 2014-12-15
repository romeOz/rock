#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping APC on HHVM"
    exit 0
fi

# this is helpful to compile extension
sudo apt-get install autoconf

###
# Install Imagick
###
IMAGICK_VERSION=3.1.2

wget http://pecl.php.net/get/imagick-${IMAGICK_VERSION}.tgz
tar zxvf imagick-${IMAGICK_VERSION}.tgz
cd "imagick-${IMAGICK_VERSION}"
phpize && ./configure && make install && echo "Installed ext/imagick-${IMAGICK_VERSION}"

echo "extension = imagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini


###
# Install gmagick
###
#GMAGICK=1.1.7RC2
#
#sudo apt-get install graphicsmagick
#wget http://pecl.php.net/get/gmagick-1.1.7RC2.tgz
#tar zxvf gmagick-${GMAGICK}.tgz
#cd "gmagick-${GMAGICK}"
#phpize && ./configure && make install && echo "Installed ext/gmagick-${GMAGICK}"
#
#echo "extension = gmagick.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini