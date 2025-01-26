<?php
class baseEngine {
	protected $board_map = array();
	
// converts board base from outside to inner value
	public function board_in($out){
		return isset($this->board_map[$out]) ? $this->board_map[$out] : $out;
	}
	
// converts board base from inner to outside value
	public function board_out($in){
		$tmp = array_search($in, $this->board_map);
		return ($tmp === false) ? $in : $tmp;
	}
}
