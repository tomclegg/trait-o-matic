#!/usr/bin/perl

while (<>)
{
    chomp;
    my @F = split ("\t");
    if ($F[2] =~ /^(chr[XY\d]+):(\d+)(?: \((rs\d+)\))?/)
    {
	my $chr = $1;
	my $pos = $2;
	my $rsid = $3;
	my $genotype = "";
	my $gene = "";
	my $amino_acid_change = "";
	if ($F[0] =~ /^([ACGT])(\s.*)?$/)
	{
	    $genotype = $1;
	    if ($F[3] =~ /(\w+):\s*([a-z]{3}\d+[a-z]{3})/i)
	    {
		$gene = $1;
		$amino_acid_change = $2;
	    }
	}
	elsif ($F[0] =~ /^([a-z]{3}\d+[a-z]{3})$/i)
	{
	    $amino_acid_change = $1;
	    if ($F[3] =~ /(\w+):\s*\Q$amino_acid_change\E/)
	    {
		$gene = $1;
	    }
	}
	else
	{
	    warn "skip: $_\n";
	    next;
	}
	my ($pubmedid) = $F[4] =~ /PubMed ID:([\d,]+)/;
	my ($webresource) = $F[4] =~ /Web Resource:(\S+)/;
	print (join ("\t", $chr, $pos, $rsid, $genotype, $gene, $amino_acid_change, $pubmedid, $webresource, @F[3..8]), "\n");
    }
    elsif (/^\s*(\#.*|)$/ || /^(Amino Acid Records|Nucleotide Records)/)
    {
	;
    }
    else
    {
	print STDERR "skip: $_\n" if $ENV{DEBUG};
    }
}

__END__

suggested usage:

echo '
 CREATE DATABASE `pharmgkb` DEFAULT CHARACTER SET ASCII COLLATE ascii_general_ci;
 ' | mysql -uroot -p
(
set -e
set -o pipefail
echo "
 DROP TABLE IF EXISTS pharmgkb.pharmgkb;
 CREATE TABLE pharmgkb.pharmgkb (
 chrom char(5),
 pos int(10),
 rsid char(16),
 genotype char(3),
 gene varchar(32),
 amino_acid_change varchar(12),
 pubmed_id varchar(64),
 webresource varchar(255),
 name char(48),
 evidence text,
 annotation text,
 genes text,
 drugs text,
 drugclasses text,
 diseases text,
 unique(rsid,genotype,gene,amino_acid_change,annotation(177)),
 index(rsid),
 index(gene,amino_acid_change)
 );
 " | mysql -uroot -p
cat ~/Variant_annotation_filtered_allele_flipped_drugs.txt | ./pharmgkb_stage2.pl > ~/pharmgkb_import.tmp
echo "
 DELETE FROM pharmgkb;
 LOAD DATA LOCAL INFILE '~/pharmgkb_import.tmp'
 INTO TABLE pharmgkb
 FIELDS TERMINATED BY '\t'
 LINES TERMINATED BY '\n';
 " | mysql -uupdater -p pharmgkb
)
