<?php
class mysqle {
	private $mysql_link;
	public  $affected_rows = 0, $connect_errno = 0, $connect_error = '', $errno = 0, $error = '', $insert_id = 0;
	
	
	private function set_vars() {
		$this->errno         = mysql_errno($this->mysql_link);
		$this->error         = mysql_error($this->mysql_link);
		$this->affected_rows = mysql_affected_rows($this->mysql_link);
		$this->insert_id     = mysql_insert_id($this->mysql_link);
	}

	
	public function __construct($host = 'localhost', $user = 'root', $pass = '') {
		$this->mysql_link = mysql_connect($host, $user, $pass);
		if (!$this->mysql_link) {
			$this->connect_errno = 999;
			$this->connect_error = "Couldn't connect to DB";
		}
	}
	
	
	public function select_db($db) {
		mysql_select_db($db,$this->mysql_link);
		$this->set_vars();

		return $this->errno ? false : true;
	}
	
	
	public function query($que) {
		$result = mysql_query($que,$this->mysql_link);
		$this->set_vars();

		return is_resource($result) ? new mysqle_result($result) : $result;
	}
	
	
	public function real_escape_string($str){
		return mysql_real_escape_string($str, $this->mysql_link);
	}
}

class mysqle_result {
	private $data;
	public  $num_rows;
	
	
	public function __construct($sql){
		$this->data     = $sql;
		$this->num_rows = mysql_num_rows($sql);
	}
	
	
	public function fetch_assoc(){
		return mysql_fetch_assoc($this->data);
	}
	
	
	public function fetch_row(){
		return mysql_fetch_row($this->data);
	}
	
	
	public function fetch_array(){
		return mysql_fetch_array($this->data);
	}
	
	
	public function free(){
		mysql_free_result($this->data);
		$this->num_rows = 0;
	}
}
