<?php

class Results extends Controller {

	function Results()
	{
		parent::Controller();	
	}
	
	function json()
	{
		// load necessary modules
		$this->load->model('User', 'user', TRUE);

		// authenticate
		$user_details = $this->_authenticate();
		if ($user_details !== FALSE)
		{
			$data = $this->_prep_results($this->user->get($user_details, 1));
			$this->load->view('json', $data);
		}
		else
		{
			echo $this->input->post('username');
			echo $this->input->post('password');
		}
	}

	function index()
	{
		// load necessary modules
		$this->load->model('User', 'user', TRUE);
		
		// authenticate
		$user_details = $this->_authenticate();
		if (!$user_details)
			return;

		//TODO: set session variable, if necessary

		// show data
		$data = $this->_prep_results($this->user->get($user_details, 1));
		$this->load->view('results', $data);
	}
	
	// in ./system/application/config/routes.php,
	// chmod/:any/:any is remapped to this function	
	function chmod()
	{
		// load necessary modules
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		$this->load->model('File', 'file', TRUE);
		$this->load->library('xmlrpc');
		$this->config->load('trait-o-matic');

		if (!$this->config->item('enable_chmod'))
		{
			return;
		}

		// keep track of what permissions we're setting
		$job_public_mode_symbol = $this->uri->rsegment(3);
		if (!$job_public_mode_symbol)
		{
			return;
		}
		else
		{
			// again, the given numbers are just for show
			// in order to express the kinds of things that
			// users, curators, and the public may do at
			// each permission level
			//
			// group = curators
			// w = curate
			// x = reprocess, etc.
			$public_modes = array(
				'700' => -1,
				'760' => 0,
				'764' => 1
			);
			
			if (!array_key_exists($job_public_mode_symbol, $public_modes))
				return;
			$job_public_mode = $public_modes[$job_public_mode_symbol];
		}
		
		// keep track of the job ID
		$job = $this->uri->rsegment(4);
		if (!$job)
		{
			return;
		}
		
		// authenticate
		$user_details = $this->_authenticate();
		if (!$user_details)
			return;
		
		// now make sure the user is the correct one (i.e. the owner of the job)
		$user = $this->user->get($user_details, 1);
		if (!$this->job->count(array('user' => $user['id'], 'id' => $job)))
		{
			$data['error'] = 'Only users who have submitted a query may change its settings.';
			$data['redirect'] = $this->uri->uri_string();
			$this->load->view('login', $data);
			return;
		}
		
		// now actually do some work!
		$this->job->update(array('public' => $job_public_mode), array('id' => $job));
		if ($job_public_mode > 0 &&
		    $this->config->item ("enable_warehouse_storage"))
		{
			$this->_share($job);
		}
		$this->load->view('confirm_chmod');
	}

	function share()
	{
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		$this->load->model('File', 'file', TRUE);
		$this->load->library('xmlrpc');
		$this->config->load('trait-o-matic');
		if (!$this->config->item ("enable_warehouse_storage"))
		{
			return;
		}

		// keep track of the job ID
		$job = $this->uri->rsegment(3);
		if (!$job)
		{
			return;
		}
		
		// authenticate
		$user_details = $this->_authenticate();
		if (!$user_details)
			return;
		
		// now make sure the user is the correct one (i.e. the owner of the job)
		$user = $this->user->get($user_details, 1);
		if (!$this->job->count(array('user' => $user['id'], 'id' => $job)))
		{
			$data['error'] = 'Only users who have submitted a query may change its settings.';
			$data['redirect'] = $this->uri->uri_string();
			$this->load->view('login', $data);
			return;
		}

		$this->_share($job);
		$this->load->view('confirm_share');
	}

	function _share($job)
	{
		$path = array();
		foreach (array ('genotype', 'coverage', 'phenotype') as $kind)
		{
			$file = $this->file->get(array('kind' => $kind, 'job' => $job), 1);
			if ($file && $file['path'])
				$path[$kind] = $file['path'];
			else
				$path[$kind] = '';
		}
		//TODO: move server address into a config file
		$this->xmlrpc->server('http://localhost/', 8080);
		$this->xmlrpc->method('copy_to_warehouse');
		$request = array($path['genotype'], $path['coverage'], $path['phenotype'], '', '', TRUE);
		$this->xmlrpc->request($request);
		if (!$this->xmlrpc->send_request())
		{
			// echo $this->xmlrpc->display_error();
			//TODO: error out, with some sort of interface
		}
	}
	
