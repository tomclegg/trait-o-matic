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
 inheritance ENUM('unknown','dominant','recessive','other','undefined'),
 impact ENUM('unknown','pathogenic','likely pathogenic','benign','likely benign','protective','likely protective','pharmacogenetic','likely pharmacogenetic','none'),
 dbsnp_id VARCHAR(16),
 overall_frequency_n INT UNSIGNED,
 overall_frequency_d INT UNSIGNED,
 overall_frequency_f FLOAT,
 gwas_max_or FLOAT,
 genome_hits INT UNSIGNED,
 web_hits INT UNSIGNED,
 summary_short TEXT,
 UNIQUE KEY (gene, aa_change)
);

LOAD DATA LOCAL INFILE '$DATA/get-evidence-data.fifo' INTO TABLE \`latest_tmp\` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';

DROP TABLE IF EXISTS latest;
CREATE TABLE IF NOT EXISTS latest LIKE latest_tmp;
LOCK TABLES latest WRITE;
DELETE FROM latest;
INSERT INTO latest SELECT * FROM latest_tmp;
UNLOCK TABLES;

EOF

rm -f get-evidence-data.fifo
mkfifo get-evidence-data.fifo

hostname="`hostname`"
if [ "${hostname%freelogy.org}" = `hostname` ]
then
  host=evidence.personalgenomes.org
else
  host=evidence.oxf.freelogy.org
fi

wget -Oget-evidence-data.fifo http://$host/download/latest.tsv &

mysql -uupdater -p"$MYSQL_PASS" get_evidence <get-evidence-sql.fifo

mv get-evidence.log.0 get-evidence.log.1 || true
mv get-evidence.log get-evidence.log.0 || true
