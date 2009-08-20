#!/usr/bin/python
# Filename: config.py
import os

UPLOAD_DIR = os.getenv('HOME') + "/upload"
DB_HOST = "localhost"

DB_READ_USER = "reader"
DB_READ_PASSWD = "shakespeare"
DB_READ_DATABASE = "caliban"

DB_UPDATE_USER = "updater"
DB_UPDATE_PASSWD = "shakespeare"
DB_UPDATE_DATABASE = "caliban"

DB_WRITE_USER = "writer"
DB_WRITE_PASSWD = "shakespeare"
DB_WRITE_DATABASE = "ariel"

DBSNP_USER = "reader"
DBSNP_PASSWD = "shakespeare"
DBSNP_DATABASE = "dbsnp"

HGMD_USER = "reader"
HGMD_PASSWD = "shakespeare"
HGMD_DATABASE = "hgmd_pro"

GENOTYPE_USER = "updater"
GENOTYPE_PASSWD = "shakespeare"
GENOTYPE_DATABASE = "genotypes"

REFERENCE_GENOME = os.getenv('HOME') + "/trait/hg18.2bit"