	// in ./system/application/config/routes.php,
	// download/:any/:any is remapped to this function	
	function download()
	{
		// load necessary modules
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		$this->config->load('trait-o-matic');

		if (!$this->config->item('enable_download_gff'))
		{
			return;
		}

		// keep track of what file is being requested
		$what = $this->uri->rsegment(3);
		if (!$what)
		{
			return;
		}
		
		// keep track of the job ID
		$job = $this->uri->rsegment(4);
		if (!$job)
		{
			return;
		}
		
		// public data
		if ($this->job->count(array('id' => $job, 'public' => 1)))
		{
			// force download data
			$this->_force_download_source_file($what, $job);
			return;
		}
		
		// otherwise, authenticate
		$user_details = $this->_authenticate();
		if (!$user_details)
			return;
		
		// now make sure the user is the correct one (i.e. the owner of the job)
		$user = $this->user->get($user_details, 1);
		if (!$this->job->count(array('user' => $user['id'], 'id' => $job)))
		{
			$data['error'] = 'Only users who have submitted a query may download these data.';
			$data['redirect'] = $this->uri->uri_string();
			$this->load->view('login', $data);
			return;
		}
		
		// force download data
		$this->_force_download_source_file($what, $job);
	}
	
	// in ./system/application/config/routes.php,
	// samples/:any is remapped to this function
	function samples()
	{
		// load necessary modules
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		
		$username = $this->uri->rsegment(3);
		if ($username === FALSE)
			return;

		$username = ereg_replace ("_", " ", $username);
		$user = $this->user->get(array('username' => $username), 1);
		// we check to make sure that at least one released job exists;
		// the function _prep_results does not do this check
		if (!$user || !$this->job->count(array('user' => $user['id'], 'public' => 1)))
			return;
		
		// make sure to show only publicly released results
		$data = $this->_prep_results($user, TRUE);
		$this->load->view('results', $data);
	}
	
	// this is our authentication function
	// it displays the login page, sets the proper redirect
	// and returns FALSE when authentication fails; otherwise
	// it returns an associative array of user details
	function _authenticate()
	{
		if ($this->input->post('username') !== FALSE)
		{
			// populate array with user details
			$user_details = array(
				'username' => trim(ereg_replace ("_", " ", $this->input->post('username'))),
				'password_hash' => hash('sha256', $this->input->post('password'))
			);

			// error checking
			if (!$user_details['username'])
			{
				$data['error'] = '<strong>Name</strong> is required.';
				$data['redirect'] = $this->uri->uri_string();
				$this->load->view('login', $data);
				return FALSE;
			}
			if (!$this->user->count($user_details))
			{
				$data['error'] = 'Incorrect name or password.';
				$data['redirect'] = $this->uri->uri_string();
				$this->load->view('login', $data);
				return FALSE;
			}
			
			// set session data
			$session_user_details = array(
				'username' => $user_details['username']
			);
			$this->session->set_userdata($session_user_details);
		}
		// look at session data if no input is supplied
		else if ($this->session->userdata('username') !== FALSE)
		{

			// populate array with user details and do a sanity check
			$user_details = array(
				'username' => $this->session->userdata('username')
			);
			if (!$this->user->count($user_details))
			{
				$data['error'] = 'Incorrect name or password.';
				$data['redirect'] = $this->uri->uri_string();
				$this->load->view('login', $data);
				return FALSE;
			}
		}
		else
		{
			$data['redirect'] = $this->uri->uri_string();
			$this->load->view('login', $data);
			return FALSE;
		}
		return $user_details;
	}
	
