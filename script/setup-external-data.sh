#!/bin/sh

set -e
set -x

echo It is safe to restart this script - it keeps track of progress

# Use "continue" flag on wget, so that we can just rerun this script and it will do the right thing
WGET='wget -c -nv'
GUNZIP='gunzip -f'
DEST=$HOME/trait
DATA=$HOME/data
CORE=$HOME/trait-o-matic/core
MYSQL_PASS=shakespeare

mkdir -p $DEST
mkdir -p $DATA

cd $DEST

$WGET ftp://hgdownload.cse.ucsc.edu/goldenPath/hg18/bigZips/hg18.2bit
 
cd $DATA
 
# dbSNP (only two tables)
if [ ! -f dbSNP.stamp ]; then
  $WGET ftp://ftp.ncbi.nih.gov:21/snp/organisms/human_9606/database/organism_data/OmimVarLocusIdSNP.bcp.gz
  $WGET ftp://ftp.ncbi.nih.gov:21/snp/organisms/human_9606/database/organism_data/b129/b129_SNPChrPosOnRef_36_3.bcp.gz
  $GUNZIP -c OmimVarLocusIdSNP.bcp.gz > OmimVarLocusIdSNP.bcp
  $GUNZIP -c b129_SNPChrPosOnRef_36_3.bcp.gz > b129_SNPChrPosOnRef_36_3.bcp
  touch dbSNP.stamp
fi
 
# HapMap
#XXX $WGET -r -l1 --accept allele\* --no-parent http://ftp.hapmap.org/frequencies/2009-02_phaseII+III/forward/non-redundant/
 
# morbidmap/OMIM
$WGET ftp://ftp.ncbi.nih.gov/repository/OMIM/morbidmap
ln -sf morbidmap morbidmap.txt
 
# OMIM
if [ ! -f omim.stamp ]; then
  $WGET ftp://ftp.ncbi.nih.gov/repository/OMIM/omim.txt.Z
  $GUNZIP -c omim.txt.Z > omim.txt
  python $CORE/omim_print_variants.py omim.txt > omim.tsv && touch omim.stamp
  rm omim.txt
fi
 
# refFlat/UCSC
$WGET http://hgdownload.cse.ucsc.edu/goldenPath/hg18/database/refFlat.txt.gz 
$GUNZIP -c refFlat.txt.gz > refFlat.txt
 
# snp/UCSC
if [ ! -f snp129.stamp ]; then
  $WGET http://hgdownload.cse.ucsc.edu/goldenPath/hg18/database/snp129.txt.gz
  $GUNZIP -c snp129.txt.gz > snp129.txt
  mysql -uupdater -p$MYSQL_PASS -e "USE caliban; LOAD DATA LOCAL INFILE '~/data/snp129.txt' INTO TABLE snp129 FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';" && touch snp129.stamp
  rm -f snp129.txt
fi
 
# SNPedia
echo Downloading snpedia data, this could take 45 minutes
[ ! -f snpedia.stamp ] && python $CORE/snpedia.py > snpedia.txt && touch snpedia.stamp
# -- clean up some descriptive text
sed -i'.bak' 's/ (None)//' snpedia.txt
awk 'BEGIN { FS = "\t" }; ($5 !~ /(^normal)|(^\?)/ || $5 ~ /;/)' snpedia.txt > snpedia.filtered.txt
python $CORE/snpedia_print_genotypes.py snpedia.filtered.txt > snpedia.tsv

echo Loading other data
if [ ! -f load.stamp ]; then
  mysql -uupdater -p$MYSQL_PASS < $CORE/../script/load.sql && touch load.stamp
fi

# HapMap
echo Loading HapMap data, this could take hours...
for file in ftp.hapmap.org/frequencies/2009-02_phaseII+III/forward/non-redundant/allele_*.gz ; do
  if [ ! -f $file.stamp ]; then
    zcat $file | python $CORE/hapmap_load_database.py && touch $file.stamp
  fi
done

