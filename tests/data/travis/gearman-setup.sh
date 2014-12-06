#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Gearman on HHVM"
    exit 0
fi
sudo add-apt-repository -y ppa:ondrej/php5-5.6
sudo apt-get update

# Install Gearman
sudo apt-get install -y libgearman6
sudo apt-get install -y php5-gearman

# Install gearman-server
sudo apt-get install gearman-job-server

cd -
# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

