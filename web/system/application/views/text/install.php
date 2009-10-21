Complete source code for Trait-o-matic is now available in the repository but lacks thorough commenting. The following are instructions detailing how code from the repository can be used to install a fully working mirror.

h2. Installing from source

To install the latest version of Trait-o-matic, issue the following commands.  _(This procedure was tested on Debian/GNU Linux version "lenny" and should work equally well on Ubuntu version "hardy" or later versions.)_

bc. (set -e
cd
chmod a+x ~
sudo apt-get update
sudo apt-get -qq install git git-core
git clone git://github.com/tomclegg/trait-o-matic.git
cd trait-o-matic/script
sudo mkdir /home/trait
sudo chown www-data:www-data /home/trait
USER=www-data HOME=/home/trait PORT=80 ./install-root.sh
#
# Note prompt to set up a mysql root password during mysql-server install
#
sudo -u www-data USER=www-data HOME=/home/trait ./install-user.sh
)

Check the configuration (this should not output any warnings):

bc. ~/trait-o-matic/script/check.sh

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

bc. sudo -u www-data USER=www-data HOME=/home/trait IMPORT_BINARY=1 ~/trait-o-matic/setup-external-data.sh

Finally, start the Trait-o-matic core XMLRPC server.

<pre>
sudo /etc/init.d/trait-o-matic start
</pre>
