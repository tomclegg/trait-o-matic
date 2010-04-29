<?php
if (!isset($top_current_tab))
  $top_current_tab = "/query/";
require ('top.php');
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