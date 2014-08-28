#!/bin/sh

SCRIPT=$(readlink -f "$0")
CWD=$(dirname "$SCRIPT")

# Install Sphinx
sudo add-apt-repository -y ppa:builds/sphinxsearch-rel21
sudo apt-get update
sudo apt-get install sphinxsearch
sudo service sphinxsearch stop

# log files
sudo mkdir /var/log/sphinx
sudo touch /var/log/sphinx/searchd.log
sudo touch /var/log/sphinx/query.log
sudo chmod -R 777 /var/log/sphinx # ugly (for travis)

# spl dir
sudo mkdir /var/lib/sphinx
sudo chmod 777 /var/lib/sphinx # ugly (for travis)

# run dir pid
sudo mkdir /var/run/sphinx
sudo chmod 777 /var/run/sphinx # ugly (for travis)

# Setup source database
mysql -D rocktest -u travis < $CWD/../sphinx/source.sql

# setup test Sphinx indexes:
indexer --config $CWD/../sphinx/sphinx.conf --all

# run searchd:
searchd --config $CWD/../sphinx/sphinx.conf

