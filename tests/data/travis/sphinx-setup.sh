#!/bin/sh

SCRIPT=$(readlink -f "$0")
CWD=$(dirname "$SCRIPT")

# Install Sphinx
add-apt-repository -y ppa:builds/sphinxsearch-rel22
apt-get update
apt-get install sphinxsearch
service sphinxsearch stop

# log files
mkdir /var/log/sphinx
touch /var/log/sphinx/searchd.log
touch /var/log/sphinx/query.log
chmod -R 777 /var/log/sphinx # ugly (for travis)

# spl dir
mkdir /var/lib/sphinx
chmod 777 /var/lib/sphinx # ugly (for travis)

# run dir pid
mkdir /var/run/sphinx
chmod 777 /var/run/sphinx # ugly (for travis)

# Setup source database
mysql -D rocktest -u travis < ${CWD}/../sphinx/source.sql

# setup test Sphinx indexes:
indexer --config ${CWD}/../sphinx/sphinx.conf --all

# run searchd:
searchd --config ${CWD}/../sphinx/sphinx.conf

