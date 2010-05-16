#!/bin/bash

set -e

. "$CONFIG/config.sh"
cd "$DATA"
MYSQL_PASS=$(cat $CONFIG/dbpassword)

exec 2>>get-evidence.log

flock --exclusive --nonblock 2

rm -f get-evidence-sql.fifo
mkfifo get-evidence-sql.fifo
cat >get-evidence-sql.fifo <<EOF &

CREATE TEMPORARY TABLE latest_tmp (
 gene VARCHAR(32),
 aa_change VARCHAR(16),
 aa_change_short VARCHAR(16),
 rsid INT UNSIGNED,
 inheritance ENUM('unknown','dominant','recessive','other','undefined'),
 impact VARCHAR(32),
 qualified_impact VARCHAR(64),
 dbsnp_id VARCHAR(16),
 overall_frequency_n INT UNSIGNED,
 overall_frequency_d INT UNSIGNED,
 overall_frequency_f FLOAT,
 gwas_max_or FLOAT,
 genome_hits INT UNSIGNED,
 web_hits INT UNSIGNED,
 certainty CHAR(2),
 summary_short TEXT,
 KEY (gene, aa_change),
 KEY (rsid)
);

LOAD DATA LOCAL INFILE '$DATA/get-evidence-data.fifo' INTO TABLE \`latest_tmp\` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';

UPDATE latest_tmp SET impact = CONCAT('likely ',impact) WHERE certainty=1 AND impact IN ('benign','pharmacogenetic','pathogenic','protective');
UPDATE latest_tmp SET impact = CONCAT('uncertain ',impact) WHERE certainty=0 AND impact IN ('benign','pharmacogenetic','pathogenic','protective');

DROP TABLE IF EXISTS latest;
CREATE TABLE IF NOT EXISTS latest LIKE latest_tmp;
LOCK TABLES latest WRITE;
DELETE FROM latest;
INSERT INTO latest SELECT * FROM latest_tmp;
UNLOCK TABLES;

EOF

rm -f get-evidence-preprocess.pl.* || true
cat >get-evidence-preprocess.pl.$$ <<'EOF'

$_ = <>;
chomp;
$i = 0;
for (split "\t") {
  $fieldpos{$_} = $i;
  ++$i;
}
@fieldlist = map { exists $fieldpos{$_} ? $fieldpos{$_} : -1 } qw(gene aa_change aa_change_short rsid inheritance impact qualified_impact dbsnp_id overall_frequency_n overall_frequency_d overall_frequency max_or_or n_genomes n_web_hits certainty summary_short);
while (<>) {
  chomp;
  @F = split "\t";
  push @F, "";
  print (join ("\t", @F[@fieldlist]), "\n");
}

EOF
mv get-evidence-preprocess.pl.$$ get-evidence-preprocess.pl

rm -f get-evidence-data.fifo
mkfifo get-evidence-data.fifo

hostname="`hostname`"
if [ "${hostname%freelogy.org}" = `hostname` ]
then
  host=evidence.personalgenomes.org
else
  host=evidence.oxf.freelogy.org
fi
if [ ! -z "$GET_EVIDENCE_HOST" ]
then
  host="$GET_EVIDENCE_HOST"
fi

wget -O- http://$host/download/latest/flat/latest-flat.tsv | tee get-evidence-latest-flat.tsv | perl get-evidence-preprocess.pl > get-evidence-data.fifo &

 mysql -uupdater -p"$MYSQL_PASS" get_evidence <get-evidence-sql.fifo

mv get-evidence.log.0 get-evidence.log.1 || true
mv get-evidence.log get-evidence.log.0 || true
