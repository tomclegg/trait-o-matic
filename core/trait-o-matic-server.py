#!/usr/bin/python
# Filename: server.py

"""
usage: %prog [options]
  --pidfile=PATH: location of pid file
  --stderr=PATH: location of log file
  -h, --host=STRING: the host on which to listen
  -p, --port=NUMBER: the port on which to listen
  -t, --trackback: invoke the server's trackback function with arguments url, path, kind, request_token (does not start a new server)
"""

# Start an XMLRPC server for Trait-o-matic
# ---
# This code is part of the Trait-o-matic project and is governed by its license.

import base64, hashlib, os, shutil, subprocess, sys, urllib, urllib2, re, warehouse
from SimpleXMLRPCServer import SimpleXMLRPCServer as xrs
from tempfile import mkstemp
from utils import doc_optparse
from config import UPLOAD_DIR, REFERENCE_GENOME

script_dir = os.path.dirname(sys.argv[0])

def trackback(url, params):
	request = urllib2.Request(url, params)
	request.add_header('User-agent', 'Trait-o-matic/20090123 Python')
	request.add_header('Content-type', 'application/x-www-form-urlencoded;charset=utf-8')
	try:
		file = urllib2.urlopen(request)
	except urllib2.HTTPError, detail:
		print "Unexpected http error:", detail, "for url", url
		return False
	except:
		print "Unexpected error:", sys.exc_info()[0], "for url", url
		return False
	file.close()
	return True

