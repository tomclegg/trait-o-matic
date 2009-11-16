<?php

class Samples extends Controller {

	function Samples()
	{
		parent::Controller();	
	}
	
	function index()
	{
		$this->load->database(); // we use some active record commands
		$this->load->model('Job', 'job', TRUE);
		$this->load->model('User', 'user', TRUE);
		$this->load->model('Human', 'human', TRUE);

		// find the 50 most recent samples
		$data['samples'] = array();
		$jobs = $this->job->get(array('public' => 1), 50);
		foreach ($jobs as $job)
		{
			$user = $this->user->get(array('id' => $job['user']), 1);
			$human = $this->human->get(array('id' => $job['human']), 1);
			$result = array ('user' => $user,
					 'job' => $job,
					 'human' => $human,
					 'extra' => array());
			// make a suitable label with the info we have
			if ($human)
			{
			  $result['name'] = $human['name'] ? $human['name'] : "Anonymous";
			  if ($human['location'])
			    $result['extra'][] = $human['location'];
			  if ($human['sex'] == 'M' || $human['sex'] == 'F')
			    $result['extra'][] = ($human['sex'] == 'M' ? "male" : "female");
			}
			else
			{
			  // use the name of the user who submitted the job (original behavior)
			  $result['name'] = $result['user']['username'];
			}
			$label = $result['name'];
			if ($result['extra'] && count($result['extra']))
			  $label .= "\n" . implode ("\n", $result['extra']);
			if ($job['label'])
			  $label .= "\n" . ereg_replace (", ", "\n", $job['label']);
			$result['htmllabel'] = nl2br(htmlspecialchars($label));

			$data['samples'][] = $result;
			
		}
		sort($data['samples']);
		
		$this->load->view('samples', $data);
	}
	
	// note that in ./system/application/config/routes.php,
	// samples/:any is remapped to results/samples/$1
	
}
