Complete source code for Trait-o-matic is available from "multiple sources":source. The following instructions can be used to install a fully working mirror.

h2. Installing from source

To install the latest version of Trait-o-matic, issue the following commands.  Replace http://yourhost.example.com/ with the appropriate URL for your site.   _(This procedure was tested on Debian/GNU Linux version "lenny" and should work equally well on Ubuntu version "hardy" or later versions.)_

bc. (
set -e
cd
chmod a+x ~
sudo apt-get update
sudo apt-get -qq install git git-core
git clone git://github.com/tomclegg/trait-o-matic.git
cd trait-o-matic/script
sudo mkdir /home/trait
sudo chown www-data:www-data /home/trait
USER=www-data HOME=/home/trait BASE_URL=http://yourhost.example.com/ ./configure.sh
./install.sh
)

You will be prompted to set up a mysql root password if you hadn't already installed mysql-server.  Other than that, everything should happen without further input.

Check the configuration (this should not output any warnings):

bc. ~/trait-o-matic/script/check.sh

Restart apache (the <code>install.sh</code> script already ran "apache2ctl graceful" which should be enough, but this will do a full stop/start cycle just to be sure.)

bc. sudo /etc/init.d/apache2 restart

If your installation has access to a Free Factories storage system, set up the Free Factories client library now.  If the reference data is available on your cluster, this step will make the following step proceed _much_ faster.

bc. echo "deb http://dev.freelogy.org/apt hardy main contrib non-free" \
 | sudo tee -a /etc/apt/sources.list
wget -q http://dev.freelogy.org/53212765.key -O- | sudo apt-key add -
sudo apt-get update
sudo apt-get install libwarehouse-perl

Configure the Free Factories client library:

bc. [ -e /etc/warehouse/warehouse-client.conf ] || (
sudo mkdir -p /etc/warehouse
echo '$Warehouse::warehouses=[{name=>"templeton",configurl=>
      "http://templeton-controller.oxf.freelogy.org:44848/warehouse-client.conf"}];1;
     ' | sudo tee /etc/warehouse/warehouse-client.conf >/dev/null )

Populate the databases with reference data.  _(Note: This takes about 30 minutes if you're downloading the data from the local cluster, or 2-3 hours if you're downloading everything on a fast internet link.)_

bc. sudo -u www-data USER=www-data HOME=/home/trait IMPORT_BINARY=1 ~/trait-o-matic/script/setup-external-data.sh

If you are downloading data from the local cluster, you may be prompted to enter some commands to finish importing the data into the database and restart the MySQL server.

Finally, start the Trait-o-matic core XMLRPC server.

<pre>
sudo /etc/init.d/trait-o-matic start
</pre>

*Optional:* Register your installation and git repository so (if you leave it publicly accessible) other developers can see what you commit to your repository.  It's also a good idea to describe what you're working on at "the Trait-o-matic home page":https://trac.scalablecomputingexperts.com/wiki/Doc/Trait-o-matic.

bc. ~/trait-o-matic/script/clonetrack-update.sh

*Optional:* Set up an .htpasswd file with a username and password.  This allows users to log in at <code>http://your.T-o-m.host/authenticate</code> to make admin features appear (such as browsing other T-o-m data sets on your cluster -- look in <code>/home/trait/config/trait-o-matic.php</code> for more).

bc. sudo -u www-data htpasswd -b -c /home/trait/www/.htpasswd foouser b@rpassw0rd

Stay current by "upgrading to the latest version":upgrade periodically.
