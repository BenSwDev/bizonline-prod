<?php
/**
 * UDB is a class for more comfortable work with databases.
 * @package udb
 * @version 1.2 (requires PHP 7.0 or newer)
 *
 * ChangeLog:
 *     v1.2
 *     - method "full_list()" is now deprecated
 *     - added new method "single_list(...)" to replace "full_list(...)" for uniform name rules
 *     - added new method "insert_multi(...)" for simultaneous insert of multiple rows
 */

const UDB_NUMERIC = 1;
const UDB_ASSOC   = 2;
const UDB_BOTH    = 3;

const UDB_REPORT_NONE = 0;
const UDB_REPORT_FILE = 128;
const UDB_REPORT_TEXT = 256;

const UDB_RFILE_PATH = 'udb_errors.log';

class udbLogger {
    const MODE_NONE = 0;
    const MODE_FILE = 1;

    private $timer;
    private $logs;
    private $filename;
    private $mode;

    private $runTime;
    private $runLim = 2;

    public function __construct($m = self::MODE_NONE){
        $this->timer = microtime(true);
        $this->logs  = array(date('Y-m-d H:i:s') . ' | ' . $_SERVER['REQUEST_URI']);
        $this->filename = (strpos($_SERVER['SCRIPT_FILENAME'], 'cron') === false) ? 'sql.log' : 'cron.log';
        $this->runLim   = (strpos($_SERVER['SCRIPT_FILENAME'], 'cron') === false) ? 2 : 0;
    }

    public function log($que, $start, $end){
        if ($this->mode){
            $this->logs[]   = '-- ' . ($start - $this->timer) . ':' . ($end - $start) . ' ' . $que;
            $this->runTime  = $start - $this->timer;
        }
    }

