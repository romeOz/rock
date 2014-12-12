#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping RabbitMQ on HHVM"
    exit 0
fi


php tests/data/mq/rabbit/simple_server.php &
php tests/data/mq/rabbit/pub_server.php &
# Install RabbitMQ
#sudo apt-get install rabbitmq-server
