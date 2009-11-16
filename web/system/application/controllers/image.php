<?php

class Image extends Controller {

	function Image()
	{
		parent::Controller();	
	}
	
	function get($size,$id)
	{
		// TODO: obey config variables instead of hardcoding
		// /home/trait/upload/image (see also
		// controllers/samples.php)

		if (ereg ("^[0-9x]+$", $size) &&
		    ereg ("^[0-9]+", $id, $regs))
		{
			header("Content-type: image/png");
			readfile("/home/trait/upload/image/thumb_${size}_$regs[0].png");
		}
	}
}
