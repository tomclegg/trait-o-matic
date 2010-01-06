<?php

$config['enable_warehouse_storage'] = file_exists ("/etc/warehouse/warehouse-client.conf") && isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_hgmd'] = isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_pharmgkb'] = isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_get_evidence'] = isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_download_gff'] = true;
$config['enable_download_dbsnp'] = true;
$config['enable_download_nssnp'] = true;
$config['enable_download_json'] = true;
$config['site_url_for_trackback'] = false;
$config['enable_chmod'] = true;
$config['backend_intermediary'] = 'json';
$config['enable_browse_shared'] = isset ($_SERVER['PHP_AUTH_USER']);
$config['enable_reprocess_any'] = isset ($_SERVER['PHP_AUTH_USER']);

?>