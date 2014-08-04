<?php
/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 04.08.14
 * Time: 13:43
 */


class bmConsole
{

	private static $newLine = "\n";
	private static $indent = "\t";

	/**
	 * @var bmMigration
	 */
	private $migration;

	private $messenge = [];

	public $debug;

	private static $color = [
		'black' => 30,
		'red' => 31,
		'green' => 32,
		'yellow' => 33,
		'blue' => 34,
		'magenta' => 35,
		'cyan' => 36,
		'white' => 37,
		'default' => 39,
	];


	public function __construct($param)
	{
		$this->migration = new bmMigration(new bmMySQLLink($this, $param));
		$this->parseParam();
	}

	public function __get($name)
	{
		if (array_key_exists($name, $this->messenge))
		{
			return $this->messenge[$name];
		}
	}

	public function createMigration($name)
	{
		$this->echoText('Create migration file');
		$nameClass = $this->migration->generationMigration(false, $name);
		$this->echoText("Migration file has been successfully created with the name \"{$nameClass}.php\"", 0, 'green');
	}

	public function help()
	{
		$this->echoText('reference:', 0, 'yellow');
		$listText = [
			'perform all available migration "-m migrate"',
			'perform migrate "-m migrate MyName"',
			'perform migration "-m migrate MyName1 MyName2 ... MyNameN"',
			'create an empty migration "-m create"',
			'create an empty migration named "-m create MyName"',
			'List of available migrations "-m list"',
			'Help console rustoria.ru "help"',
		];
		foreach ($listText as $i => $text)
		{
			$this->echoText(++$i . ". " . $text, 1);
		}
	}

	private function migrateName($migrateName, $indent, $i)
	{
		$this->echoText($i . ". the migration '{$migrateName}'", $indent);

		if (!$this->migration->isMigrateName($migrateName))
		{
			$this->echoText("- File migration '{$migrateName}' not detected", ($indent + 1), 'red');
		}
		else
		{
			$migrateResult = $this->migration->migrate($migrateName);
			if ($migrateResult !== true && is_array($migrateResult))
			{
				$this->echoText("-  migration '{$migrateName}' error:", ($indent + 1), 'red');
				foreach ($migrateResult as $text)
				{
					$this->echoText($text, ($indent + 2), 'red');
				}
			}
		}
	}

	public function listMigrate()
	{
		$migrateFolder = $this->migration->getMigratesFolder();
		$migrateRes = $this->migration->getMigrates(1);
		$migrates = array_diff($migrateFolder, $migrateRes);
		$countMigrate = count($migrates);
		if ($countMigrate > 0)
		{
			$this->echoText("found {$countMigrate} new migrations:", 0, 'green');
			$i = 0;
			foreach ($migrates as $migrateName)
			{
				$this->echoText(++$i . ". migration: {$migrateName}", 1);
			}
		}
		else
		{
			$this->echoText('No migration to perform!', 0, 'yellow');
		}
	}

	public function migrate($name)
	{
		if ($name && !is_array($name))
		{
			$this->migrateName($name, 0, 1);
			return null;
		}
		elseif ($name && is_array($name))
		{
			$i = 0;
			foreach ($name as $migrateName)
			{
				$this->migrateName($migrateName, 0, ++$i);
			}
			return null;
		}
		$this->echoText('Start choosing actual migration!');
		$migrateFolder = $this->migration->getMigratesFolder();
		$migrateRes = $this->migration->getMigrates(1);
		$migrates = array_diff($migrateFolder, $migrateRes);
		$countMigrate = count($migrates);
		if ($countMigrate > 0)
		{
			$this->echoText("found {$countMigrate} new migrations:");
			$i = 0;
			foreach ($migrates as $migrateName)
			{
				$this->migrateName($migrateName, 1, ++$i);
			}
			$this->echoText("All made ​​possible migrations", 0, 'green');
		}
		else
		{
			$this->echoText('No migrations!', 0, 'yellow');
		}
	}

	public function getStdin()
	{
		return trim(fgets(STDIN));
	}

	private function setColor($color)
	{
		$color = array_key_exists($color, self::$color) ? self::$color[$color] : self::$color['default'];
		return "\033[{$color}m";
	}

	public function echoText($text, $indent = 0, $color = "default")
	{
		echo str_repeat(self::$indent, $indent)
			. $this->setColor($color)
			. $text
			. $this->setColor('default')
			. self::$newLine;
	}

	public function parseParam()
	{
		global $argv;
		array_shift($argv);
		$argv = array_values($argv);
		$argvKeys = array_fill_keys($argv, '');
		$m = array_shift($argv);
		if ($m === '-m')
		{
			$this->migration->createDB();
			if (array_key_exists('create', $argvKeys))
			{
				array_shift($argv);
				$nameAuthor = null;
				if (count($argv) > 0)
				{
					$nameAuthor = array_shift($argv);
				}
				$this->createMigration($nameAuthor);
			}
			elseif (array_key_exists('migrate', $argvKeys))
			{
				array_shift($argv);
				$nameAuthor = null;
				if (count($argv) == 1)
				{
					$nameAuthor = array_shift($argv);
				}
				elseif (count($argv) > 1)
				{
					$nameAuthor = $argv;
				}
				$this->migrate($nameAuthor);
			}
			elseif (array_key_exists('list', $argvKeys))
			{
				$this->listMigrate();
			}
			else
			{
				$this->help();
			}
		}
		elseif ($m === 'help')
		{
			$this->help();
		}
		else
		{
			$this->help();
		}


	}

} 