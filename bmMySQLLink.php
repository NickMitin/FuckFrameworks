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


class bmMySQLLink extends bmFFObject
{

	/**
	 * @var mysqli
	 */
	private $mysqli = 0;

	public $persistentConnection = 0;
	public $host = 'localhost';
	public $database = 'default';
	public $user = 'root';
	public $password = '';
	public $time = 0;
	public $queriesCount = 0;
	public $profiler = '';
	public $queryDubles = array();

	public $queriesQuantity = 0; // for debug puroses // Andrew Kolpakov

	public function __construct($application, $parameters = array())
	{
		parent::__construct($application, $parameters);
		$this->connect();
	}

	public function quoteSmart($value)
	{
		// если magic_quotes_gpc включена - используем stripslashes
		if (get_magic_quotes_gpc()) {
			$value = stripslashes($value);
		}
		// Если переменная - число, то экранировать её не нужно
		// если нет - то окружем её кавычками, и экранируем
		if (!is_numeric($value)) {
			$value = "'" . $this->mysqli->real_escape_string($value) . "'";
		}
		return $value;
	}

	public function beginTransaction()
	{
		$this->mysqli->autocommit(false);
	}



	public function commit()
	{
		$this->mysqli->commit();
		$this->mysqli->autocommit(true);
	}

	public function connect()
	{
		$fileName = projectRoot . '/conf/db_' . $this->database . '.conf';
		if (!file_exists($fileName))
		{
			$fileName = projectRoot . '/conf/db_default.conf';
		}

		require $fileName;

		if ($this->linkId == 0)
		{
			$serverIp = $this->host;

			if ($this->persistentConnection)
			{
				$this->mysqli = new mysqli('p:' . $serverIp, $this->user, $this->password, $this->database);
			}
			else
			{
				$this->mysqli = new mysqli($serverIp, $this->user, $this->password, $this->database);
			}

			if (!$this->mysqli)
			{
				print    "Connection failed";
			}


			$this->mysqli->set_charset("utf8");
		}
	}

	public function query($sqltext)
	{
		//echo($sqltext ."\n--------------------\n");

		if (($this->mysqli) && ($sqltext != ''))
		{
			//file_put_contents(documentRoot . '/sql.txt' ,$_SERVER["REQUEST_URI"] . "\n" . '=============================================<br />' . $sqltext . '<br />==============================' . "\n", FILE_APPEND);

			$timeStart = microtime(true);
			$result = $this->mysqli->query($sqltext, $this->linkId);
			if (defined('C_BM_DEBUG') && C_BM_DEBUG === true && $this->application->bmDebug)
			{
				$this->application->bmDebug->query($sqltext);
				$this->application->bmDebug->stopTimer();
			}
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
			if ($this->application->debug)
			{
				bmTools::pushProfile('sql', [$sqltext, $time]);
			}

			if ($result)
			{
				return $result;
			}
			else
			{

				error_log("-----");
				error_log("-- MySQL error:\n   " . $this->mysqli->error);
				error_log("-- Request:\n   " . $sqltext);

				//error_log("-- Backtrace:\n   " . $sqltext);
				//$bt = debug_backtrace();
				//error_log(var_export($bt, true));

				error_log("-----\n\n");

				if ($this->application->debug)
				{
					print $sqltext . "\n" . $this->mysqli->error;
					echo '<pre>';
				}
			}
		}

		return false;
	}

	public function disconnect()
	{
		if ($this->mysqli)
		{
			$this->mysqli->close();
			$this->mysqli = null;
		}
	}

	public function nextObject($cursor)
	{
		return $cursor->fetch_object();
	}

	public function nextHash($cursor)
	{
		return $cursor->fetch_assoc();
	}

	public function nextRow($cursor)
	{
		return $cursor->fetch_row();
	}

	public function formatInput($value)
	{
		return $this->mysqli->escape_string($value);
	}

	public function ffTypeToNativeType($type, $defaultValue = '')
	{
		$defaultValue = $this->formatInput($defaultValue, $type);
		switch ($type)
		{
			case BM_VT_TEXT:
				$result = " LONGTEXT NOT NULL";
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


	public function rowCount($cursor)
	{
		return $cursor->num_rows;
	}

	public function free($cursor)
	{
		return $cursor->free();
	}

	public function insertId()
	{
		return $this->mysqli->insert_id;
	}

	function getValue($sqlText, $rowNumber = 0, $fieldName = 0)
	{
		$cursor = $this->query($sqlText);
		$result = null;
		if ($cursor->num_rows > 0)
		{
			if ($cursor->data_seek($rowNumber))
			{
				$result = $cursor->fetch_row();
				$result = $result[$fieldName];
			}
		}
		$this->free($cursor);

		return $result;
	}

	function getObject($sqlText)
	{
		$cursor = $this->query($sqlText);
		if ($cursor->num_rows == 1)
		{
			$result = $this->nextObject($cursor);
		}
		else
		{
			$result = false;
		}
		$this->free($cursor);

		return $result;
	}

	function select($queryText)
	{
		return new bmDataQuery($this, $queryText);
	}

	public function getColumn($sqlText, $index = 0)
	{
		$cursor = $this->query($sqlText);
		if ($cursor->num_rows > 0)
		{
			$result = array();
			while ($row = $this->nextRow($cursor))
			{
				$result[] = $row[$index];
			}
		}
		else
		{
			$result = false;
		}
		$this->free($cursor);

		return $result;
	}

	public function tableExists($tableName)
	{
		$cursor = $this->query("SHOW TABLES LIKE '" . $this->formatInput($tableName) . "';");
		$result = ($cursor->num_rows > 0);
		$this->free($cursor);

		return $result;
	}

	public function tableColumnExists($tableName, $columnName)
	{
		$cursor = $this->query("SHOW COLUMNS FROM `{$tableName}` LIKE " . $this->quoteSmart($columnName) . ";");
		$result = ($cursor->num_rows > 0);
		$this->free($cursor);

		return $result;
	}

}

class bmDataQuery
{
	private $instance = null;
	private $dataLink = null;

	public function bmDataQuery($dataLink, $queryText)
	{
		$this->dataLink = $dataLink;
		$this->instance = $this->dataLink->query($queryText);
	}

	public function rowCount()
	{
		return $this->dataLink->rowCount($this->instance);
	}

	public function nextObject()
	{
		return $this->dataLink->nextObject($this->instance);
	}

	public function nextHash()
	{
		return $this->dataLink->nextHash($this->instance);
	}

	public function nextRow()
	{
		return $this->dataLink->nextRow($this->instance);
	}

	public function insertId()
	{
		return $this->dataLink->insertId();
	}

	public function free()
	{
		return $this->dataLink->free($this->instance);
	}

}

?>