	// note that invoking this function incorrectly may permit bypassing
	// password restrictions
	function _force_download_source_file($what, $job)
	{
		// load necessary modules
		$this->load->model('File', 'file', TRUE);
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		$this->load->helper('file');
		$this->load->helper('json');
		$this->load->helper('language');
		$this->load->helper('warehouse');
		$this->config->load('trait-o-matic');

		if ($what == "json")
		{
			if (!$this->config->item('enable_download_json'))
				return;
			$job = $this->job->get(array('id' => $job),1);
			$user = $this->user->get(array('id' => $job['user']),1);
			$auth_user = $this->_authenticate();
			$data = $this->_prep_results ($user, !$auth_user || $auth_user['id'] != $user['id']);
			$filename = $user['username'];
			if ($job['processed'])
				$filename .= " ".$job['processed'];
			$filename .= ".json";
			header ("Content-type: text/json");
			header ("Content-disposition: attachment; filename=\"{$filename}\"");
			print json_encode ($data);
			exit;
		}
		
		// grab the appropriate file
		//TODO: kind of a hack for "ns"
		$kind = ($what == "ns" || $what == "dbsnp") ? "out/readme" : $what;
		$data_file = $this->file->get(array('kind' => $kind, 'job' => $job), 1);
		if (!$data_file)
			return;
		if ($what == "ns")
			$data_file_path = dirname($data_file['path']) . "/ns.gff";
		else if ($what == "dbsnp")
			$data_file_path = dirname($data_file['path']) . "/genotype.dbsnp.gff";
		else
			$data_file_path = $data_file['path'];

		if (is_link ($data_file_path) &&
		    ereg("^warehouse://[^/]*/([0-9a-f]+)", readlink($data_file_path), $matches))
		{
			// set unique filename based on locator if the
			// desired data file is a symlink to a
			// warehouse locator

			$ext = pathinfo(readlink($data_file_path), PATHINFO_EXTENSION);
			$filename = $matches[1] . "." . $ext;
		}
		else
		{
			// set unique file name based on hash,
			// preserving the extension (note the use of
			// $data_file_path and $data_file['path'], the
			// latter of which allows the retrieved "ns"
			// file to use the "genotype" file's
			// extension)

			$ext = pathinfo($data_file['path'], PATHINFO_EXTENSION);
			if (empty($ext)) $ext = "gff";
			$filename = hash_file('sha256', $data_file_path) . '.' . $ext;
		}
		
		// force download
		header("Content-type: text/plain");
		header("Content-disposition: attachment; filename=\"{$filename}\"");
		warehouse_readfile($data_file_path);
	}
	
	// note that invoking this function incorrectly may permit bypassing
	// password restrictions
	function _prep_results($user, $public_only=FALSE)
	{
		// load necessary modules
		$this->load->model('File', 'file', TRUE);
		$this->load->model('Job', 'job', TRUE);
		$this->load->helper('file');
		$this->load->helper('json');
		$this->load->helper('language');
		$this->load->helper('url');
		$this->load->helper('warehouse');
		$this->config->load('trait-o-matic');
		// load strings for phenotypes
		$this->lang->load('phenotype');
		
		// load the user name into our output data
		$data['username'] = $user['username'];
		// ...and remember whether it's being accessed as a public view or sample
		$data['public'] = $public_only;
		
		// retrieve most recent job
		if ($public_only)
			$jobs = $this->job->get(array('user' => $user['id'], 'public' => 1));
		else
			$jobs = $this->job->get(array('user' => $user['id']));
		$most_recent_job = end($jobs);
		
		// update retrieval timestamp on the most recent job
		$debug_most_recent_job_id = $most_recent_job['id'];
		log_message('debug', "Updating timestamp on {$debug_most_recent_job_id}");
		$this->job->update_timestamp('retrieved', array('id' => $most_recent_job['id']));
		
		// load the job ID and privacy setting into our output data
		$data['job_id'] = $most_recent_job['id'];
		$data['job_public_mode'] = $most_recent_job['public'];
		
		// read user-submitted phenotypes and append to data
		$phenotype_file = $this->file->get(array('kind' => 'phenotype', 'job' => $most_recent_job['id']), 1);
		$phenotype_path = $phenotype_file['path'];
		$data['phenotypes'] = get_object_vars(json_decode(warehouse_fetch($phenotype_path)));
		//TODO: error out if no file is found

		$data['processed'] = $most_recent_job['processed'];
		
		// read results
		$job_id = $most_recent_job['id'];
		$job_dir = basename(dirname($phenotype_path));
		$data['phenotypes']['omim'] = $this->_load_output_data('omim', $job_id, $job_dir);
		$data['phenotypes']['snpedia'] = $this->_load_output_data('snpedia', $job_id, $job_dir);
		if ($this->config->item('enable_hgmd'))
			$data['phenotypes']['hgmd'] = $this->_load_output_data('hgmd', $job_id, $job_dir);
		if ($this->config->item('enable_pharmgkb'))
			$data['phenotypes']['pharmgkb'] = $this->_load_output_data('pharmgkb', $job_id, $job_dir);
		$data['phenotypes']['morbid'] = $this->_load_output_data('morbid', $job_id, $job_dir);

		if ($this->config->item('enable_warehouse_storage'))
		{
			// get warehouse locators if available
			foreach (array ('genotype', 'coverage', 'phenotype') as $kind)
			{
				$data_file = $this->file->get(array('kind' => $kind, 'job' => $most_recent_job['id']), 1);
				if (!($data_file &&
				      $data_file['path'] &&
				      ($data_path = $data_file['path'])))
					continue;
				else if (is_link($data_path) &&
					 ereg ("^warehouse://", ($locator = readlink($data_path))))
					$data["locator"][$kind] = $locator;
				else if (is_link($data_path."-locator"))
					$data["locator"][$kind] = readlink($data_path."-locator");
				else if (file_exists($data_path))
					$data["locator"][$kind] = "";
			}
		}

		return $data;
	}
	
