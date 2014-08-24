#!/bin/sh

if (php --version | grep -i HHVM > /dev/null); then
    echo "skipping APC on HHVM"
else
    # Install ZeroMQ
    sudo add-apt-repository -y ppa:chris-lea/zeromq
    sudo apt-get update
    sudo apt-get install libzmq3 php5-zmq

    # Install Gearman
    sudo apt-get install gearman-job-server php5-gearman

    # Install RabbitMQ
    #sudo apt-get install rabbitmq-server
fi