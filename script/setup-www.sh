#!/bin/sh

set -e

WGET='wget -c -nv'
SOURCE=$HOME/trait-o-matic
TARGET=$HOME/www
CONFIG=$HOME/config
cd
$WGET http://codeigniter.com/download.php
rm -Rf CodeIgniter_1.7.1
unzip -q CodeIgniter_1.7.1.zip
cd CodeIgniter_1.7.1
rm -Rf $TARGET
mkdir $TARGET
cp index.php $TARGET
cp -R system $TARGET/system
cp -R $SOURCE/web/errors $TARGET
cp -R $SOURCE/web/media $TARGET
cp -R $SOURCE/web/scripts $TARGET
cp -R $SOURCE/web/statistics $TARGET
rm -Rf $TARGET/system/application
cp -R $SOURCE/web/system/application $TARGET/system/application
cp $SOURCE/web/htaccess $TARGET/.htaccess
for conf in config database trait-o-matic
do
  ln -s $CONFIG/$conf.php $TARGET/system/application/config/$conf.php
  if cp -i $TARGET/system/application/config/$conf.default.php $CONFIG/$conf.php
  then
    echo >&2 "*** "
    echo >&2 "*** Please edit $CONFIG/$conf.php to suit your installation."
    echo >&2 "*** "
  else
    echo >&2 "*** "
    echo >&2 "*** Please ensure $CONFIG/$conf.php is up-to-date."
    echo >&2 "*** Latest defaults can be found at:"
    echo >&2 "***   $TARGET/system/application/config/$conf.default.php"
    echo >&2 "*** "
  fi
done
perl -p -e "s/%USER%/$USER/" < $SOURCE/web/system/application/config/upload.php.in > $TARGET/system/application/config/upload.php

