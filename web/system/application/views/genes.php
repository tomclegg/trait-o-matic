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
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/prototype/1.6.0.2/prototype.js"></script>
	<script type="text/javascript" src="http://ajax.googleapis.com/ajax/libs/scriptaculous/1.8.1/effects.js"></script>
	<script type="text/javascript" src="/scripts/genes.js"></script>
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
				<span class="link"><a href="/docs/">Documentation</a></span>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
				<span class="link"><a href="http://github.com/xwu/trait-o-matic/tree">Code Repository</a></span>
			</p>
		</div>
	</div></div>
	<div id="subhead"><div>
		<h2><span>Find and classify phenotypic correlations for variations in whole genomes</span></h2>
		<div id="submenu">
			<p>
				<span class="link"><a href="/">View Samples</a></span>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
				<span class="current">Submit Query</span>
				<span class="bullet"> &nbsp;&bull;&nbsp; </span>
				<span class="link"><a href="/results/">Retrieve Results</a></span>
			</p>
		</div>
	</div></div>
<?php
endif;
?>
	<div id="body"><div>
		<div id="main">
			<div class="two-column">
				<div class="column">
					<h3>1. Genes</h3>
					<p>By submitting these data, you acknowledge that you possess all rights thereto necessary for submission. Further, you acknowledge your compliance with our <a href="/terms/">Terms of Service</a>.</p>
				</div>
				<div class="last column">
<?php if (isset($error)): ?>
					<div class="error"><?php foreach ($error as $e): ?><div><?php echo $e; ?></div><?php endforeach; ?></div>
<?php endif; ?>
<?php $this->config->load('trait-o-matic');
      if ($this->config->item('enable_warehouse_storage')) {
 ?>

<form id="datasource-form">
<input type="radio" name="datasource" value="browser" id="datasource-browser" onchange="genes_forms_showhide()" checked /> Upload files from your browser
<br />
<input type="radio" name="datasource" value="warehouse" id="datasource-warehouse" onchange="genes_forms_showhide()" /> Use shared data from the warehouse
</form>

<?php } ?>
					<form enctype="multipart/form-data" name="gene-form" id="gene-form" method="POST" action="/query/">
						<div class="wrapper">
							<p><label class="label">Genotype<br>
							<input type="file" class="file" name="genotype" id="genotype"></label></p>
							<p><label class="label">Coverage<span class="description"> (optional)</span><br>
							<input type="file" class="file" name="coverage" id="coverage"></label></p>
						</div>
						<p class="submit"><span class="label"></span><input type="submit" name="submit-gene-form" id="submit-gene-form" value="Next &raquo;"></p>
					</form>
<?php if ($this->config->item('enable_warehouse_storage')) { ?>
					<form name="from-warehouse-form" id="from-warehouse-form" method="POST" action="/query/" style="display: none;">
						<div class="wrapper">
							<p><label class="label">Genotype<br>
							<input type="text" class="wide text" name="genotype_locator" id="genotype" value="<?= htmlspecialchars(isset($genotype_locator) ? $genotype_locator : "warehouse:///") ?>"></label></p>
							<p><label class="label">Coverage<span class="description"> (optional)</span><br>
							<input type="text" class="wide text" name="coverage_locator" id="coverage" value="<?= htmlspecialchars(isset($coverage_locator) ? $coverage_locator : "warehouse:///") ?>"></label></p>
<!--
							<p><label class="label">Phenotype/profile<span class="description"> (optional)</span><br>
							<input type="text" class="wide text" name="phenotype_locator" id="phenotype" value="<?= htmlspecialchars(isset($phenotype_locator) ? $phenotype_locator : "warehouse:///") ?>"></label></p>
-->
						</div>
						<p class="submit"><span class="label"></span><input type="submit" name="submit-from-warehouse-form" id="submit-from-warehouse-form" value="Next &raquo;"></p>
					</form>
<?php } ?>
				</div>
			</div>
		</div>
	</div></div>
<?php
// show footer only if we're not submitting into an iframe
if (!isset($asynchronous) || !$asynchronous):
?>
	<div id="foot"><div>
		<div id="copyright">
			<p>
				<span>Copyright &copy; MMIX President and Fellows of Harvard College<br>[{elapsed_time} s]</span>
			</p>
		</div>
	</div></div>
<?php
// script to copy iframe contents into parent document
else:
?>
<script type="text/javascript">
var node = top.document.getElementById("main");
$(node).update($("main").innerHTML);
Element.extend(top.document).fire("ajax:update");
</script>
<?php
endif;
?>
</body>
</html>