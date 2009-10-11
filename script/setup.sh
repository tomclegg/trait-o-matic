#!/bin/sh

set -e

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

# Update php.ini
cp /etc/php5/apache2/php.ini /tmp
./update-php-init php-ini-update.txt /tmp/php.ini
sudo cp /tmp/php.ini /etc/php5/apache2/php.ini

# Create dirs
sudo -u "$USER" mkdir -p ~/tmp ~/upload ~/log
sudo -u "$USER" chmod a+rwxt ~/tmp ~/upload

# Apache config
perl -p -e 's/%([A-Z]+)%/$ENV{$1}/g' < trait-apache-site.in > /tmp/trait-apache-site
sudo cp -f /tmp/trait-apache-site /etc/apache2/sites-available/trait-o-matic
sudo a2enmod expires
sudo a2enmod deflate
sudo a2enmod rewrite
sudo a2ensite trait-o-matic
sudo a2dissite default
sudo /etc/init.d/apache2 restart

# Init script
sudo perl -p -e 's/%([A-Z]+)%/$ENV{$1}/g' < ../core/trait.sh.in > /tmp/trait-o-matic
chmod 755 /tmp/trait-o-matic
sudo cp /tmp/trait-o-matic /etc/init.d/trait-o-matic
sudo update-rc.d trait-o-matic start 20 2 3 4 5 . stop 80 0 1 6 .
