USE caliban;
truncate `morbidmap`;
LOAD DATA LOCAL INFILE '~/data/morbidmap.txt' INTO TABLE `morbidmap` FIELDS TERMINATED BY '|' LINES TERMINATED BY '\n';
truncate `omim`;
LOAD DATA LOCAL INFILE '~/data/omim.tsv' INTO TABLE `omim` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';
truncate `refflat`;
LOAD DATA LOCAL INFILE '~/data/refFlat.txt' INTO TABLE `refflat` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n' IGNORE 1 LINES;
truncate `snpedia`;
LOAD DATA LOCAL INFILE '~/data/snpedia.tsv' INTO TABLE `snpedia` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';
 
USE dbsnp;
truncate `OmimVarLocusIdSNP`;
LOAD DATA LOCAL INFILE '~/data/OmimVarLocusIdSNP.bcp' INTO TABLE `OmimVarLocusIdSNP` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';
truncate `b129_SNPChrPosOnRef_36_3`;
LOAD DATA LOCAL INFILE '~/data/b129_SNPChrPosOnRef_36_3.bcp' INTO TABLE `b129_SNPChrPosOnRef_36_3` FIELDS TERMINATED BY '\t' LINES TERMINATED BY '\n';
