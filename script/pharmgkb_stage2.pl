#!/usr/bin/perl

while (<>)
{
    if ($. == 1)
    {
	/^Extracted Allele\tPosition \(RSID\)\tName\(s\)\tEvidence\tAnnotation\tGenes\tDrugs\tDrug Classes\tDiseases\n$/
	    or die "Headings not as expected, aborting";
	next;
    }
    chomp;
    my @F = split ("\t");
    if ($F[1] =~ /^(chr[XY\d]+):(\d+)(?: \((rs\d+)\))?/)
    {
	my $chr = $1;
	my $pos = $2;
	my $rsid = $3;
	my $genotype = "";
	if ($F[0] =~ /^Risk(?:\/trait)? Allele[:=] ?(?:rs\d+-)?([ACGT])$/i ||
	    $F[0] =~ /^Allele ([ACGT])$/i ||
	    $F[0] =~ /^([ACGT])[- ]Allele$/i ||
	    $F[0] =~ /^([ACGT][;\/]?[ACGT]) genotype$/i ||
	    0)
	{
	    $genotype = $1;
	    $genotype =~ s/[^ACGT]//gi;
	    $genotype =~ s/(.)(.)/$1 lt $2 ? "$1;$2" : "$2;$1"/e || ($genotype = "$genotype;$genotype");
	    $genotype =~ tr/a-z/A-Z/;
	}
	my ($pubmedid) = $F[3] =~ /PubMed ID:([\d,]+)/;
	my ($webresource) = $F[3] =~ /Web Resource:(\S+)/;
	print (join ("\t", $chr, $pos, $rsid, $genotype, $pubmedid, $webresource, @F[2..8]), "\n");
    }
    elsif (/^\s*\#/)
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
 pubmed_id varchar(64),
 webresource varchar(255),
 name char(16),
 evidence text,
 annotation text,
 genes text,
 drugs text,
 drugclasses text,
 diseases text,
 unique(chrom,pos,rsid,genotype,annotation(197)),
 index(chrom,pos)
 );
 " | mysql -uroot -p
cat ~/var.csv | ./pharmgkb_stage2.pl > ~/pharmgkb_import.tmp
echo "
 DELETE FROM pharmgkb;
 LOAD DATA LOCAL INFILE '~/pharmgkb_import.tmp'
 INTO TABLE pharmgkb
 FIELDS TERMINATED BY '\t'
 LINES TERMINATED BY '\n';
 " | mysql -uupdater -p pharmgkb
)
