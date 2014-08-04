<?php

/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 15.01.14
 * Time: 11:35
 */

class bmMigration
{


	/**
	 * @var bmMySQLLink
	 */
	private $db;

	private $sqls,
		$commits,
		$objects,
		$fields;

	public function __construct($db)
	{
		$this->db = $db;
		$this->sqls =
		$this->objects =
		$this->fields =
		$this->commits = [];
	}

	public function addSql($sql)
	{
		array_push($this->sqls, $sql);
	}

	public function addCommit($commit)
	{
		array_push($this->commits, " * {$commit}");
	}

	public function addObject($object)
	{
		array_push($this->objects, $this->db->quoteSmart($object));
	}

	public function addField($object, $field)
	{
		$this->fields["{$this->db->quoteSmart($field)}"] = $this->db->quoteSmart($object);
	}

	public function getSqls()
	{
		return $this->sqls;
	}

	public function getObjects()
	{
		return $this->objects;
	}

	public function getFields()
	{
		return $this->fields;
	}

	public function getCommits()
	{
		return $this->commits;
	}

	public function isSqls()
	{
		return count($this->getSqls()) > 0;
	}

	private function _getName($name)
	{
		if ($name)
		{
			$name = time() . "_" . $name;
		}
		else
		{
			$name = time() . '_m';
			if (isset($_SERVER) && array_key_exists('HTTP_HOST', $_SERVER))
			{
				$host = explode('.', $_SERVER['HTTP_HOST']);
				$name = time();
				$name .= ('_' . array_shift($host));
			}
		}


		$name = "bm" . ucfirst($name);

		return $name;
	}

	private function _getTemplate($templateName, $escape = true, $path = projectRoot)
	{
		$template = file_get_contents($path . '/templates/' . $templateName . '.html');
		if ($escape)
		{
			$template = addcslashes($template, '"');
		}

		return $template;
	}

	public function getMigrates($isUp = null)
	{
		$where = !is_null($isUp) ? " where isUp = {$isUp} " : null;
		$migrateRes = $this->db->select("select * from migration {$where} order by id asc");
		$migrates = [];
		if ($migrateRes->rowCount() > 0)
		{
			while ($migrate = $migrateRes->nextObject())
			{
				array_push($migrates, $migrate->name);
			}
		}

		return $migrates;
	}

	public function getMigratesFolder()
	{
		if (!is_dir(projectRoot . '/migration'))
		{
			mkdir(projectRoot . '/migration');
		}
		$fullNameFile = projectRoot . '/migration/*';
		$files = glob($fullNameFile);
		if (is_array($files) && count($files) > 0)
		{
			$files = array_map(
				function ($item)
				{
					$item = basename($item);
					$item = trim($item, '.php ');
					return $item;
				}, $files
			);

			sort($files);
			return $files;
		}
		return [];
	}

	public function isMigrateName($name)
	{
		if (!is_dir(projectRoot . '/migration'))
		{
			mkdir(projectRoot . '/migration');
		}
		$fullNameFile = projectRoot . '/migration/' . $name . '.php';
		return file_exists($fullNameFile);
	}

	public function migrate($name)
	{
		$migrate = new $name();
		$migrateResult = $migrate->migrate();
		if ($migrateResult === true)
		{
			$date = new bmDateTime();
			$dateSql = $date->format("Y-m-d H:i:s");
			$sql = "
				insert into
					migration
					(`name`, `date`, `isUp`)
				values
					('{$name}', '{$dateSql}', 1)
				";
			$this->db->query($sql);
		}
		return $migrateResult;
	}

	public function createDB()
	{
		if (!$this->db->tableExists('migration'))
		{
			$sql = "
			CREATE TABLE `migration`
			(
				`id` INT PRIMARY KEY NOT NULL AUTO_INCREMENT,
				`name` VARCHAR(255) NOT NULL,
				`date` DATETIME NOT NULL,
				`isUp` INT NOT NULL
			);
		";
			$this->db->query($sql);
		}
	}

	public function generationMigration($isNotSqls = true, $name = null)
	{
		if (!$this->isSqls() && $isNotSqls)
		{
			return false;
		}

		$dbUp = [];

		array_push($dbUp, "// \$this->execute(\"insert ignore into ....\");");
		array_push($dbUp, "\t\t// \$this->execute(\"update ....\");");

		if ($this->isSqls())
		{
			foreach ($this->getSqls() as $sql)
			{
				array_push($dbUp, "\t\t\$this->execute(\"{$sql}\");");
			}
		}

		$date = new bmDateTime();

		$time = $date->format("H:i:s");
		$date = $date->format("d.m.Y");

		$nameClass = $this->_getName($name);

		if (!is_dir(projectRoot . '/migration'))
		{
			mkdir(projectRoot . '/migration');
		}

		$fullNameFile = projectRoot . '/migration/' . $nameClass . '.php';

		$dbUp = implode("\n", $dbUp);

		$commits = $this->getCommits();
		array_push($commits, ' *');
		$commit = implode("\n", $commits);

		$objects = $this->getObjects();
		$object = '';
		if (count($objects) > 0)
		{
			$object = implode(", ", $objects);
		}

		$fields = $this->getFields();
		$field = '';
		if (count($fields) > 0)
		{
			array_walk(
				$fields, function (&$v, $k)
				{
					$v = "[{$v} => {$k}]";
				}
			);
			$field = implode(", ", $fields);
		}

		$class = '';

		eval('$class  = "' . $this->_getTemplate('/autogeneration/migration') . '";');

		if ($class)
		{
			file_put_contents($fullNameFile, $class);
		}

		return $nameClass;
	}

}