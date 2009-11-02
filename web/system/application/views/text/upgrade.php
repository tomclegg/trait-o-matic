This guide assumes that you installed Trait-o-matic using the "recommended installation procedure":install.

h2. Upgrading to the latest version

Back up your current installation in case something goes wrong.

bc. sudo tar czf ~/Trait-o-matic-`date +%s`.tar.gz /etc/apache2/ /etc/init.d/trait* /home/trait/

Fetch the latest version of the source code.

bc. cd ~/trait-o-matic && git pull && echo Done.

Upgrade your installation.

bc. (
set -e
chmod a+x ~
cd ~/trait-o-matic
USER=www-data HOME=/home/trait PORT=80 ./script/install-root.sh
sudo -u www-data USER=www-data HOME=/home/trait ./script/install-user.sh
~/trait-o-matic/script/check.sh
sudo /etc/init.d/trait-o-matic restart
echo Done.
)

Using the provided <code>/home/trait/config/config.default.py</code> as a guide, add suitable values for any new configuration settings to your local configuration file, <code>/home/trait/config/config.py</code>.  If you change anything, restart the Trait-o-matic server:

bc. sudo /etc/init.d/trait-o-matic restart

Similarly, compare the following *.php configuration files with *.default.php and update your local version to suit.  You don't need to restart anything after changing these files.

bc. /home/trait/config/config.php
/home/trait/config/database.php
/home/trait/config/trait-o-matic.php
