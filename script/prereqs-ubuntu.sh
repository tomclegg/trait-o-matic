#!/bin/sh

set -e

APTGET="apt-get -qq"

$APTGET install wget
$APTGET install zip unzip
 
$APTGET install apache2
$APTGET install apache2-threaded-dev
 
a2enmod expires
a2enmod deflate
a2enmod rewrite

apache2ctl graceful
 
$APTGET install mysql-server
# note the prompt to set root password
$APTGET install mysql-client
$APTGET install libmysqlclient15-dev
 
$APTGET install python-dev --fix-missing
$APTGET install libapache2-mod-python
$APTGET install python-mysqldb
$APTGET install python-pyrex
 
$APTGET install php5
$APTGET install php5-dev
$APTGET install php5-mysql
 
echo If apt-get python-biopython fails, ensure that you have universe in your apt source list
$APTGET install python-biopython

#$APTGET install sendmail

