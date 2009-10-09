Complete source code for Trait-o-matic is now available in the repository but lacks thorough commenting. The following are instructions detailing how code from the repository can be used to install a fully working mirror.

h2. Prerequisites

From a fresh install of Ubuntu (Hardy Heron) on a virtual node with AMD64 processors, the following commands were issued:

<script src="http://gist.github.com/132735.js"></script>

Configuration of Apache and Sendmail settings then took place. In particular:
* In <code>/etc/hostname</code>, the existing hostname was deleted and replaced by the fully qualified hostname
* In <code>/etc/hosts</code>, the fully qualified hostname was inserted before the existing hostname
* In <code>/etc/mail/sendmail.mc</code>, the existing hostname was deleted and replaced by the fully qualified hostname in the <code>MASQUERADE_AS</code> option, and all features were moved before <code>MAILER_DEFINITIONS</code>
* In <code>/etc/apache2/sites-available/default</code>, <code>ServerSignature On</code> was replaced by <code>ServerSignature Off</code>
* In <code>/etc/apache2/sites-available/default</code>, the following was added and/or modified:

<script src="http://gist.github.com/132749.js"></script>

* In <code>/etc/php5/apache2/php.ini</code>, the following settings were modified to those values indicated below:

<script src="http://gist.github.com/132788.js"></script>

The following commands were then issued:

<pre>
sudo chown www-data:www-data /var/www
sudo chmod 777 /var/www
sudo apache2ctl graceful
sudo sendmailconfig
</pre>

h2. Core

Trait-o-matic source can be retrieved by downloading from GitHub; core components were extracted and copied to <code>/usr/share/trait</code>, the necessary Pyrex extensions were compiled in-place, and then the XMLRPC init script (by which Trait-o-matic responds to requests) was copied to the correct directory and installed:

<script src="http://gist.github.com/132743.js"></script>

Databases, database users, and database tables were created for access to MySQL (note that it is advised to change the password from the default <code>shakespeare</code>, and that the file <code>/usr/share/trait/config.py</code> must be updated accordingly). The SQL commands issued were as follows:

<script src="http://gist.github.com/132739.js"></script>

Reference genome data were retrieved in 2bit format from UCSC and saved to the path indicated in <code>/usr/share/trait/config.py</code> (by default, <code>/var/trait/hg18.2bit</code>) and other data to be stored in database tables were retrieved from their respective sources:

<script src="http://gist.github.com/132744.js"></script>

Data tables were loaded with the following MySQL commands:

<script src="http://gist.github.com/132767.js"></script>

It is a peculiarity of the current storage system that permanent files are by default stored in <code>/tmp</code>; with a second volume mounted at <code>/scratch</code> with more ample storage space, <code>/tmp</code> was then moved to <code>/scratch/tmp</code> and configured not to be wiped on reboot:

<pre>
sudo mv /tmp /scratch/tmp
sudo ln -s /scratch/tmp /tmp
sudo sed -i'.bak' 's/TMPTIME=0/TMPTIME=-1/' /etc/default/rcS
</pre>

Then, the Trait-o-matic core XMLRPC server was started:

<pre>
sudo /etc/init.d/trait.sh start
</pre>


h2. Web

CodeIgniter (1.7.1) was downloaded and installed. (Note: web components are compatible with CodeIgniter versions 1.7.0 and 1.7.1; other versions have not been tested.)

<pre>
cd
wget http://codeigniter.com/download.php
unzip CodeIgniter_1.7.1.zip
cd CodeIgniter_1.7.1
sudo cp index.php /var/www
sudo cp -R system /var/www/system
sudo mv -i /var/www/index.html /var/www/index.html.default
</pre>

Then, Trait-o-matic web components were copied into the correct directories:

<pre>
cd
sudo cp -R xwu-trait-o-matic-*/web/errors /var/www
sudo cp -R xwu-trait-o-matic-*/web/media /var/www
sudo cp -R xwu-trait-o-matic-*/web/scripts /var/www
sudo cp -R xwu-trait-o-matic-*/web/statistics /var/www
sudo rm -Rf /var/www/system/application
sudo cp -R xwu-trait-o-matic-*/web/system/application /var/www/system/application
sudo cp xwu-trait-o-matic-*/web/htaccess /var/www/.htaccess
</pre>

Finally, <code>/var/www/system/application/config/config.php</code> was configured with the correct domain name. Additionally, <code>database.php</code> and <code>upload.php</code> were checked and updated with any necessary changes in setting.
