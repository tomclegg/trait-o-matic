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

CREATE TABLE IF NOT EXISTS latest_tmp (
 gene VARCHAR(32),
 aa_change VARCHAR(16),
 inheritance ENUM('unknown','dominant','recessive'),
 impact ENUM('unknown','pathogenic','putative pathogenic','benign','putative benign'),
 summary_short TEXT,
 UNIQUE KEY (gene, aa_change)
);
LOCK TABLES latest_tmp WRITE;
DELETE FROM latest_tmp;
LOAD DATA LOCAL INFILE '$DATA/get-evidence-data.fifo' INTO TABLE \`latest_tmp\` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';
UNLOCK TABLES;

CREATE TABLE IF NOT EXISTS latest LIKE latest_tmp;
LOCK TABLES latest_tmp READ, latest WRITE;
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
