<?php

/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 04.08.14
 * Time: 13:52
 */
abstract class bmIMigrate
{

	/**
	 * @var bmLog
	 */
	private $log;

	/**
	 * @var bmMySQLLink
	 */
	private $db;

	/**
	 * @var bmCacheLink
	 */
	private $cacheLink;

	public $debug;

	public function __construct()
	{
		$this->log = new bmLog();
		$this->cacheLink = new bmCacheLink($this);
		$this->db = new bmMySQLLink($this, array('database' => 'write'));
	}

	public function migrate()
	{
		$this->db->beginTransaction();
		$migrateUp = $this->up();
		$this->db->commit();

		return $migrateUp;
	}

	public function up()
	{

	}

	protected function validateObjects($objects)
	{
		$messenges = [];
		if (count($objects) > 0)
		{
			foreach ($objects as $object)
			{
				if ($this->db->tableExists($object))
				{
					array_push($messenges, "Table `{$object}` already exists in your database!");
				}
			}
		}

		return (count($messenges) > 0) ? $messenges : true;
	}

	protected function validateFields($fields)
	{
		$messenges = [];
		if (count($fields) > 0)
		{
			foreach ($fields as $obj)
			{
				$object = $field = null;
				foreach ($obj as $ob => $fi)
				{
					$object = $ob;
					$field = $fi;
				}
				if (!$object || !$field)
				{
					array_push($messenges, "Fatal error object or field empty!");
				}
				if (!$this->db->tableExists($object))
				{
					array_push($messenges, "Table `{$object}` in which you are going to insert a field `{$field}` does not exist in your database!");
				}
				elseif ($this->db->tableColumnExists($object, $field))
				{
					array_push($messenges, "Field `{$field}` already exists in your table `{$object}`!");
				}
			}
		}

		return (count($messenges) > 0) ? $messenges : true;
	}

	public function execute($sql)
	{
		$this->db->query($sql);
		$this->log->add($sql);
	}

} 