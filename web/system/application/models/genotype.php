<?php

class Genotype extends Model {

	var $_db;

	function Genotype()
	{
		parent::Model();
		$this->_db = $this->load->database('genotypes', TRUE);
		$this->db = $this->load->database('default', TRUE);
	}
	
	function get($id, $where, $limit=NULL, $offset=NULL)
	{
		if (!$this->_db->table_exists($id))
			return FALSE;

		// $this->_db->get() tries to use the most recently
		// loaded database instead of the one $this->_db
		// references -- we work around this by loading the
		// correct database, doing the query, then loading the
		// default database again.

		$this->load->database('genotypes', TRUE);
		if (is_object($where))
			$where = get_object_vars($where);		
		$this->_db->where($where);
		$query = $this->_db->get($id, $limit, $offset);
		$this->load->database('default', TRUE);

		if (is_object($where))
			return $limit == 1 ? $query->row() : $query->result();
		return $limit == 1 ? $query->row_array() : $query->result_array();
	}

}

/* End of file genotype.php */
/* Location: ./system/application/models/genotype.php */