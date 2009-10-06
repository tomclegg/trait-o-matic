<?php  if ( ! defined('BASEPATH')) exit('No direct script access allowed');

class MY_Input extends CI_Input {
  function MY_Input()
  { 
    parent::CI_Input();
  }
  /*
   * Sometimes browsers seem to send unsolicited cookies with names
   * that do not meed CodeIgniter's expectations.  The default
   * CodeIgniter behavior is to crash.  We would rather ignore them.
   */
  function _clean_input_keys($str)
  { 
    if ( ! preg_match("/^[a-z0-9:_\/-]+$/i", $str))
      return "";
    else
      return $str;
  }
}

?>
