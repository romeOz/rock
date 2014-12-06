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
wget https://launchpad.net/gearmand/1.2/1.1.12/+download/gearmand-1.1.12.tar.gz
tar xf gearmand-1.1.12.tar.gz
cd gearmand-1.1.12
./configure
make
sudo make install
cd -
yes | pecl install gearman

# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

