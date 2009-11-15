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
    $this->_browse_local ($data, array ("public >=" => 1));
    $this->load->view('jobs', $data);
  }

  function shared_data()
  {
    $this->config->load('trait-o-matic');
    if ($this->config->item('enable_browse_shared')) {
      $data['top_current_tab'] = "/browse/shared_data/";
      $this->_browse_local ($data, array ("public >=" => 0));
      $this->_browse_warehouse ($data);
      $this->load->view('jobs', $data);
    }
  }

  function allsnps($filters)
  {
    $this->config->load('trait-o-matic');
    if ($this->config->item('enable_browse_shared')) {
      $this->_browse_allsnps($filters);
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
	if (!ereg ("://", $locator) && ereg ("^[0-9a-f]", $locator))
	{
	  $locator = "warehouse:///" . $locator;
	}
	$data['warehouse_data'][$hostname][$username]["${datakind}_locator"] = $locator;
      }
    }
  }

  function _browse_local(&$data, $where)
  {
    $this->load->database(); // we use some active record commands
    $this->load->model('Job', 'job', TRUE);
    $this->load->model('File', 'file', TRUE);
    $this->load->model('User', 'user', TRUE);

    $data['jobs'] = array();
    $data['dataset_latest'] = array();
    $this->db->distinct();
    $this->db->group_by('submitted', 'desc');
    $jobs = $this->job->get($where);
    foreach ($jobs as $j)
    {
      $locators = $this->_get_locators ($j);
      if ($j['user'])
      {
	$user = $this->user->get(array('id' => $j['user']), 1);
	$j['user'] = $user;
	$data['locator2job'][implode(" ", $locators)] = $j;
      }
      $data['jobs'][] = $j;
    }
    sort($data['jobs']);
  }

  function _get_locators($job)
  {
    $this->load->model('File', 'file', TRUE);
    $this->load->helper('warehouse');
    $ret = array();
    foreach (array ('genotype', 'phenotype') as $kind)
    {
      $file = $this->file->get (array ('kind' => $kind, 'job' => $job['id']), 1);
      if (!$file)
	$ret[] = "";
      elseif (is_warehouse_symlink ($file['path']))
	$ret[] = readlink ($file['path']);
      elseif (is_warehouse_symlink (($x = $file['path']."-locator")))
	$ret[] = readlink ($x);
      elseif (is_warehouse_symlink (($x = dirname ($file['path']) . "-out/" . basename ($file['path']) . "-locator")))
	$ret[] = readlink ($x);
      else
	$ret[] = "";
      // $ret[-1] = ereg_replace("\+[^/]*", "", $ret[-1]);
    }
    return $ret;
  }

  function _browse_allsnps($filters)
  {
    $this->load->database();

    $left_join_a2 = "";
    $and_a2_gene_is_null = "";
    if (ereg ('noannotation', $filters) && "slow query" == "allowed")
    {
      // TODO: make this query execute in a reasonable time
      $left_join_a2 = "left join genotypes.allsnps a2 on a2.module <> 'ns' and a.chromosome=a2.chromosome and a.coordinates=a2.coordinates";
      $and_a2_gene_is_null = "and a2.gene is null";
    }

    $query = $this->db->query("
 select
  a.gene gene,
  a.amino_acid_change amino_acid_change,
  a.chromosome chromosome,
  a.coordinates coordinates,
  a.variant variant,
  a.ref_allele ref_allele,
  a.genotype genotype,
  a.zygosity zygosity,
  username
 from genotypes.allsnps a
 left join files on kind='out/readme' and path like concat('%/',a.job,'%')
 left join jobs on jobs.id=files.job and jobs.public >= 0
 left join users on jobs.user=users.id
 $left_join_a2
 where a.module = 'ns'
   and users.id is not null
   $and_a2_gene_is_null
 order by a.gene, a.chromosome, a.coordinates");
    header("Content-type: text/plain");
    $outq = "";
    $skipthis = 0;
    for ($row = $query->_fetch_object(), $lastrow = 0;
	 $row;
	 $lastrow = $row, $row = $query->_fetch_object())
    {
      // extract dbsnp id, if any, from "variant" field

      $row->dbsnp_id = "";
      if (ereg ("dbsnp:(rs[0-9]+)", $row->variant, $regs))
	$row->dbsnp_id = $regs[1];

      // apply filters

      if (!$lastrow ||
	  $lastrow->chromosome != $row->chromosome ||
	  $lastrow->coordinates != $row->coordinates)
      {
	// this is the first individual we're seeing with this variant
	if (!$skipthis) print $outq;
	$outq = "";
	$skipthis = 0;
      }
      else if ($skipthis)
      {
	// we've already decided that this variant is uninteresting
	continue;
      }
      else if (ereg ('unique', $filters))
      {
	// we only want unique variants, and this isn't the first
	// individual with this variant
	$skipthis = 1;
	continue;
      }

      if (ereg ('hom', $filters) && $row->zygosity != 'hom')
      {
	continue;
      }
      if (ereg ('nodbsnp', $filters) && $row->dbsnp_id)
      {
	continue;
      }

      // add this variant to the output queue
      $outq .= sprintf ("%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\t%s\n",
			$row->gene,
			$row->amino_acid_change,
			$row->chromosome,
			$row->coordinates,
			$row->dbsnp_id,
			$row->ref_allele,
			$row->genotype,
			$row->zygosity,
			$row->username);
    }
    if (!$skipthis)
      print $outq;
  }
}
