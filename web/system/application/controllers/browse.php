<?php

class Browse extends Controller {
	
  function Browse()
  {
    parent::Controller();	
  }

  function index() { $this->public_data(); }

  function public_data()
  {
    $data['top_current_tab'] = "/";
    $this->_browse ($data, array ("public >=" => 1));
  }

  function shared_data()
  {
    $this->config->load('trait-o-matic');
    if ($this->config->item('enable_browse_shared')) {
      $data['top_current_tab'] = "/browse/shared_data/";
      $this->_browse_warehouse ($data);
      $this->_browse ($data, array ("public >=" => 0));
    }
  }

  function _get_warehouse_list()
  {
    $refresh_command = 'wh manifest list | (fgrep /Trait-o-matic/ || [ "$?" = 1 ]) > /tmp/warehouse-list.tmp && mv /tmp/warehouse-list.tmp /tmp/warehouse-list.cache';

    $age = 86400;
    if (file_exists ("/tmp/warehouse-list.cache"))
      $age = time() - filemtime ("/tmp/warehouse-list.cache");

    if ($age < 300)
      return `cat /tmp/warehouse-list.cache`;

    elseif ($age < 3600)
    {

      // If the cached list more than 5 minutes old, but not ancient,
      // use it, but try to refresh it in the background

      `/sbin/start-stop-daemon --background --start --pidfile /dev/null --startas /usr/bin/flock -- --nonblock /tmp/warehouse-list.lock -c '$refresh_command'`;

      return `cat /tmp/warehouse-list.cache`;
    }

    else
    {
      // If the cached list is more than 1 hour old, don't use it, get a fresh list
      return `if sh -c '$refresh_command'; then cat /tmp/warehouse-list.cache; else wh manifest list | fgrep /Trait-o-matic/; fi`;
    }
  }

  function _browse_warehouse(&$data)
  {
    $this->config->load('trait-o-matic');
    if (!$this->config->item('enable_warehouse_storage'))
      return;
    foreach (explode ("\n", $this->_get_warehouse_list()) as $locandname)
    {
      if (ereg("^([^ ]*) /([^ /]*)/Trait-o-matic/(.*)/(genotype|profile) ",
	       $locandname, $regs))
      {
	$locator = $regs[1];
	$hostname = $regs[2];
	$username = ereg_replace ("_", " ", $regs[3]);
	$datakind = $regs[4];
	if (!ereg ("\:\/\/", $locator) && ereg ("^[0-9a-f]", $locator))
	{
	  $locator = "warehouse:///" . $locator;
	}
	$data['warehouse_data'][$hostname][$username]["${datakind}_locator"] = $locator;
      }
    }
  }

  function _browse($data, $where)
  {
    $this->load->database(); // we use some active record commands
    $this->load->model('Job', 'job', TRUE);
    $this->load->model('User', 'user', TRUE);
		
    // find the 50 most recent samples
    $data['samples'] = array();
    $this->db->distinct();
    $this->db->group_by('submitted', 'desc');
    $jobs = $this->job->get($where);
    foreach ($jobs as $j)
    {
      if ($j['user'])
      {
	$user = $this->user->get(array('id' => $j['user']), 1);
	$j['user'] = $user;
      }
      $data['jobs'][] = $j;
    }
    sort($data['jobs']);
		
    $this->load->view('jobs', $data);
  }

}
