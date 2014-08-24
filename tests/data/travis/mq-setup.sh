#!/bin/sh

if (php --version | grep -i HHVM > /dev/null); then
    echo "skipping APC on HHVM"
else
    sudo add-apt-repository -y ppa:chris-lea/zeromq
    #sudo add-apt-repository -y ppa:ondrej/php5
    sudo apt-get update

    # Install ZeroMQ
    sudo apt-get install libzmq3 libpgm-5.1-0 php5-zmq

    # Install Gearman
    sudo apt-get install gearman-job-server libev-libevent-dev uuid-dev php-pear
    sudo pecl install gearman
    echo "extension=gearman.so" >> ~/.phpenv/versions/$(phpenv version-name)/etc/php.ini

    # Install RabbitMQ
    #sudo apt-get install rabbitmq-server
fi