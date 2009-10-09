#!/bin/sh

set -e

WGET='wget -c -nv'
if [ -z "$SOURCE" ]; then SOURCE=$HOME/trait-o-matic; fi
if [ -z "$TARGET" ]; then TARGET=$HOME/www; fi
if [ -z "$CONFIG" ]; then CONFIG=$HOME/config; fi
if [ -z "$TMP" ]; then TMP=$HOME/tmp; fi
if [ -z "$CI_VERSION" ]; then CI_VERSION=1.7.1; fi
if [ -z "$TEXTILE_VERSION" ]; then TEXTILE_VERSION=2.0.0; fi

mkdir -p $TMP
cd $TMP

# Todo: use "$WGET http://codeigniter.com/download.php", figure out
# which version was downloaded, and set CI_VERSION (if not provided in
# env)

$WGET http://codeigniter.com/download_files/CodeIgniter_$CI_VERSION.zip
$WGET http://textile.thresholdstate.com/file_download/2/textile-$TEXTILE_VERSION.tar.gz

rm -Rf CodeIgniter_$CI_VERSION
unzip -q CodeIgniter_$CI_VERSION.zip
cd CodeIgniter_$CI_VERSION
mkdir -p $TARGET
tar cf - index.php system | tar -C $TARGET -xf -

cd $TMP
rm -rf textile-$TEXTILE_VERSION
tar xzf textile-$TEXTILE_VERSION.tar.gz
cd textile-$TEXTILE_VERSION
cp classTextile.php $TARGET/system/application/libraries/Textile.php

cd $SOURCE/web
tar cf - errors media scripts statistics system/application htaccess | tar -C $TARGET -xf -

cd $TARGET
mv htaccess .htaccess


for conf in config database trait-o-matic
do
  if [ ! -s $TARGET/system/application/config/$conf.php ] \
     && mv -i $TARGET/system/application/config/$conf.php $CONFIG/$conf.php 2>/dev/null
  then
    echo >&2 "*** "
    echo >&2 "*** Moved $TARGET/system/application/config/conf.php"
    echo >&2 "*** to $CONFIG/conf.php"
    echo >&2 "*** "
  fi
  if [ ! -e $TARGET/system/application/config/$conf.php ]
  then
    ln -s $CONFIG/$conf.php $TARGET/system/application/config/$conf.php
  fi
  if cp -i $TARGET/system/application/config/$conf.default.php $CONFIG/$conf.php 2>/dev/null
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

