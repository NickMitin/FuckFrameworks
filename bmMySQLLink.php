<?php
  /*
  * Copyright (c) 2009, "The Blind Mice Studio"
  * All rights reserved.
  * 
  * Redistribution and use in source and binary forms, with or without
  * modification, are permitted provided that the following conditions are met:
  * - Redistributions of source code must retain the above copyright
  *   notice, this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright
  *   notice, this list of conditions and the following disclaimer in the
  *   documentation and/or other materials provided with the distribution.
  * - Neither the name of the "The Blind Mice Studio" nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY "The Blind Mice Studio" ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL "The Blind Mice Studio" BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
  * 
  */

	
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
  
  public $queriesQuantity = 0; // for debug puroses // Andrew Kolpakov
	
	public function __construct($application, $parameters = array()) {
		parent::__construct($application, $parameters);
		$this->connect();
	}

	public function connect() {
		
    $fileName = projectRoot . '/conf/db_' . $this->database . '.conf';
    if (!file_exists($fileName))
    {
      $fileName = projectRoot . '/conf/db_default.conf';  
    }
    
    require($fileName);
		
		if ($this->linkId == 0) 
    {
			if ($this->persistentConnection) 
      {
				$this->linkId = mysql_pconnect($this->server, $this->user, $this->password);
			}
      else 
      {
				$this->linkId = mysql_connect($this->server,$this->user, $this->password, true);
			}
			if (!$this->linkId) {
				print  "Connection failed";
			}
			
			if(!mysql_select_db($this->database, $this->linkId))
      {
				print "Select database failed";
			}       
			mysql_query("set names 'utf8';");
		}   
	}

	public function query($sqltext) { 
		if (($this->linkId) && ($sqltext != '')) 
		{
			//file_put_contents(documentRoot . '/sql.txt' ,$_SERVER["REQUEST_URI"] . "\n" . '=============================================<br />' . $sqltext . '<br />==============================' . "\n", FILE_APPEND);
      
      $timeStart = microtime(true);  
      $result = mysql_query($sqltext, $this->linkId);
      ++$this->queriesCount;
      $timeEnd = microtime(true);
      $time = $timeEnd - $timeStart;
      if (BM_C_VERBOSE >= 10)
      {
        echo '<hr>';
        echo $sqltext . '<br />';
        echo $time;
        echo '</hr>';
      }
			
      if ($result) {
				return $result;
			} else {
        if ($this->application->debug) 
        {
				  print $sqltext . "\n" . mysql_error($this->linkId);
          echo '<pre>';
          //throw new Exception();
        }
			}
		}
		
		return false;
	}

	public function disconnect() {
		if ($this->linkId != 0) 
    {
			mysql_close($this->linkId);
      $this->linkId = 0;  
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
  
  public function ffTypeToNativeType($type, $defaultValue = '') 
  {
    $defaultValue = $this->formatInput($defaultValue, $type);
    switch ($type)
    {
      case BM_VT_TEXT:
        $result = " TEXT NOT NULL DEFAULT '" . $defaultValue . "'";
      break;
      case BM_VT_STRING:
        $result = " VARCHAR(255) NOT NULL DEFAULT '" . $defaultValue . "'";
      break;
      case BM_VT_INTEGER:
        $result = " INT(10) NOT NULL DEFAULT '" . $defaultValue . "'";
      break;
      case BM_VT_FLOAT:
        $result = " DOUBLE NOT NULL DEFAULT '" . $defaultValue . "'";
      break;
      case BM_VT_DATETIME:
        $result = " DATETIME NOT NULL DEFAULT '" . $defaultValue . "'";
      break;
      case BM_VT_OBJECT:
        $result = " INT(10) UNSIGNED";
      break;
      default:
        $result = " VARCHAR(255) NOT NULL DEFAULT '" . $this->formatInput($defaultValue) . "'";
      break;
    }
    return $result;
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
  
  public function tableExists($tableName)
  {
    $cursor = $this->query("SHOW TABLES LIKE '" . $this->formatInput($tableName) . "';");
    $result = (mysql_num_rows($cursor) > 0);
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