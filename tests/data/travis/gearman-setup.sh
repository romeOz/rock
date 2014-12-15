#!/bin/sh
if (php --version | grep -i HipHop > /dev/null); then
echo "Skipping Gearman on HHVM"
exit 0
fi

GEARMAND_VERSION=1.1.12

sudo add-apt-repository -y ppa:ondrej/php5
sudo apt-get update
# Install Gearman
sudo apt-get install -y libboost-all-dev
sudo apt-get install -y gperf uuid-dev
#sudo apt-get install -y gperf libevent-dev uuid-dev libcloog-ppl-dev
sudo apt-get install -y libgearman-dev
wget https://launchpad.net/gearmand/1.2/${GEARMAND_VERSION}/+download/gearmand-${GEARMAND_VERSION}.tar.gz
tar xf gearmand-${GEARMAND_VERSION}.tar.gz
cd gearmand-${GEARMAND_VERSION}
./configure
make
sudo make install
cd -
sudo ldconfig

# Install pecl gearman
yes | pecl install gearman
#echo "extension=gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini
# Install gearman-server
sudo apt-get install -y gearman-job-server

# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &