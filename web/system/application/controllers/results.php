<?php

class Results extends Controller {

	function Results()
	{
		parent::Controller();	
	}
	
	function json()
	{
		$user_details = $this->_check_user();
		if ($user_details !== FALSE)
		{
			$data = $this->_prep_results($this->user->get($user_details, 1));
			$this->load->view('results', $data);
		}
		else
		{
			echo $this->input->post('username');
			echo $this->input->post('password');
		}
	}

	function index()
	{
		$user_details = $this->_check_user();
		if ($user_details !== FALSE)
		{
			// show results
			$data = $this->_prep_results($this->user->get($user_details, 1));

			//TODO: set session variable, if necessary
			$this->load->view('results', $data);
		}
		else
		{
			$this->load->view('login');
		}		
	}
	
	function _check_user()
	{
		if ($this->input->post('username') !== FALSE)
		{
			// load necessary modules
			$this->load->model('User', 'user', TRUE);

			// populate array with user details
			$user_details = array(
				'username' => trim($this->input->post('username')),
				'password_hash' => hash('sha256', $this->input->post('password'))
			);

			// error checking
			if (!$user_details['username'])
			{
				$data['error'] = '<strong>Name</strong> is required.';
				return FALSE;
			}
			if (!$this->user->count($user_details))
			{
				$data['error'] = 'Incorrect name or password.';
				return FALSE;
			}
			return $user_details;
		}
		return FALSE;
	}

	// in ./system/application/config/routes.php,
	// samples/:any is remapped to this function
	function samples()
	{
		// load necessary modules
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		
		$s2 = $this->uri->segment(2);
		$s3 = $this->uri->segment(3);
		$username = $s3 ? $s3 : $s2;
		if ($username === FALSE)
			return;
		
		$user = $this->user->get(array('username' => $username), 1);
		// we check to make sure that at least one released job exists;
		// the function _prep_results does not do this check
		if (!$user || !$this->job->count(array('user' => $user['id'], 'public' => 1)))
			return;
		
		// make sure to show only publicly released results
		$data = $this->_prep_results($user, TRUE);	

		//TODO: set session variable, if necessary
		$this->load->view('results', $data);
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
		// load strings for phenotypes
		$this->lang->load('phenotype');
		
		// load the user name into our output data		
		$data['username'] = $user['username'];
		
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
		
		// read user-submitted phenotypes and append to data
		$phenotype_file = $this->file->get(array('kind' => 'phenotype', 'job' => $most_recent_job['id']), 1);
		$phenotype_path = $phenotype_file['path'];
		$data['phenotypes'] = get_object_vars(json_decode(read_file($phenotype_path)));
		//TODO: error out if no file is found
		
		// read results
		$job_dir = basename(dirname($phenotype_path));
		$data['phenotypes']['omim'] = $this->_load_output_data('omim', $job_dir);
		$data['phenotypes']['snpedia'] = $this->_load_output_data('snpedia', $job_dir);
		$data['phenotypes']['hgmd'] = $this->_load_output_data('hgmd', $job_dir);
		$data['phenotypes']['morbid'] = $this->_load_output_data('morbid', $job_dir);
		return $data;
	}
	
	function _load_output_data($kind, $job_dir)
	{
		$this->load->model('Genotype', 'genotype', TRUE);
		$this->load->helper('json');
				
		$data = $this->genotype->get($job_dir, array('module' => $kind));

				// default sort; first obtain list of columns by which to sort
				foreach ($data as $key => $row) {
			if (!array_key_exists ('taf', $row) ||
			    !ereg("^{", $row['taf']))
				unset ($data[$key]['taf']);
			else
				$data[$key]['taf'] = json_decode($row['taf']);
			if (!array_key_exists ('maf', $row) ||
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
