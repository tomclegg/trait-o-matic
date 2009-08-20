#!/bin/sh

set -e

(cd ../core ; python setup.py build_ext --inplace)
sudo mysql < setup.sql
cp /etc/php5/apache2/php.ini /tmp
./update-php-init php-ini-update.txt /tmp/php.ini
mkdir ~/tmp ~/upload
chmod a+rwxt ~/tmp ~/upload
sudo cp /tmp/php.ini /etc/php5/apache2/php.ini
sudo cp -f trait-apache-site /etc/apache2/sites-available/trait
sudo a2ensite trait
sudo apache2ctl restart
