#!/usr/bin/python
# Filename: gff_get-evidence_map.py

"""
usage: %prog gff_file
"""

# Output GET-Evidence information in JSON format
# ---
# This code is part of the Trait-o-matic project and is governed by its license.

import os, string, sys, re
import MySQLdb, MySQLdb.cursors
import simplejson as json
from codon import codon_321, codon_123
from copy import copy
from utils import gff
from utils.biopython_utils import reverse_complement
from utils.bitset import *
from config import DB_HOST, GETEVIDENCE_USER, GETEVIDENCE_PASSWD, GETEVIDENCE_DATABASE

query = '''
SELECT inheritance, impact, summary_short
FROM latest
WHERE gene=%s AND aa_change=%s
 AND impact NOT IN ('unknown', 'none', 'not reviewed')
 AND LENGTH(summary_short) > 0
'''

def main():
	# return if we don't have the correct arguments
	if len(sys.argv) < 2:
		raise SystemExit(__doc__.replace("%prog", sys.argv[0]))
	
	# first, try to connect to the databases
	try:
		location_connection = MySQLdb.connect(cursorclass=MySQLdb.cursors.SSCursor, host=DB_HOST, user=GETEVIDENCE_USER, passwd=GETEVIDENCE_PASSWD, db=GETEVIDENCE_DATABASE)
		location_cursor = location_connection.cursor()
		connection = MySQLdb.connect(host=DB_HOST, user=GETEVIDENCE_USER, passwd=GETEVIDENCE_PASSWD, db=GETEVIDENCE_DATABASE)
		cursor = connection.cursor()
	except MySQLdb.OperationalError, message:
		print "Error %d while connecting to database: %s" % (message[0], message[1])
		sys.exit()
	
	# doing this intersect operation speeds up our task significantly for full genomes
	gff_file = gff.input(sys.argv[1])	
	for record in gff_file:
		# lightly parse to find the alleles and rs number
		alleles = record.attributes["alleles"].strip("\"").split("/")
		ref_allele = record.attributes["ref_allele"].strip("\"")
		xrefs = ()
		try:
			xrefs = record.attributes["db_xref"].strip("\"").split(",")
		except KeyError:
			try:
				xrefs = record.attributes["Dbxref"].strip("\"").split(",")
			except KeyError:
				pass
		for x in xrefs:
			if x.startswith("dbsnp:rs"):
				rs = x.replace("dbsnp:", "")
				break

		# we wouldn't know what to do with this, so pass it up for now
		if len(alleles) > 2:
			continue

		# create the genotype string from the given alleles
		#TODO: do something about the Y chromosome
		if len(alleles) == 1:
			alleles = (alleles[0], alleles[0])
			genotype = alleles[0]
		else:
			genotype = '/'.join(sorted(alleles))

		for gene_acid_base in record.attributes["amino_acid"].split("/"):

			# get amino acid change
			x = gene_acid_base.split(" ",1)
			gene = x[0]
			amino_acid_change_and_position = x[1]

			# convert to long form

			acid_change = re.sub(r' .*',r'', amino_acid_change_and_position)
			acid_change = re.sub(r'[A-Z]', lambda x: codon_123(x.group(0)), acid_change)
			acid_change = re.sub(r'TERM', 'Stop', acid_change)

			# query the database
			cursor.execute(query, (gene, acid_change))
			data = cursor.fetchall()

			# if this gene/AA change caused a hit, stop here and report it
			if cursor.rowcount > 0:
				break

		if cursor.rowcount > 0:
			for d in data:
				inheritance = d[0]
				impact = d[1]
				notes = d[2] + " ("
				if impact == "not reviewed" or impact == "none" or impact == "unknown":
					notes = notes + "impact not reviewed"
				else:
					notes = notes + impact
				if inheritance == "dominant" or inheritance == "recessive":
					notes = notes + ", " + inheritance + ")"
				else:
					notes = notes + ", inheritance pattern " + inheritance + ")"

				# format for output
				if record.start == record.end:
					coordinates = str(record.start)
				else:
					coordinates = str(record.start) + "-" + str(record.end)

				reference = "http://evidence.personalgenomes.org/" + gene + "-" + acid_change

				output = {
					"chromosome": record.seqname,
					"coordinates": coordinates,
					"gene": gene,
					"amino_acid_change": amino_acid_change_and_position,
					"amino_acid": record.attributes["amino_acid"],
					"genotype": genotype,
					"variant": str(record),
					"phenotype": notes,
					"reference": reference
				}
				print json.dumps(output)
	
	# close database cursor and connection
	cursor.close()
	connection.close()
	location_cursor.close()
	location_connection.close()

if __name__ == "__main__":
	main()