def main():
	# parse options
	option, args = doc_optparse.parse(__doc__)
	
	if option.stderr:
		sysin = sys.stdin.fileno()
		sysout = sys.stdout.fileno()
		syserr = sys.stderr.fileno()
		newout = file(option.stderr,'a+',0)
		sys.stderr.flush()
		sys.stdout.flush()
		os.close(sysin)
		os.close(sysout)
		os.dup2(newout.fileno(), sysout)
		os.close(sys.stderr.fileno())
		os.dup2(newout.fileno(), syserr)

	if option.pidfile:
		file(option.pidfile,'w+').write("%d\n" % os.getpid())

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
		if not os.path.exists(permanent_dir):
			os.makedirs(permanent_dir)
			shutil.copy(output_path, permanent_file)
		
		# run the query
		submit_local(permanent_file)
		return s
	server.register_function(submit)
	
	def submit_local(genotype_file, coverage_file='', trackback_url='', request_token=''):
		# create output dir
		input_dir = os.path.dirname(genotype_file)
		output_dir = input_dir + "-out"
		try:
			if not os.path.exists(output_dir):
				os.makedirs(output_dir)
		except:
			print "Unexpected error:", sys.exc_info()[0]

		# cache phenotype/profile data locally if it is a special symlink
		if (os.path.islink(os.path.join(input_dir,"phenotype"))
		    and
		    re.match('warehouse://.*', os.readlink(os.path.join(input_dir,"phenotype")))):
			cmd = '''(
			set -e
			cd '%s'
			whget phenotype phenotype.$$
			mv phenotype phenotype-locator
			mv --no-target-directory phenotype.$$ phenotype
			) &''' % os.path.dirname(genotype_file)
			subprocess.call (cmd, shell=True)

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
		         'pharmgkb_bin': os.path.join(script_dir, "gff_pharmgkb_map.py"),
		         'H': os.path.join(script_dir, "json_allele_frequency_query.py"),
		         'I': os.path.join(script_dir, "json_to_job_database.py"),
		         'Z': os.path.join(script_dir, "trait-o-matic-server.py"),
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
		         'pharmgkb_out': os.path.join(output_dir, "pharmgkb.json"),
		         '8': "",
		         '0': os.path.join(output_dir, "README"),
			 'lockfile': os.path.join(output_dir, "lock")}
		cmd = '''(
		flock --nonblock --exclusive 2 || exit
		set -x
		%(fetch)s '%(in)s' | python '%(A)s' '%(reference)s' /dev/stdin > '%(1)s'
		python '%(B)s' '%(1)s' > '%(2)s'
		python '%(C)s' '%(2)s' '%(reference)s' > '%(3)s'
		python '%(D)s' '%(3)s' > '%(4)s'
		python '%(E)s' '%(3)s' > '%(5)s'
		python '%(F)s' '%(3)s' > '%(6)s'
		python '%(G)s' '%(2)s' > '%(7)s'
		python '%(pharmgkb_bin)s' '%(3)s' > '%(pharmgkb_out)s'
		python '%(H)s' '%(4)s' '%(5)s' '%(6)s' '%(7)s' '%(pharmgkb_out)s' --in-place
		python '%(I)s' --drop-tables '%(4)s' '%(5)s' '%(6)s' '%(7)s' '%(pharmgkb_out)s'
		touch '%(0)s'
		python '%(Z)s' -t '%(url)s' '%(4)s' 'out/omim' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(5)s' 'out/hgmd' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(6)s' 'out/morbid' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(7)s' 'out/snpedia' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(pharmgkb_out)s' 'out/pharmgkb' '%(token)s'
		python '%(Z)s' -t '%(url)s' '%(0)s' 'out/readme' '%(token)s'
		rm -f %(lockfile)s
		) 2>>%(lockfile)s &''' % args
		subprocess.call(cmd, shell=True)
		return output_dir
	server.register_function(submit_local)

	def get_progress(genotype_file):
		output_dir = os.path.dirname(genotype_file) + "-out"
		lockfile = os.path.join(output_dir,'lock')
		# remove the lockfile if it is stale
		subprocess.call('flock --nonblock --exclusive %(lock)s rm -f %(lock)s || true'
				% { "lock": lockfile }, shell=True)
		if os.path.exists(lockfile):
			return { "state": "processing" }
		else:
			return { "state": "finished" }
	server.register_function(get_progress)

	def copy_to_warehouse(genotype_file, coverage_file, phenotype_file, trackback_url='', request_token='', recopy=True, tag=False):
		output_dir = os.path.dirname(genotype_file)

		g_locator = _copy_file_to_warehouse (genotype_file, "genotype.gff", tag, "genotype")
		c_locator = _copy_file_to_warehouse (coverage_file, "coverage", tag, "coverage")
		p_locator = _copy_file_to_warehouse (phenotype_file, "profile.json", tag, "profile")
		if (g_locator != None and
		    c_locator != None and
		    p_locator != None):
			return (g_locator, c_locator, p_locator)
		return None
	server.register_function(copy_to_warehouse)

	def _copy_file_to_warehouse (source_file, target_filename=None, tag=False, data_type=None, trackback_url=None, recopy=True):
		if not source_file:
			return ''

		# if file is special symlink, return link target
		if os.path.islink(source_file):
			if re.match('warehouse://.*', os.readlink(source_file)):
				locator = os.readlink(source_file)
				_update_warehouse_name_list (locator, target_filename, tag, data_type)
				return locator

		# if file has already been copied to warehouse, do not recopy
		if not recopy and os.path.islink(source_file + '-locator'):
			locator = os.readlink(source_file + '-locator')
			_update_warehouse_name_list (locator, target_filename, tag, data_type)
			return locator

		# if copying is required, fork a child process and return now
		if os.fork() > 0:
			# wait for intermediate proc to fork & exit
			os.wait()
			# return existing locator if available
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
			locator = 'warehouse:///' + locator.strip() + '/' + target_filename
			try:
				os.symlink(locator, source_file + '-locator.tmp')
				os.rename(source_file + '-locator.tmp', source_file + '-locator')
				_update_warehouse_name_list (locator, target_filename, tag, data_type)
			except OSError:
				print >> sys.stderr, 'Ignoring error creating symlink ' + source_file + '-locator'
			if trackback_url:
				subprocess.call("python '%(Z)s' -t '%(url)s' '%(out)s' '%(source)s' '%(token)s'"
						% { 'Z': os.path.join (script_dir, "trait-o-matic-server.py"),
						    'url': trackback_url,
						    'out': locator,
						    'source': source_file,
						    'token': request_token })
			os._exit(0)
		os._exit(1)
	
	def _update_warehouse_name_list (locator, target_filename, tag, data_type):
		if tag:
			share_name = "/" + os.uname()[1] + "/Trait-o-matic/" + tag + "/" + data_type
			share_target = re.sub("warehouse:///", "", locator)
			old_target = warehouse.name_lookup (share_name)
			whargs = ["wh",
				  "manifest",
				  "name",
				  "name=" + share_name,
				  "newkey=" + share_target]
			if old_target:
				whargs.append ("oldkey=" + old_target)
			subprocess.call (whargs)

	# run the server's main loop
	server.serve_forever()

if __name__ == "__main__":
	main()