	// note that invoking this function incorrectly may permit bypassing
	// password restrictions
	function _load_output_data($kind, $job_id, $job_dir)
	{
		$this->config->load('trait-o-matic');
		$this->load->model('File', 'file', TRUE);
		$this->load->helper('file');
		$this->load->helper('json');

		$data = NULL;
		if ($this->config->item('backend_intermediary') == 'mysql')
		{
			$this->load->model('Genotype', 'genotype', TRUE);
			$data = $this->genotype->get($job_dir, array('module' => $kind));
		}
		if (!$data)
		{
			$file = $this->file->get(array('kind' => "out/{$kind}", 'job' => $job_id), 1);
			if (!$file) return NULL;
			$data = array();
			$path = $file['path'];
			foreach (preg_split('/[\r\n]+/', read_file($path), -1, PREG_SPLIT_NO_EMPTY) as $line)
			{
				$data[] = get_object_vars(json_decode($line));
			}
		}
		// default sort; first obtain list of columns by which to sort
		foreach ($data as $key => $row) {
			if (array_key_exists ('taf', $row) && is_object($row['taf']))
				;
			else if (!array_key_exists ('taf', $row) ||
			    !ereg("^{", $row['taf']))
				unset ($data[$key]['taf']);
			else
				$data[$key]['taf'] = json_decode($row['taf']);

			if (array_key_exists ('maf', $row) && is_object($row['maf']))
				;
			else if (!array_key_exists ('maf', $row) ||
			    !ereg("^{", $row['maf']))
				unset ($data[$key]['maf']);
			else
				$data[$key]['maf'] = json_decode($row['maf']);

			if (!isset ($row['gene'])) unset($data[$key]['gene']);

					// to have chromosomes sort correctly, we convert X, Y, M (or MT) to numbers
					$chromosome[$key]  = str_replace('chr', '', $row['chromosome']);
					switch ($chromosome[$key])
					{
					case 'X':
						$chromosome[$key] = '23';
						break;
					case 'Y':
						$chromosome[$key] = '24';
						break;
					case 'M':
					case 'MT':
						$chromosome[$key] = '25';
						break;
					}
					// other things to sort by; we include amino acid position despite having genome
					// coordinates to break ties in case of alternative splicings
					$coordinates[$key] = $row['coordinates'];
					$gene[$key] = array_key_exists('gene', $row) ? $row['gene'] : "";
					$amino_acid_position[$key] = array_key_exists('amino_acid_change', $row) ?
					                               preg_replace('/\\D/', '', $row['amino_acid_change']) : "";
					$phenotype[$key] = $row['phenotype'];
				}
				@array_multisort($chromosome, SORT_NUMERIC, $coordinates, SORT_NUMERIC,
				                 $gene, $amino_acid_position, SORT_NUMERIC, $phenotype, $data);
		return $data;
	}
}

/* End of file results.php */
/* Location: ./system/application/controllers/results.php */
