#!/bin/bash

set -e
set -o pipefail

. "$(echo "$0" | sed -e 's/[^\/]*$//')defaults.sh"

echo It is safe to restart this script - it keeps track of progress

mkdir -p $DATA
cd $DATA

cp $SCRIPT_DIR/load.sql $DATA/load.sql

try_whget ()
{
  manifest_name="$1"
  dest_dir="$2"
  manifest_hash=$(wh manifest lookup name="$manifest_name")
  if [ $? = 0 ]
  then
    mkdir -p $dest_dir
    echo "Retrieving $manifest_name -- $manifest_hash"
    if whget -r "$manifest_hash/" "$dest_dir/"
    then
      return 0
    fi
  fi
  return 1
}

# Use "continue" flag on wget, so that we can just rerun this script and it will do the right thing
WGET='wget -c -nv'
GUNZIP='gunzip -f'
MYSQL_PASS=$(cat $CONFIG/dbpassword)

if [ ! -f hg18.2bit.stamp ]
then
  try_whget /Trait-o-matic/data/hg18.2bit . || \
  $WGET ftp://hgdownload.cse.ucsc.edu/goldenPath/hg18/bigZips/hg18.2bit
  touch hg18.2bit.stamp
fi
 
cd $DATA
 
# dbSNP (only two tables)
if [ ! -f dbSNP.stamp ]; then
  try_whget /Trait-o-matic/data/OmimVarLocusIdSNP . || \
  $WGET ftp://ftp.ncbi.nih.gov:21/snp/organisms/human_9606/database/organism_data/OmimVarLocusIdSNP.bcp.gz
  try_whget /Trait-o-matic/data/b129_SNPChrPosOnRef_36_3.bcp.gz . || \
  $WGET ftp://ftp.ncbi.nih.gov:21/snp/organisms/human_9606/database/organism_data/b129/b129_SNPChrPosOnRef_36_3.bcp.gz
  touch dbSNP.stamp
fi
 
# HapMap
if [ ! -f hapmap.stamp ]; then
  try_whget /Trait-o-matic/data/hapmap ftp.hapmap.org || \
  $WGET -r -l1 --accept allele\* --no-parent http://ftp.hapmap.org/frequencies/2009-02_phaseII+III/forward/non-redundant/
  rm -f ftp.hapmap.org/frequencies/2009-02_phaseII+III/forward/non-redundant/genotype* || true
  touch hapmap.stamp
fi
 
# morbidmap/OMIM
if [ ! -e morbidmap.txt ]; then
  try_whget  /Trait-o-matic/data/morbidmap . || \
  $WGET ftp://ftp.ncbi.nih.gov/repository/OMIM/morbidmap
  ln -sf morbidmap morbidmap.txt
fi
 
# OMIM
if [ ! -f omim.stamp ]; then
  try_whget  /Trait-o-matic/data/omim.txt.Z . || \
  $WGET ftp://ftp.ncbi.nih.gov/repository/OMIM/omim.txt.Z
  $GUNZIP -c omim.txt.Z >omim.txt
  python $CORE/omim_print_variants.py omim.txt > omim.tsv
  rm omim.txt
  touch omim.stamp
fi
 
# refFlat/UCSC
if [ ! -f refFlat.stamp ]; then
  try_whget  /Trait-o-matic/data/refFlat.txt.gz . || \
  $WGET http://hgdownload.cse.ucsc.edu/goldenPath/hg18/database/refFlat.txt.gz 
  $GUNZIP -c refFlat.txt.gz > refFlat.txt
  touch refFlat.stamp
fi
 
# snp/UCSC
if [ ! -f snp129.stamp ]; then
  try_whget  /Trait-o-matic/data/snp129 . || \
  $WGET http://hgdownload.cse.ucsc.edu/goldenPath/hg18/database/snp129.txt.gz
  $GUNZIP -c snp129.txt.gz | mysql -uupdater -p"$MYSQL_PASS" -e "USE caliban; LOAD DATA LOCAL INFILE '/dev/stdin' INTO TABLE snp129 FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';"
  touch snp129.stamp
  rm -f snp129.txt
fi
 
# SNPedia
if [ ! -f snpedia.stamp ]
then
  try_whget  /Trait-o-matic/data/snpedia.txt . || \
  (
    echo Downloading snpedia data, this could take 45 minutes
    python $CORE/snpedia.py > snpedia.txt
  )
  touch snpedia.stamp
fi
# -- clean up some descriptive text
sed 's/ (None)//' < snpedia.txt \
 | awk 'BEGIN { FS = "\t" }; ($5 !~ /(^normal)|(^\?)/ || $5 ~ /;/)' \
 > snpedia.filtered.txt
python $CORE/snpedia_print_genotypes.py snpedia.filtered.txt > snpedia.tsv

echo Loading morbidmap, omim, refFlat, snpedia, dbSNP data into MySQL
if [ ! -f load.stamp ]; then
  $GUNZIP < OmimVarLocusIdSNP.bcp.gz > OmimVarLocusIdSNP.bcp
  rm -f b129.fifo
  mkfifo b129.fifo
  $GUNZIP < b129_SNPChrPosOnRef_36_3.bcp.gz > b129.fifo &
  mysql -uupdater -p$MYSQL_PASS < $DATA/load.sql
  rm -f b129.fifo
  touch load.stamp
fi

# HapMap
echo Loading HapMap data, this could take hours...
for file in ftp.hapmap.org/frequencies/2009-02_phaseII+III/forward/non-redundant/allele_* ; do
  cat=cat
  if [ "${file##*.}" = bz2 ]; then cat="bzip2 -cd"; fi
  if [ "${file##*.}" = gz ]; then cat="gzip -cd"; fi
  if [ ! -f $file.stamp ]; then
    $cat $file | python $CORE/hapmap_load_database.py && touch $file.stamp
  fi
done

