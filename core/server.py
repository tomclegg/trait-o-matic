#!/usr/bin/python
# Filename: server.py

"""
usage: %prog [options]
  -h, --host=STRING: the host on which to listen
  -p, --port=NUMBER: the port on which to listen
  -t, --trackback: invoke the server's trackback function with arguments url, path, kind, request_token (does not start a new server)
"""

# Start an XMLRPC server for Trait-o-matic
# ---
# This code is part of the Trait-o-matic project and is governed by its license.

import base64, hashlib, os, shutil, subprocess, sys, urllib, urllib2, re
from SimpleXMLRPCServer import SimpleXMLRPCServer as xrs
from tempfile import mkstemp
from utils import doc_optparse
from config import UPLOAD_DIR, REFERENCE_GENOME

def trackback(url, params):
	request = urllib2.Request(url, params)
	request.add_header('User-agent', 'Trait-o-matic/20090123 Python')
	request.add_header('Content-type', 'application/x-www-form-urlencoded;charset=utf-8')
	try:
		file = urllib2.urlopen(request)
	except URLError:
		return False
	file.close()
	return True

def main():
	# parse options
	option, args = doc_optparse.parse(__doc__)
	
	# deal with the trackback option
	if option.trackback:
		if len(args) < 4:
			doc_optparse.exit()
		url = args[0]
		path = args[1]
		kind = args[2]
		request_token = args[3]
		params = urllib.urlencode({ 'path': path, 'kind': kind, 'request_token': request_token })            
		trackback(url, params)
		return
	
	# otherwise, figure out the host and port
	host = option.host or "localhost"
	port = int(option.port or 8080)
	
	# create server
	server = xrs((host, port))
	server.register_introspection_functions()
	
	def submit(genotype_file, coverage_file='', username=None, password=None):
		# get genotype file
		r = urllib2.Request(genotype_file)
		if username is not None:
			h = "Basic %s" % base64.encodestring('%s:%s' % (username, password)).strip()
			r.add_header("Authorization", h)
		handle = urllib2.urlopen(r)
		
		# write it to a temporary location while calculating its hash
		s = hashlib.sha1()
		output_handle, output_path = mkstemp()
		for line in handle:
			os.write(output_handle, line)
			s.update(line)
		os.close(output_handle)
		
		# now figure out where to store the file permanently
		permanent_dir = os.path.join(UPLOAD_DIR, s.hexdigest())
		permanent_file = os.path.join(permanent_dir, "genotype.gff")
		if not os.exists(permanent_dir):
			os.makedirs(permanent_dir)
			shutil.move(output_path, permanent_file)
		
		# run the query
		submit_local(permanent_file)
		return s
	server.register_function(submit)
	
	def submit_local(genotype_file, coverage_file='', trackback_url='', request_token=''):
		# execute script
		script_dir = os.path.dirname(sys.argv[0])
		output_dir = os.path.dirname(genotype_file)

		# fetch from warehouse if genotype file is special symlink
		fetch_command = "cat"
		if os.path.islink(genotype_file):
			if re.match('warehouse://.*', os.readlink(genotype_file)):
				fetch_command = "whget"

		# letters refer to scripts; numbers refer to outputs
		args = { 'A': os.path.join(script_dir, "gff_twobit_query.py"),
		         'B': os.path.join(script_dir, "gff_dbsnp_query.py"),
		         'C': os.path.join(script_dir, "gff_nonsynonymous_filter.py"),
		         'D': os.path.join(script_dir, "gff_omim_map.py"),
		         'E': os.path.join(script_dir, "gff_hgmd_map.py"),
		         'F': os.path.join(script_dir, "gff_morbid_map.py"),
		         'G': os.path.join(script_dir, "gff_snpedia_map.py"),
		         'H': os.path.join(script_dir, "json_allele_frequency_query.py"),
		         'Z': os.path.join(script_dir, "server.py"),
		         'in': genotype_file,
			 'fetch': fetch_command,
		         'reference': REFERENCE_GENOME,
		         'url': trackback_url,
		         'token': request_token,
		         '1': os.path.join(output_dir, "genotype.gff"),
		         '2': os.path.join(output_dir, "genotype.dbsnp.gff"),
		         '3': os.path.join(output_dir, "ns.gff"),
		         '4': os.path.join(output_dir, "omim.json"),
		         '5': os.path.join(output_dir, "hgmd.json"),
		         '6': os.path.join(output_dir, "morbid.json"),
		         '7': os.path.join(output_dir, "snpedia.json"),
		         '8': "",
		         '0': os.path.join(output_dir, "README") }
		cmd = '''(
		%(fetch)s '%(in)s' | tee /tmp/gff | python '%(A)s' '%(reference)s' /dev/stdin > '%(1)s'
		python '%(B)s' '%(1)s' > '%(2)s'
		python '%(C)s' '%(2)s' '%(reference)s' > '%(3)s'
		python '%(D)s' '%(3)s' > '%(4)s'
		python '%(E)s' '%(3)s' > '%(5)s'
		python '%(F)s' '%(3)s' > '%(6)s'
		python '%(G)s' '%(2)s' > '%(7)s'
		python '%(H)s' '%(4)s' '%(5)s' '%(6)s' '%(7)s' --in-place
		touch '%(0)s'
		python '%(Z)s' -t '%(url)s' '%(4)s' 'out/omim' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(5)s' 'out/hgmd' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(6)s' 'out/morbid' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(7)s' 'out/snpedia' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(0)s' 'out/readme' '%(token)s'
		)&''' % args
		subprocess.call(cmd, shell=True)
		return output_dir
	server.register_function(submit_local)
	
	def copy_to_warehouse(genotype_file, coverage_file, phenotype_file, trackback_url='', request_token='', recopy=True):
		# execute script
		script_dir = os.path.dirname(sys.argv[0])
		output_dir = os.path.dirname(genotype_file)

		g_locator = _copy_file_to_warehouse (genotype_file, "genotype.gff")
		c_locator = _copy_file_to_warehouse (coverage_file, "coverage")
		p_locator = _copy_file_to_warehouse (phenotype_file, "phenotype.json")
		if (g_locator != None and
		    c_locator != None and
		    p_locator != None):
			return (g_locator, c_locator, p_locator)
		return None
	server.register_function(copy_to_warehouse)

	def _copy_file_to_warehouse (source_file, target_filename=None, trackback_url=None, recopy=True):
		if not source_file:
			return ''

		# if file is special symlink, return link target
		if os.path.islink(source_file):
			if re.match('warehouse://.*', os.readlink(source_file)):
				return os.readlink(source_file)

		# if file has already been copied to warehouse, do not recopy
		if not recopy and os.path.islink(source_file + '-locator'):
			return os.readlink(source_file + '-locator')

		# if copying is required, fork a child process and return now
		if os.fork() > 0:
			os.wait()
			if os.path.islink(source_file + '-locator'):
				return os.readlink(source_file + '-locator')
			return ''

		# double-fork avoids accumulating zombie child processes
		if os.fork() > 0:
			os._exit(0)

		if not target_filename:
			target_filename = os.path.basename (source_file)
		whput = subprocess.Popen(["whput",
					  "--in-manifest",
					  "--use-filename=%s" % target_filename,
					  source_file],
					 stdout=subprocess.PIPE)
		(locator, errors) = whput.communicate()
		ret = whput.returncode
		if ret == None:
			ret = whput.wait
		if ret == 0:
			locator = locator.strip()
			if not os.path.islink(source_file + '-locator'):
				try:
					os.symlink(locator, source_file + '-locator')
				except OSError:
					print >> sys.stderr, 'Ignoring error creating symlink ' + source_file + '-locator'
			if trackback_url:
				subprocess.call("python '%(Z)s' -t '%(url)s' '%(out)s' '%(source)s' '%(token)s'"
						% { 'Z': os.path.join (script_dir, "server.py"),
						    'url': trackback_url,
						    'out': locator,
						    'source': source_file,
						    'token': request_token })
			os._exit(0)
		os._exit(1)
	
	# run the server's main loop
	server.serve_forever()

if __name__ == "__main__":
	main()