    public function __destruct(){
        if (count($this->logs) > 1 && $this->runTime > $this->runLim)
            file_put_contents(__DIR__ . '/' . $this->filename, implode(PHP_EOL . PHP_EOL, $this->logs) . PHP_EOL . '+---------------------------------------' . PHP_EOL . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    public function set_mode($mode){
        $this->mode = $mode;
    }
}


class udb {
	private static $pref_list = array('mysqli' => 'mysqli', 'mysql' => 'mysqle');
	private static $report    = UDB_REPORT_TEXT, $stop = true, $class = null, $links = array(), $last = null;
	private static $connect   = array();
	
    public static $logger;

	private static function foo_pointer($key){
		switch($key){
			case UDB_NUMERIC:
				return 'fetch_row';
			case UDB_ASSOC:
				return 'fetch_assoc';
			case UDB_BOTH:
				return 'fetch_array';
		}
		return null;
	}
	
	private static function key_struct(&$result, $keys, &$row, $val = null){
		if (!count($keys))
			return $result = is_null($val) ? $row : $row[$val];
		
		$key = array_shift($keys);
		if (is_null($key))
			return $result[] = is_null($val) ? $row : $row[$val];
		
		is_array($result[$row[$key]]) or $result[$row[$key]] = array();
		self::key_struct($result[$row[$key]], $keys, $row, $val);

        return $result;
	}
	
	private static function big_error($msg){
		$trace  = debug_backtrace();
		$error  = $trace[0];
		$result = array();
		
		if (is_string($msg))
			$result[] = basename($error['file']).':'.$error['line'].' '.$msg;
		elseif (is_object($msg) && property_exists($msg,'errno'))
			$result[] = basename($error['file']).':'.$error['line'].' '.$msg->error.PHP_EOL.PHP_EOL.$error['args'][1];

		for($i=1; $i<count($trace); $i++)
			$result[] = $i.': '.basename($trace[$i]['file']).':'.$trace[$i]['line'].' '.(isset($trace[$i]['object']) ? get_class($trace[$i]['object']).$trace[$i]['type'] : ($trace[$i]['class'] ? $trace[$i]['class'].$trace[$i]['type'] : '')).$trace[$i]['function'].'()';
			
		if (self::$report & UDB_REPORT_FILE)
			file_put_contents(UDB_RFILE_PATH,'+---------------------------------'.PHP_EOL.date('Y-m-d H:i:s').PHP_EOL.implode(PHP_EOL,$result).PHP_EOL, FILE_APPEND | LOCK_EX);
		if (self::$report & UDB_REPORT_TEXT)
			echo nl2br(implode(PHP_EOL,$result));
			
		if (self::$stop)
			exit;

        return null;
	}
	
	public static function init($pref = null, $report = UDB_REPORT_TEXT)
	{
		self::$report = $report;

		if (!$pref || is_array($pref)){
			$pref ? (isset($pref['class']) ? null : $pref['class'] = array_keys(self::$pref_list)) : array('class' => array_keys(self::$pref_list));

			foreach($pref as $option => $value){
				switch($option){
					case 'class':
						$list = is_array($value) ? array_reverse($value) : array($value);
						foreach($list as $class)
							if (class_exists(self::$pref_list[$class]))
								self::$class = self::$pref_list[$class];
					break;
					
					case 'connect':
						if (is_array($value))
							foreach(array('user','pass','db') as $key)
								if (isset($value[$key]))
									self::$connect[$key] = $value[$key];
	
						self::$connect['host'] or self::$connect['host'] = 'localhost';
					break;
					
					case 'stop':
						self::$stop = (bool)$value;
					break;
				}
			}
		}

		self::$class or self::big_error("Coudn't initialyze UniDB.");

        self::$logger or self::$logger = new udbLogger;
	}
	
	public static function connect($host = 'localhost', $user = 'root', $pass = '', $db = '')
	{
	    /** @var mysqli $tmp */
		$tmp = new self::$class($host, $user, $pass);
		if(!$tmp->connect_errno){
			self::$links[] = self::$last = $tmp;

			$tmp->set_charset('utf8') or self::big_error($tmp);
			if ($db)
				$tmp->select_db($db) or self::big_error($tmp);

			return max(array_keys(self::$links));
		} 
		else
			self::big_error($tmp);
			
		return -1;
	}

	public static function set_charset($charset, $index = -1){
		if ($obj = ($index < 0) ? self::$last : self::$links[$index])
			$obj->set_charset($charset);
	}
	
	public static function query($que, $index = -1){
		if (!count(self::$links))
			self::connect(self::$connect['host'], self::$connect['user'], self::$connect['pass'], self::$connect['db']);

		if ($obj = ($index < 0) ? self::$last : self::$links[$index]){
            $start = microtime(true);
			$res = $obj->query($que);
            self::$logger->log($que, $start, microtime(true));

			return $obj->errno ? self::big_error($obj,$que) : $res;
		}

		return self::big_error('No link with index '.$index.' found in list.');
	}
	
	
	public static function last_insert_id($index = -1){
		if ($obj = ($index < 0) ? self::$last : self::$links[$index])
			return $obj->insert_id;
		return self::big_error('No link with index '.$index.' found in list.');
	}

	public static function insert($table, $data, $withUpdate = false, $escapeData = true, $index = -1){
		$keys = array_keys($data);

		if ($escapeData){
			foreach($data as &$v)
				$v = udb::escape_string($v,$index);
			unset($v);
		}
		
		$que = "INSERT INTO `".$table."`(`".implode('`,`',$keys)."`) VALUES('".implode("','",$data)."')";
		if ($withUpdate){
			$vals = array();
			foreach($keys as $key)
				$vals[] = "`".$key."` = VALUES(`".$key."`)";
			$que .= " ON DUPLICATE KEY UPDATE ".implode(',',$vals);
		}
		return self::query($que, $index) ? self::last_insert_id() : false;
	}

    public static function insertNull($table, $data, $withUpdate = false, $escapeData = true, $index = -1){
        $keys = array_keys($data);

        if ($escapeData){
            foreach($data as &$v)
                $v = is_null($v) ? 'NULL' : "'" . ($escapeData ? udb::escape_string($v, $index) : $v) . "'";
            unset($v);
        }

        $que = "INSERT INTO `".$table."`(`".implode('`,`',$keys)."`) VALUES(".implode(",",$data).")";
        if ($withUpdate){
            $vals = array();
            foreach($keys as $key)
                $vals[] = "`".$key."` = VALUES(`".$key."`)";
            $que .= " ON DUPLICATE KEY UPDATE ".implode(',',$vals);
        }
        return self::query($que, $index) ? self::last_insert_id() : false;
    }

    /**
     * @param string $table    table to insert data into
     * @param array $data      array of rows to insert with each element being array(fieldName => fieldValue) pairs for single row
     * @param bool $withUpdate flag for ON DUPLICATE KEY addition. Default: FALSE;
     * @param bool $escapeData flag for escaping data before inserting with udb::escape_string(). Default: TRUE;
     * @param int $index       index of DB connection in stack
     * @return bool|int        ID of last inserted row or FALSE on failure
     *
     * Function inserts multiple rows into DB table. Works by combinig inserts with same keys into single query.
     */
    public static function insert_multi(string $table, array $data, $withUpdate = false, $escapeData = true, $index = -1){
        $keys = [];
        foreach($data as $i => $row){
            if (!is_array($row))
                return self::big_error('Illegal data array on insert #' . $i);

            $tmp = json_encode(array_keys($row));
            isset($keys[$tmp]) ? $keys[$tmp][] = $row : $keys[$tmp] = [$row];
        }

        foreach($keys as $kj => $list){
            $insert = [];
            $karr   = json_decode($kj, true);

            foreach($list as $row){
                foreach($row as &$value)
                    $value = is_null($value) ? 'NULL' : "'" . ($escapeData ? udb::escape_string($value, $index) : $value) . "'";
                unset($value);

                $insert[] = '(' . implode(',', $row) . ')';
            }

            $que = "INSERT INTO `" . $table . "`(`" . implode('`,`', $karr) . "`) VALUES" . implode(',', $insert);
            if ($withUpdate)
                $que .= " ON DUPLICATE KEY UPDATE " . implode(',', array_map(function($key){
                        return '`' . $key . '` = VALUES(`' . $key . '`)';
                    }, $karr));

            $id = self::query($que, $index) ? self::last_insert_id() : false;
        }
        return $id ?? false;
    }

	public static function update($table, $data, $condition, $escapeData = true, $index = -1){
		$list = array();
		foreach($data as $key => $val)
			$list[] = "`".$key."` = '".($escapeData ? udb::escape_string($val, $index) : $val)."'";

		$que = "UPDATE `".$table."` SET ".implode(',',$list)." WHERE ".$condition;
		return self::query($que, $index);
	}

    public static function updateNull($table, $data, $condition, $escapeData = true, $index = -1){
        $list = array();
        foreach($data as $key => $val)
            $list[] = "`" . $key . "` = " . (is_null($val) ? 'NULL' : "'" . ($escapeData ? udb::escape_string($val, $index) : $val) . "'");

        $que = "UPDATE `".$table."` SET ".implode(',',$list)." WHERE ".$condition;
        return self::query($que, $index);
    }

	public static function affected_rows($index = -1){
		if ($obj = ($index < 0) ? self::$last : self::$links[$index])
			return $obj->affected_rows;
		return self::big_error('No link with index '.$index.' found in list.');
	}
	
	public static function num_rows($result){
		return property_exists($result,'num_rows') ? $result->num_rows : -1;
	}

    public static function full_list($que, $key_type = UDB_ASSOC, $index = -1){
        return self::single_list($que, $key_type, $index);
    }

	public static function single_list($que, $key_type = UDB_ASSOC, $index = -1){
		$foo = self::foo_pointer($key_type);
        /** @var mysqli_result $sql */
		$sql = self::query($que, $index);
		$res = array();

		while($row = $sql->$foo())
			$res[] = $row;
		$sql->free();
			
		return $res;
	}
	
	public static function single_row($que, $key_type = UDB_ASSOC, $index = -1){
		$foo = self::foo_pointer($key_type);
        /** @var mysqli_result $sql */
		$sql = self::query($que, $index);
		$row = $sql->$foo();
		$sql->free();
			
		return $row;
	}
	
	public static function single_value($que, $index = -1){
		$sql = self::query($que, $index);
        /** @var mysqli_result $sql */
		$row = $sql->fetch_row();
		$sql->free();
			
		return $row[0];
	}
	
	public static function single_column($que, $colIndex = 0, $index = -1){
		return self::key_value($que, null, $colIndex, $index);
	}

	public static function key_value($que, $keyIndex = 0, $valueIndex = 1, $index = -1){
        /** @var mysqli_result $sql */
		$sql = self::query($que, $index);
		$res = array();

		if (is_null($keyIndex))
			while($row = $sql->fetch_array())
				$res[] = $row[$valueIndex];
		elseif (is_array($keyIndex))
			while($row = $sql->fetch_array())
				self::key_struct($res, $keyIndex, $row, $valueIndex);
		else
			while($row = $sql->fetch_array())
				$res[$row[$keyIndex]] = $row[$valueIndex];
		$sql->free();

		return $res;
	}

	public static function key_column($que, $keyIndex = 0, $colIndex = 1, $index = -1){
	    $newKey = is_array($keyIndex) ? (end($keyIndex) === null ? $keyIndex : array_merge($keyIndex, array(null))) : (is_null($keyIndex) ? $keyIndex : array($keyIndex, null));

	    return self::key_value($que, $newKey, $colIndex, $index);
    }

	public static function key_row($que, $key, $key_type = UDB_ASSOC, $index = -1){
		$foo = self::foo_pointer($key_type);
        /** @var mysqli_result $sql */
		$sql = self::query($que, $index);
		$res = array();

		if (is_array($key))
			while($row = $sql->$foo())
				self::key_struct($res,$key,$row);
		else
			while($row = $sql->$foo())
				$res[$row[$key]] = $row;
		$sql->free();
			
		return $res;
	}
	
	public static function key_list($que, $key, $key_type = UDB_ASSOC, $index = -1){
		if (is_array($key))
			is_null(end($key)) ? null : $key[] = null;
		elseif (is_null($key))
			return self::full_list($que, $key_type, $index);
		else
			$key = array($key,null);
			
		return self::key_row($que, $key, $key_type, $index);
	}
	
	public static function found_rows($index = -1){
		return self::single_value('SELECT FOUND_ROWS()',$index);
	}
	
	public static function escape_string($str, $index = -1){
		if (!count(self::$links))
			self::connect(self::$connect['host'], self::$connect['user'], self::$connect['pass'], self::$connect['db']);

		if ($obj = ($index < 0) ? self::$last : self::$links[$index])
			return $obj->real_escape_string($str);
		else
			return self::big_error('No link with index '.$index.' found in list.');
	}

	public static function next_row($sql, $key_type = UDB_ASSOC){
		$foo = self::foo_pointer($key_type);
		return (is_object($sql) && method_exists($sql, $foo)) ? $sql->$foo() : null;
	}

	public static function set_log($mode){
	    self::$logger->set_mode($mode);
    }

    public static function wrap_fields($f){
        return array_map(function($a){
            return preg_match('/\s|[*)(`\.]/', $a) ? $a : "`" . $a . "`";
        }, is_array($f) ? $f : [$f]);
    }
}
