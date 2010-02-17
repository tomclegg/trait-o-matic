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
		usort($data['samples'], array ("Samples", "_sort"));
		
		$this->load->view('samples', $data);
	}

	static function _compare ($a, $b)
	{
		if ($a < $b) return -1;
		if ($a > $b) return 1;
		return 0;
	}

	static function _sort($a, $b)
	{
		if ($cmp = ((!$a['human'] || !isset($a['human']['name'])) -
			    (!$b['human'] || !isset($b['human']['name']))))
			return $cmp;
		if ($cmp = Samples::_compare($a['extra'], $b['extra']))
			return $cmp;
		if (isset ($a['human']['name']) &&
		    isset ($b['human']['name']) &&
		    ($cmp = _compare($a['human']['name'],
				     $b['human']['name'])))
			return $cmp;
		return 0;
	}
}
