#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Gearman on HHVM"
    exit 0
fi
sudo add-apt-repository -y ppa:ondrej/php5-5.6
sudo apt-get update

# Install Gearman
sudo apt-get install gearman-job-server
#sudo apt-get install php5-gearman
#echo "extension = gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
sudo apt-get install libboost-all-dev
sudo apt-get install gperf
sudo apt-get install libuuid1
#sudo apt-get install libev-libevent-dev
sudo apt-get install uuid-dev
sudo apt-get install libgearman-dev

git clone https://github.com/hjr3/pecl-gearman.git
cd pecl-gearman
phpize && ./configure && make && make install && echo "Installed ext/php-gearman-dev"

echo "extension = gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

