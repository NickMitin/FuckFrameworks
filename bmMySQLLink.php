<?php

class bmMySQLLink extends bmFFObject {

	private $linkId = 0;
	
	public $persistentConnection = 0;
	public $server = 'localhost';
	public $database = 'default';
	public $user = 'root';
	public $password = '';
	public $time = 0;
	public $queriesCount = 0;
	public $profiler = '';
	public $queryDubles = array();
	
	public function __construct($application, $parameters = array()) {
		parent::__construct($application, $parameters);
		$this->connect();
	}

	public function connect() {
		
		require(projectRoot . '/conf/db_' . $this->database . '.conf');
		if ($this->database == 'weboadmin')
		{
			$this->server = 'prod1.weborama.ru';
		}
		if ($this->linkId == 0) {
			if ($this->persistentConnection) {
				$this->linkId = mysql_pconnect($this->server, $this->user, $this->password);
			} else {
				$this->linkId = mysql_connect($this->server,$this->user, $this->password, true);
			}
			if (!$this->linkId) {
				//ERROR HERE "Connection failed"
			}
			
			if(!mysql_select_db($this->database, $this->linkId)) {
				//ERROR HERE "Select database failed"
			}       
			mysql_query("set names 'utf8';");
		}   
	}

	public function query($sqltext) { 
		if (($this->linkId) && ($sqltext != '')) 
		{
			$startTime = microtime(true);
			$result = mysql_query($sqltext, $this->linkId);
			if ($this->application->debug)
			{
				$currentTime = microtime(true) - $startTime;
				$this->time += $currentTime;
				++$this->queriesCount;
			}

			if ($result) {
				return $result;
			} else {
        if ($this->application->debug) 
        {
				  print $sqltext . "\n" . mysql_error($this->linkId);
        }
			}
		}
		
		return false;
	}

	public function disconnect() {
		if ($this->linkId != 0) {
			$this->linkId = 0;
			mysql_close($this->linkId);
		}
	}
	
	public function nextObject($cursor) {
		return mysql_fetch_object($cursor);
	}
	
	public function nextHash($cursor) {
		return mysql_fetch_assoc($cursor);
	}
	
	public function nextRow($cursor) {
		return mysql_fetch_row($cursor);
	}
	
	public function formatInput($value) {
		return mysql_real_escape_string($value, $this->linkId);
	}

	
	public function rowCount($cursor) {
		return mysql_num_rows($cursor);
	}
	
	public function free($cursor) {
		return mysql_free_result($cursor);
	}
	
	public function insertId() {
		return mysql_insert_id($this->linkId);
	}
	
	function getValue($sqlText, $rowNumber = 0, $fieldName = 0) 
	{
		$cursor = $this->query($sqlText);
		if (mysql_num_rows($cursor) > 0) {
			$result = mysql_result($cursor, $rowNumber, $fieldName);
		} else {
			$result = null;
		}                                                     
		$this->free($cursor);
		return $result;
	}
	
	function getObject($sqlText) {
		$cursor = $this->query($sqlText);
		if (mysql_num_rows($cursor) == 1) {
			$result = $this->nextObject($cursor);
		} else {
			$result = false;
		}
		$this->free($cursor); 
		return $result;
	}
	
	function select($queryText) {
		
		return new bmDataQuery($this, $queryText);
		
	}
	
	public function getColumn($sqlText, $index = 0)
	{
		$cursor = $this->query($sqlText);
		if (mysql_num_rows($cursor) > 0) {
			$result = array();
			while ($row = $this->nextRow($cursor))
			{
				$result[] = $row[$index];
			}
		} else {
			$result = false;
		}
		$this->free($cursor);
		return $result;
	}   

}

class bmDataQuery {
	
	private $instance = null;
	private $dataLink = null;
	
	public function bmDataQuery($dataLink, $queryText) {
		
		$this->dataLink = $dataLink;
		$this->instance = $this->dataLink->query($queryText);
		
	}
	
	public function rowCount() {
		return $this->dataLink->rowCount($this->instance);
	}
	
	public function nextObject() {
		return $this->dataLink->nextObject($this->instance);
	}
	
	public function nextHash() {
		return $this->dataLink->nextHash($this->instance);
	}
	
	public function nextRow() {
		return $this->dataLink->nextRow($this->instance);
	}
	
	public function insertId() {
		return $this->dataLink->insertId();
	}
	
	public function free() {
		return $this->dataLink->free($this->instance);
	}
	
}

?>