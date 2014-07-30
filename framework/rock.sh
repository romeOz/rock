#!/usr/bin/env bash

CWD=$(dirname "$0")

while [ 1 ] ; do
   if [ "${1#--user=}" != "$1" ] ; then
      user="${1#--user=}"
   elif [ "$1" = "-u" ] ; then
      shift ; user="$1"
   elif [ -z "$1" ] ; then
      break # The keys ended
   else
      echo "Warning: Unknown key" 1>&2
      exit 1
   fi
   shift
done

cp -r ${CWD}/build/apps ${CWD}/../
cp -r ${CWD}/build/assets ${CWD}/../www/
cp -r ${CWD}/build/500.html ${CWD}/../www/

if [[ ${user} != "" ]]; then
  mysql -u${user} -p -e 'CREATE DATABASE rockdemo CHARACTER SET utf8 COLLATE utf8_general_ci;';
  php ${CWD}/../apps/common/migrations/bootstrap.php;
fi;