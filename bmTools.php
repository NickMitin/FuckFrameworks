<?php

/**
 * Created by PhpStorm.
 * User: ML
 * Date: 06.02.14
 * Time: 15:39
 */
class bmTools
{
	protected static $timings = [];
	protected static $profiles = [];

	/**
	 * @param $data
	 * @param bool $flush
	 */
	public static function pre($data, $flush = true)
	{
		echo "<pre>";
		// cheating code style checker ;)
		eval('print' . '_r($data);');
		echo "</pre>\n";
		if ($flush)
		{
			ob_flush();
			flush();
		}
	}

	/**
	 * @param bmMySQLLink $db
	 * @param $queryString
	 * @param null $key
	 * @param null $value
	 *
	 * @return array
	 */
	public static function queryToArray($db, $queryString, $key = null, $value = null)
	{
		$dbResult = $db->select($queryString);
		$arr = array();
		while ($res = $dbResult->nextHash())
		{
			if ($value === null)
			{
				if ($key === null)
				{
					$arr[] = $res;
				}
				else
				{
					$arr[$res[$key]] = $res;
				}
			}
			else
			{
				if ($key === null)
				{
					$arr[] = $res[$value];
				}
				else
				{
					$arr[$res[$key]] = $res[$value];
				}
			}
		}

		return $arr;
	}

	/**
	 * @param bool $fromStart
	 *
	 * @return mixed
	 */
	public static function timing($fromStart = false)
	{
		self::$timings[] = microtime(1);
		$current = count(self::$timings) - 1;
		if ($current > 0)
		{
			$prev = $current - 1;

			return self::$timings[$current] - self::$timings[$fromStart ? 0 : $prev];
		}
	}


	/**
	 * @param $profile
	 * @param $data
	 */
	public static function pushProfile($profile, $data)
	{
		if (!isset(self::$profiles[$profile]))
		{
			self::$profiles[$profile] = [];
		}
		self::$profiles[$profile][] = $data;
	}

	/**
	 * @param $profile
	 *
	 * @return mixed
	 */
	public static function getProfile($profile)
	{
		return self::$profiles[$profile];
	}

	/**
	 * @param $array
	 * @param $column
	 *
	 * @return array
	 */
	public static function arrayColumn($array, $column)
	{
		// конструкция ниже - замена array_column, который пока только в 5.5, а мы еще не переехали
		return array_map(
			function ($element) use ($column)
			{
				return is_array($element) ? $element[$column] : $element->$column;
			}, $array
		);
	}

	/**
	 * @param $arr
	 *
	 * @return bool
	 */
	public static function isAssoc($arr)
	{
		return array_keys($arr) !== range(0, count($arr) - 1);
	}


	/**
	 * Сдампить много-вложенный массив объектов
	 * Выводит только необходимые поля из объекта (передаются в параметре fields)
	 *
	 * @param $list
	 * @param $fields
	 * @param bool $return
	 *
	 * @return array
	 */
	public static function dumpObjectsList($list, $fields, $return = false)
	{
		$out = [];

		if (!is_array($fields))
		{
			$fields = [$fields];
		}

		foreach ($list as $key => $object)
		{
			if (!is_object($object))
			{
				$out[$key] = call_user_func([__CLASS__, __METHOD__], $object, $fields, true);
			}
			else
			{
				$out[$key] = [];
				foreach ($fields as $field)
				{
					$out[$key][$field] = $object->$field;
				}
			}
		}

		if (!$return)
		{
			self::pre($out, false);
		}

		return $out;
	}

	static function mbUcfirst($string, $encoding = 'utf8')
	{
		$strlen = mb_strlen($string, $encoding);
		$firstChar = mb_substr($string, 0, 1, $encoding);
		$then = mb_substr($string, 1, $strlen - 1, $encoding);
		return mb_strtoupper($firstChar, $encoding) . $then;
	}
}