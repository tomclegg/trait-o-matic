#!/bin/sh

set -e

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

# Update php.ini
cp /etc/php5/apache2/php.ini /tmp
$SCRIPT_DIR/update-php-init php-ini-update.txt /tmp/php.ini
cp /tmp/php.ini /etc/php5/apache2/php.ini

# Create dirs
sudo -u "$USER" mkdir -p $TMP $UPLOAD $LOG
if [ "$USER" != www-data ]; then sudo -u "$USER" chmod a+rwxt $TMP $UPLOAD; fi

# Apache config
perl -p -e 's/%([A-Z]+)%/$ENV{$1}/g' < $SCRIPT_DIR/trait-apache-site.in > /tmp/trait-apache-site
cp -f /tmp/trait-apache-site /etc/apache2/sites-available/trait-o-matic
a2enmod expires
a2enmod deflate
a2enmod rewrite
a2ensite trait-o-matic
a2dissite default
apache2ctl graceful

# Init script
perl -p -e 's/%([A-Z]+)%/$ENV{$1}/g' < $SOURCE/script/trait-o-matic.in > /etc/init.d/trait-o-matic.tmp
chmod 755 /etc/init.d/trait-o-matic.tmp
chown 0:0 /etc/init.d/trait-o-matic.tmp
mv /etc/init.d/trait-o-matic.tmp /etc/init.d/trait-o-matic
update-rc.d trait-o-matic start 20 2 3 4 5 . stop 80 0 1 6 .
