<?php
// always expired, always modified
header("Expires: Sat, 05 Nov 2005 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD HTML 4.01//EN" "http://www.w3.org/TR/html4/strict.dtd">
<html>
<head>
	<title>Trait-o-matic</title>
	<link rel="stylesheet" media="screen" type="text/css" href="/media/styles.css">
	<link rel="stylesheet" media="screen" type="text/css" href="/media/index.css">
	<!--[if lte IE 7]><link rel="stylesheet" media="screen" type="text/css" href="/media/styles-ie.css"><![endif]-->
	<!--[if lte IE 7]><link rel="stylesheet" media="screen" type="text/css" href="/media/index-ie.css"><![endif]-->
	<!--[if IE 8]><link rel="stylesheet" media="screen" type="text/css" href="/media/styles-ie8.css"><![endif]-->
	<link rel="stylesheet" media="only screen and (max-device-width: 480px)" type="text/css" href="/media/styles-iphone.css">
	<link rel="stylesheet" media="only screen and (max-device-width: 480px)" type="text/css" href="/media/index-iphone.css">
	<meta name="viewport" content="user-scalable=no,width=device-width">
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.1/prototype.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8.3/effects.js"></script>
	<script type="text/javascript" src="/scripts/glider.js"></script>
	<script type="text/javascript" src="/scripts/index.js"></script>
	<script type="text/javascript" src="/scripts/genes.js"></script>
	<script type="text/javascript" src="/scripts/sortable.js"></script>
	<script type="text/javascript" src="/scripts/toggle.js"></script>
	<script type="text/javascript" src="/scripts/legend.js"></script>
	<script type="text/javascript" src="/scripts/results.js"></script>
</head>
<body>
<?php
// show header only if we're not submitting into an iframe
if (!isset($asynchronous) || !$asynchronous):
?>
	<div id="head"><div>
		<div id="logotype"><a href="/"><img src="/media/logotype.gif" width="158" height="36" alt="Trait-o-matic"></a></div>
		<div id="menu">
			<p>
				<span class="description"><em>See also:</em></span>
				<span class="link"><a href="https://trac.scalablecomputingexperts.com/wiki/Doc/Trait-o-matic">Project home</a></span>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
				<span class="link"><a href="/docs/install">How to install</a></span>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
				<span class="link"><a href="/docs/source">Source code</a></span>
			</p>
		</div>
	</div></div>
	<div id="subhead"><div>
		<h2><span>Find and classify phenotypic correlations for variations in whole genomes</span></h2>
		<div id="submenu">
			<p>
<?php
$first=1;
$tabs = array ("/ View Samples",
	       "/query/ Submit Query",
	       "/results/ Retrieve Results");
$this->config->load('trait-o-matic');
if ($this->config->item('enable_browse_shared')) {
  array_splice($tabs, 1, 0, "/browse/shared_data/ View Shared Data");
}
foreach ($tabs as $tab) {
  if (ereg ("([^ ]+) (.*)", $tab, $regs)) {
    if (!$first) {
?>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
<?php
    }
    $first=0;
    if (isset($top_current_tab) && $regs[1] == $top_current_tab) {
?>
				<span class="current"><?=$regs[2]?></span>
<?php
    }
    else {
?>
				<span class="link"><a href="<?=$regs[1]?>"><?=$regs[2]?></a></span>
<?php
    }
  }
}
?>
			</p>
		</div>
	</div></div>
<?php
endif;
?>
