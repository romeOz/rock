#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping ZeroMQ on HHVM"
    exit 0
fi

sudo add-apt-repository -y ppa:chris-lea/zeromq
#sudo add-apt-repository -y ppa:ondrej/php5
sudo apt-get update

# Install ZeroMQ
sudo apt-get install -y pkg-config libzmq3-dev libpgm-5.1-0
#wget http://download.zeromq.org/zeromq-4.0.4.tar.gz
#tar -xf zeromq-4.0.4.tar.gz
#cd zeromq-4.0.4
#./configure
#make
#sudo make install
#cd -
#yes | pecl install zmq-beta
# compile manually, because `pecl install apcu-beta` keep asking questions
git clone https://github.com/mkoppanen/php-zmq.git
cd php-zmq
phpize && ./configure && make && make install && echo "Installed ext/php-zmq-dev"

echo "extension = zmq.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

cd -
# Run servers (workers)
php tests/data/mq/zero/simple_server.php &
php tests/data/mq/zero/pub_server.php &