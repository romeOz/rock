#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Gearman on HHVM"
    exit 0
fi
#sudo add-apt-repository -y ppa:ondrej/php5
#sudo apt-get update

# Install Gearman
sudo apt-get install -y libboost-graph-parallel-dev libboost-mpi-dev libboost-mpi-python-dev
sudo apt-get install -y libboost-all-dev
sudo apt-get install -y gperf libevent-dev uuid-dev libcloog-ppl-dev
wget https://launchpad.net/gearmand/1.2/1.1.12/+download/gearmand-1.1.12.tar.gz
tar xf gearmand-1.1.12.tar.gz
cd gearmand-1.1.12
./configure
make
sudo make install
cd -

# Install pecl gearman
yes | pecl install gearman
echo "extension = gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

# Install gearman-server
sudo apt-get install gearman-job-server

cd -
# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

