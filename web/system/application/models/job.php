<?php

class Job extends Model {

	var $_table = 'jobs';
	
	function Job()
	{
		parent::Model();
	}
	
	function insert() // note that this function takes no arguments
	{
		////////////////////////////////////////////
		$this->db->set('submitted', 'NOW()', FALSE);
		////////////////////////////////////////////
		if ($this->db->insert($this->_table))
			return $this->db->insert_id();
		return FALSE;
	}
	
	function count($where)
	{
		if (is_object($where))
			$where = get_object_vars($where);		
		$this->db->where($where);
		return $this->db->count_all_results($this->_table);
	}
	
	function get($where, $limit=NULL, $offset=NULL)
	{
		if (is_object($where))
			$where = get_object_vars($where);		
		$this->db->where($where);
		$this->db->order_by('id', 'desc');
		$query = $this->db->get($this->_table, $limit, $offset);
		if (is_object($where))
			return $limit == 1 ? $query->row() : $query->result();
		return $limit == 1 ? $query->row_array() : $query->result_array();
	}
	
	function update($set, $where)
	{
		return $this->db->update($this->_table, $set, $where);
	}

	function get_all($job, $user, $human)
	{
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
		$result['label'] = $label;
		$result['htmllabel'] = nl2br(htmlspecialchars($label));

		$result['image'] = $human
			? $this->get_thumbnail ($human['id'], 100, 100)
			: "/media/placeholder.gif";

		return $result;
	}
	
	function get_thumbnail ($id, $w, $h)
	{
	  // TODO: move this stuff to models/Image.php

	  if (!is_dir ("/home/trait/upload/image") &&
	      !mkdir ("/home/trait/upload/image"))
	  {
	    // no chance anything good is going to happen
	    return "/media/placeholder.gif";
	  }

	  if (file_exists ($thumbnail="/home/trait/upload/image/thumb_${w}x${h}_${id}.png"))
	  {
	    // already made a thumnail, just return its url
	    return "/image/get/${w}x${h}/${id}.png";
	  }

	  // find an uploaded image that we can make a thumbnail from

	  if (file_exists ($source="/home/trait/upload/image/$id.png"))
	    $i = imagecreatefrompng ($source);
	  else if (file_exists ($source="/home/trait/upload/image/$id.jpg"))
	    $i = imagecreatefromjpeg ($source);
	  else if (file_exists ($source="/home/trait/upload/image/$id.gif"))
	    $i = imagecreatefromgif ($source);
	  else
	    // found nothing, return generic image
	    return "/media/placeholder.gif";

	  // Make a new thumbnail

	  $t = imagecreatetruecolor($w, $h);

	  // where are we going to copy from?
	  $sx = 0;
	  $sy = 0;
	  $sw = imagesx($i);
	  $sh = imagesy($i);

	  // copying to?
	  $dx = 0;
	  $dy = 0;
	  $dw = $w;
	  $dh = $h;

	  if ($sw < $dw)	// source width small -> shrink dest area
	  {
	    $dx = ($dw - $sw) / 2;
	    $dw = $sw;
	  }
	  if ($sh < $dh)	// source height small -> shrink dest area
	  {
	    $dy = ($dh - $sh) / 2;
	    $dh = $sh;
	  }
	  if ($sw/$sh < $dw/$dh) // source skinnier than dest - crop source h
	  {
	    $sy = ($sh - ($sw * ($dh/$dw))) / 2;
	    $sh -= $sy * 2;
	  }
	  else		// source flatter than dest - crop source w
	  {
	    $sx = ($sw - ($sh * ($dw/$dh))) / 2;
	    $sw -= $sx * 2;
	  }
	  imagefilledrectangle ($t, 0, 0, $w, $h,
				imagecolorallocate ($t, 255, 255, 255));
	  imagecopyresampled ($t, $i, $dx, $dy, $sx, $sy, $dw, $dh, $sw, $sh);
	  imagepng ($t, $thumbnail.".tmp.".posix_getpid());
	  rename ($thumbnail.".tmp.".posix_getpid(), $thumbnail);
	  return "/image/get/${w}x${h}/${id}.png";
	}

	////////////////////////////////////////////
	function update_timestamp($key, $where)
	{
		$this->db->set($key, 'NOW()', FALSE);
		$this->db->where($where);
		return $this->db->update($this->_table);
	}
	////////////////////////////////////////////

}

/* End of file job.php */
/* Location: ./system/application/models/job.php */
