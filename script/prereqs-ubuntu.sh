#!/bin/sh

set -e

apt-get install wget
apt-get install zip unzip
 
apt-get install apache2
apt-get install apache2-threaded-dev
 
a2enmod expires
a2enmod deflate
a2enmod rewrite

apache2ctl graceful
 
apt-get install mysql-server
# note the prompt to set root password
apt-get install mysql-client
apt-get install libmysqlclient15-dev
 
apt-get install python-dev --fix-missing
apt-get install libapache2-mod-python
apt-get install python-mysqldb
apt-get install python-pyrex
 
apt-get install php5
apt-get install php5-dev
apt-get install php5-mysql
 
echo If this fails, ensure that you have universe in your apt source list
apt-get install python-biopython

#apt-get install sendmail

