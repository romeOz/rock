#!/bin/sh

if (php --version | grep -i HipHop > /dev/null); then
    echo "Skipping Gearman on HHVM"
    exit 0
fi
#sudo add-apt-repository -y ppa:ondrej/php5
#sudo apt-get update

# Install Gearman
sudo apt-get install -y libgearman-dev php5-dev
cd -

# Install pecl gearman
yes | pecl install channel://pecl.php.net/gearman-1.1.2

# Install gearman-server
sudo apt-get install gearman-job-server

cd -
# Run servers (workers)
php tests/data/mq/gearman/simple_server.php &

