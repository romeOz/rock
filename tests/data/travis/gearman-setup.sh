#!/bin/sh
if (php --version | grep -i HipHop > /dev/null); then
echo "Skipping Gearman on HHVM"
exit 0
fi

sudo add-apt-repository -y ppa:ondrej/php5
sudo apt-get update

# Install gearman-server
sudo apt-get install -y gearman-job-server

# Install Gearman
sudo apt-get install -y libboost-all-dev
sudo apt-get install -y gperf
sudo apt-get install -y libuuid1
#sudo apt-get install libev-libevent-dev
sudo apt-get install -y uuid-dev
sudo apt-get install -y libgearman-dev

cd ~

wget https://launchpad.net/gearmand/1.2/1.1.11/+download/gearmand-1.1.11.tar.gz
tar xf gearmand-1.1.11.tar.gz
cd gearmand-1.1.11
./configure
make
sudo make install

sudo ldconfig
cd ~
# Install pecl gearman
yes | pecl install gearman
#echo "extension=gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

cd ~
# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &