<?php

$config['enable_warehouse_storage'] = file_exists ("/etc/warehouse/warehouse-client.conf") && isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_hgmd'] = isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_download_gff'] = true;
$config['site_url_for_trackback'] = false;
$config['enable_chmod'] = true;
$config['backend_intermediary'] = 'json';

?>