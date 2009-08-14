#!/bin/sh

set -e

(cd ../core ; python setup.py build_ext --inplace)
sudo mysql < setup.sql
cp /etc/php5/apache2/php.ini /tmp
./update-php-init php-ini-update.txt /tmp/php.ini
sudo cp /tmp/php.ini /etc/php5/apache2/php.ini
sudo apache2ctl restart
sudo mysql < setup.sql
