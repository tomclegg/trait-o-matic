#!/bin/sh

set -e

WGET='wget -c -nv'
SOURCE=$HOME/trait-o-matic
TARGET=$HOME/www
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
perl -p -e "s/%USER%/$USER/" < $SOURCE/web/system/application/config/upload.php.in > $TARGET/system/application/config/upload.php

