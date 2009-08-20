<?php
// always expired, always modified
header("Expires: Sat, 05 Nov 2005 00:00:00 GMT");
header("Last-Modified: " . gmdate("D, d M Y H:i:s") . " GMT");

// HTTP/1.1
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Cache-Control: post-check=0, pre-check=0", false);

// HTTP/1.0
header("Pragma: no-cache");
header("Content-type: text/plain");
?>
{
<?php
	$first_source = 1;
foreach (array('omim' => 'OMIM', 'snpedia' => 'SNPedia', 'hgmd' => 'HGMD', 'morbid' => 'Other hypotheses') as $k => $v):
	if (!$first_source) { echo " ,\n"; }
	$first_source = 0;
		echo " \"", $k, "\": ["
?>

<?php
	$first_phenotype = 1;
foreach ($phenotypes[$k] as $o):
	if (!$first_phenotype) { echo "   ,\n"; }
	$first_phenotype = 0;
?>
   {
<?php
// these variables are re-used; don't let previous values taint the output
unset($maf, $taf, $minor, $rare, $freq_unknown, $url);

// last-minute allele frequency calculations; for now, we give every
// variant the benefit of the doubt and use the lowest allele frequency
// for any population in which the subject claims to have ancestry
if (array_key_exists('maf', $o) && $o['maf'] != "N/A")
{
	$mafs = array_intersect_key(array_change_key_case(get_object_vars($o['maf']), CASE_LOWER),
	                            array_flip($phenotypes['ancestry']));
	if (count($mafs))
	{
		$freq_unknown = FALSE;
		$maf = min($mafs);
		$minor = $maf < 0.5;
		$rare = $maf < 0.05;
	}
	else
	{
		$freq_unknown = TRUE;
	}
}
else
{
	$freq_unknown = TRUE;
}

// trait allele frequencies are used over maf values, where available
if (array_key_exists('taf', $o) && $o['taf'] != "N/A")
{
	$tafs = array_intersect_key(array_change_key_case(get_object_vars($o['taf']), CASE_LOWER),
	                            array_flip($phenotypes['ancestry']));
	if (count($tafs))
	{
		$taf = min($tafs);
		$minor = $taf < 0.5;
		$rare = $taf < 0.05;
	}
}

// last-minute presentational corrections
// for this we need the chromosome name minus the "chr" prefix
$chromosome_without_prefix = str_replace('chr', '', $o['chromosome']);

// format genotypes: snpedia gives actual semicolon-separated genotypes;
// others give only a list of alleles--we treat these differently
if (strpos($o['genotype'], ';') !== FALSE)
{
	$o['genotype'] = str_replace(';', '/', $o['genotype']);
	if (!(is_numeric($chromosome_without_prefix) ||
	  ($chromosome_without_prefix == 'X' && $phenotypes['sex'] == 'female')))
	{
		$alleles = array_unique(explode('/', $o['genotype']));
		if (count($alleles) == 1)
			$o['genotype'] = $alleles[0];
	}
}
else if (is_numeric($chromosome_without_prefix) ||
  ($chromosome_without_prefix == 'X' && $phenotypes['sex'] == 'female'))
{
	if (strpos($o['genotype'], '/') === FALSE)
		$o['genotype'] = $o['genotype'].'/'.$o['genotype'];
}

$v = preg_split('/\t/', $o['variant']);

// format reference links
$references = explode(',', $o['reference']);
//TODO: do something about showing more than the first reference
//TODO: do something about LSDBs referenced in HGMD
$reference = explode(':', $references[0]);
switch ($reference[0])
{
case 'dbsnp':
	$article_id = $reference[1];
	$url = "http://www.snpedia.com/index.php/{$article_id}";
	break;
case 'omim':
	$allele_id = explode('.', $reference[1]);
	$article_id = $allele_id[0];
	$url = "http://www.ncbi.nlm.nih.gov/entrez/dispomim.cgi?id={$article_id}";
	break;
case 'pmid':
	$pmid = $reference[1];
	$url = "http://www.ncbi.nlm.nih.gov/pubmed/{$pmid}";
	break;
}
?>
    "frequency": <?php if ($freq_unknown): ?>"unknown"<?php elseif ($rare): ?>"rare"<?php elseif ($minor): ?>"minor"<?php else: ?>"major"<?php endif; ?>,
    "coordinates": "<?php echo $o['chromosome'].':'.$o['coordinates']; ?>",
    "gene": <?php if (array_key_exists('gene', $o) && array_key_exists('amino_acid_change', $o)): ?>"<?php echo $o['gene']; ?>",
    "gene_change": "<?php echo $o['amino_acid_change']; ?>"<?php else: ?>"not-computed"<?php endif; ?>,
    "genotype": "<?php echo $o['genotype']; ?>",
<?php if (array_key_exists('trait_allele', $o)): ?>    "trait_allele": "<?php echo $o['trait_allele']; ?>"<?php endif; ?>,
    "url": "<?php echo $url; ?>",
    "phenotype": "<?php echo $o['phenotype']; ?>"
<?php if (array_key_exists('score', $o)): ?>
    ,
    "score": <?php echo $o['score']; ?>
<?php endif; ?>
   }
<?php endforeach; ?>
 ]
<?php endforeach; ?>
}
