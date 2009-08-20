#!/bin/sh

set -e

# Build python exts
(cd ../core ; python setup.py build_ext --inplace)

# Update php.ini
cp /etc/php5/apache2/php.ini /tmp
./update-php-init php-ini-update.txt /tmp/php.ini
sudo cp /tmp/php.ini /etc/php5/apache2/php.ini

# Create dirs
mkdir -p ~/tmp ~/upload ~/log
chmod a+rwxt ~/tmp ~/upload

# Apache config
perl -p -e "s/%USER%/$USER/" < trait-apache-site.in > /tmp/trait-apache-site
sudo cp -f /tmp/trait-apache-site /etc/apache2/sites-available/trait
sudo a2ensite trait
sudo apache2ctl restart

# Init script
sudo perl -p -e "s/%USER%/$USER/" < ../core/trait.sh.in > /tmp/trait.sh
chmod 755 /tmp/trait.sh
sudo cp /tmp/trait.sh /etc/init.d/trait.sh
sudo update-rc.d trait.sh start 20 2 3 4 5 . stop 80 0 1 6 .
