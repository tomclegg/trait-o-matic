<?php

class Samples extends Controller {

	function Samples()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->load->database(); // we use some active record commands
		$this->load->model('User', 'user', TRUE);
		$this->load->model('Human', 'human', TRUE);
		$this->load->model('Job', 'job', TRUE);

		// find the 50 most recent samples
		$data['samples'] = array();
		$jobs = $this->job->get(array('public' => 1), 50);
		foreach ($jobs as $job)
		{
			$result = $this->job->get_all ($job,
						       $this->user->get(array('id'=>$job['user']),1),
						       $this->human->get(array('id'=>$job['human']),1));

			$data['samples'][] = $result;
			
		}
		sort($data['samples']);
		
		$this->load->view('samples', $data);
	}
}
