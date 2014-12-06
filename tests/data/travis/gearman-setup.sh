#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Gearman on HHVM"
    exit 0
fi
#sudo add-apt-repository -y ppa:ondrej/php5
#sudo apt-get update

# Install Gearman
sudo apt-get install gearman-job-server
#sudo apt-get install php5-gearman
#echo "extension = gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
sudo apt-get install -y libboost-all-dev
sudo apt-get install -y gperf
sudo apt-get install -y libevent-dev
sudo apt-get install -y uuid-dev
sudo apt-get install -y libcloog-ppl-dev

wget https://launchpad.net/gearmand/1.2/1.1.11/+download/gearmand-1.1.12.tar.gz
tar xf gearmand-1.1.12.tar.gz
cd gearmand-1.1.12
./configure
make
sudo make install
cd -
yes | pecl install gearman

# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

