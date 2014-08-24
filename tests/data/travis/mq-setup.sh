#!/bin/sh

if (php --version | grep -i HHVM > /dev/null); then
    echo "skipping MQ on HHVM"
else
    sudo add-apt-repository -y ppa:chris-lea/zeromq
    sudo add-apt-repository -y ppa:ondrej/php5
    sudo apt-get update

    # Install ZeroMQ
    sudo apt-get install libzmq3 libpgm-5.1-0
    #wget http://download.zeromq.org/zeromq-4.0.4.tar.gz
    #tar -xf zeromq-4.0.4.tar.gz
    #cd zeromq-4.0.4
    #./configure
    #make
    #sudo make install
    #cd -
    yes | pecl install zmq-beta

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
    wget https://launchpad.net/gearmand/1.2/1.1.11/+download/gearmand-1.1.11.tar.gz
    tar xf gearmand-1.1.11.tar.gz
    cd gearmand-1.1.11
    ./configure
    make
    sudo make install
    cd -
    yes | pecl install gearman

    # Run servers (workers)
    php tests/data/mq/zero/simple_server.php &
    php tests/data/mq/zero/pub_server.php &

    php tests/data/mq/gearman/simple_server.php &
    # Install RabbitMQ
    #sudo apt-get install rabbitmq-server
fi