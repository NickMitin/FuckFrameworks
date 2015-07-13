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

/* Константы типа связанного объекта
  *  BM_ROT = BM ReferencedObjectType
  */

define('BM_ROT_MAIN', 1); // Главный объект связи
define('BM_ROT_DEPENDED', 2); // Зависимый объект связи
define('BM_ROT_ADDITIONAL', 3); // Добавочные, справочные объекты связи
define('BM_ROT_NOT_AN_OBJECT', 4); // Простое свойство, а не объект

abstract class bmDataObject extends bmFFObject
{

	public $map = array();
	public $objectName = '';
	public $dirty = array();
	public $dirtyQueue = array();
	protected $readonly = false;
	public $updateCount = 0;
	public $runningCheckDirty = false;
	public $storage = 'rdbs+dods';
	private $cacheQueue = array();

	public function getMethodObjects($link, $param, $load = true)
	{
		$cacheKey = strpos($link, 'link_') !== false ? substr($link, 5) : $link;
		$cacheKey .= '_' . $this->properties['identifier'];

		if (!$param['fields'])
		{
			$sql = "
				SELECT
				  `{$link}`.`{$param['object']}Id` AS `identifier`
				FROM
				  `{$link}`
				WHERE
				  `{$link}`.`{$this->objectName}Id` = " . $this->properties['identifier'] . ";
			  ";

			if (!$load)
			{
				$this->properties["old" . ucfirst($param['object']) . "Ids"] = $this->getSimpleLinks($sql, $cacheKey, $param['object'], E_OBJECTS_NOT_FOUND, $load);

				return $this->properties["old" . ucfirst($param['object']) . "Ids"];
			}
			else
			{
				return $this->getSimpleLinks($sql, $cacheKey, $param['object'], E_OBJECTS_NOT_FOUND, $load);
			}
		}
		else
		{
			$where = [];
			$map = array(
				"{$this->objectName} IS {$this->objectName}" => 5,
				"{$param['object']} IS {$param['object']}" => 5
			);
			foreach ($param['fields'] as $field => $type)
			{
				array_push($where, "`{$link}`.`{$field}` AS `{$field}`");
				$map[$field] = $type;
			}

			if ($param['objects'])
			{
				foreach ($param['objects'] as $field)
				{
					array_push($where, "`{$link}`.`{$field}Id` AS `{$field}Id`");
					$map["{$field} IS {$field}"] = 5;
				}
			}

			$where = implode(",\n", $where);
			$sql = "
				SELECT
				  `{$link}`.`{$this->objectName}Id` AS `{$this->objectName}Id`,
				  `{$link}`.`{$param['object']}Id` AS `{$param['object']}Id`,

				  {$where}
				FROM
				  `{$link}`
				WHERE
				  `{$link}`.`{$this->objectName}Id` = " . $this->properties['identifier'] . ";
			  ";

			if (!$load)
			{
				$this->properties["old" . ucfirst($param['object']) . "Ids"] = $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, $load);

				return $this->properties["old" . ucfirst($param['object']) . "Ids"];
			}
			else
			{
				return $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, $load);
			}
		}
	}

	public function addMethodObject($object, $param)
	{
		$objectIds = $this->{$object . 'Ids'};

		$objectId = $param[$object . 'Id'];

		if (count($param) > 1)
		{
			if (!$this->itemExists($objectId, $object . 'Id', $objectIds))
			{
				$item = new stdClass();
				foreach ($param as $var => $val)
				{
					$item->{$var} = $val;
				}
				$this->properties[$object . 'Ids'][] = $item;

				$this->dirty['save' . ucfirst($object) . 's'] = true;
			}
		}
		else
		{
			if (!in_array($objectId, $objectIds))
			{
				$this->properties[$object . 'Ids'][] = $objectId;
			}

			$this->dirty['save' . ucfirst($object) . 's'] = true;
		}


		return $this;
	}

	public function removeMethodObject($object, $objectId, $link)
	{
		$objectIds = $this->{$object . 'Ids'};

		if ($link)
		{
			$key = array_search($objectId, $this->properties[$object . 'Ids']);

			if ($key !== false)
			{
				unset($this->properties[$object . 'Ids'][$key]);
				array_unique($this->properties[$object . 'Ids']);
				$this->dirty['save' . ucfirst($object) . 's'] = true;
			}
		}
		else
		{
			foreach ($objectIds as $key => $identifier)
			{
				if ($identifier == $objectId)
				{
					unset($this->properties[$object . 'Ids'][$key]);
				}
			}

			$this->dirty['save' . ucfirst($object) . 's'] = true;
		}

		return $this;
	}

	public function removesMethodObject($object)
	{
		$this->properties[$object . 'Ids'] = array();


		$this->dirty['save' . ucfirst($object) . 's'] = true;

		return $this;
	}

	protected function saveMethodObject($link, $param)
	{
		$cacheKeyLink = strpos($link, 'link_') !== false ? substr($link, 5) : $link;
		$object = $param['object'];

		if ($param['fields'])
		{
			if(defined('USE_SEPARATE_DB_FOR_WRITE') &&  USE_SEPARATE_DB_FOR_WRITE == 1)
			{
				$dataLink = $this->application->dataLinkWrite;
			} else {
				$dataLink = $this->application->dataLink;
			}
			$cacheLink = $this->application->cacheLink;

			$cacheKeysToDelete = array();
			$cacheKeysToDelete[] = $cacheKeyLink . 's_' . $this->properties['identifier'];

			$oldObjectIds = $this->properties['old' . ucfirst($object) . 'Ids'];
			$objectIds = $this->properties[$object . 'Ids'];

			$itemsToDelete = $this->itemDiff($oldObjectIds, $objectIds, $object . 'Id');
			$itemsToAdd = $this->itemDiff($objectIds, $oldObjectIds, $object . 'Id');

			foreach ($itemsToDelete as $itemToDelete)
			{
				$objectId = $itemToDelete->{$object . 'Id'};
				$cacheKeysToDelete[] = $cacheKeyLink . 's_' . $objectId;
			}

			foreach ($cacheKeysToDelete as $cacheKey)
			{
				$cacheLink->delete($cacheKey);
			}

			if (count($itemsToDelete) > 0)
			{
				$sql = "
					DELETE FROM
						`{$link}`
				  	WHERE
						`{$this->objectName}Id` = " . $this->properties['identifier'] . "
            	  		AND `{$object}Id` IN (" . $this->itemImplode($itemsToDelete, $object . 'Id') . ");";

				$dataLink->query($sql);
			}

			$insertStrings = $insertStringsField = array();

			foreach ($itemsToAdd as $item)
			{
				$insertStringsField = array(
					"`{$this->objectName}Id`",
					"`{$object}Id`",
				);
				$itemVal = '('
					. $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER)
					. ', '
					. $dataLink->formatInput($item->{$object . 'Id'}, 5);

				if ($param['objects'])
				{
					foreach ($param['objects'] as $fieldObject)
					{
						$itemVal .= ', ' . $dataLink->formatInput($item->{$fieldObject . 'Id'}, 5);
						array_push($insertStringsField, "`{$fieldObject}Id`");
					}
				}
				if ($param['fields'])
				{
					foreach ($param['fields'] as $field => $type)
					{
						$itemVal .= ', ' . $dataLink->formatInput($item->{$field}, $type);
						array_push($insertStringsField, "`{$field}`");
					}
				}
				$itemVal .= ')';
				$insertStrings[] = $itemVal;
			}

			if (count($insertStrings) > 0)
			{
				$sql = "
					INSERT IGNORE INTO
                  		`{$link}`
                  		(" . implode(', ', $insertStringsField) . ")
                	VALUES
                  		" . implode(', ', $insertStrings) . ";";

				$dataLink->query($sql);
			}

			$this->enqueueCache('save' . ucfirst($object) . 's');
			$this->dirty['save' . ucfirst($object) . 's'] = false;

			$this->properties['old' . ucfirst($object) . 'Ids'] = $this->properties[$object . 'Ids'];
		}
		else
		{
			if(defined('USE_SEPARATE_DB_FOR_WRITE') &&  USE_SEPARATE_DB_FOR_WRITE == 1)
			{
				$dataLink = $this->application->dataLinkWrite;
			} else {
				$dataLink = $this->application->dataLink;
			}
			$cacheLink = $this->application->cacheLink;

			$cacheKeysToDelete = array();
			$cacheKeysToDelete[] = $cacheKeyLink . 's_' . $this->properties['identifier'];

			$oldObjectIds = $this->properties['old' . ucfirst($object) . 'Ids'];
			$objectIds = $this->properties[$object . 'Ids'];

			$idsToDelete = array_diff($oldObjectIds, $objectIds);
			$idsToAdd = array_diff($objectIds, $oldObjectIds);

			foreach ($idsToDelete as $idToDelete)
			{
				$cacheKeysToDelete[] = $cacheKeyLink . 's_' . $idToDelete;
			}

			foreach ($cacheKeysToDelete as $cacheKey)
			{
				$cacheLink->delete($cacheKey);
			}

			if (count($idsToDelete) > 0)
			{
				$sql = "
          			DELETE FROM
            			`{$link}`
          			WHERE
            			`{$this->objectName}Id` = " . $this->properties['identifier'] . "
            		AND `{$object}Id` IN (" . implode(', ', $idsToDelete) . ");";

				$dataLink->query($sql);
			}

			$insertStrings = array();

			foreach ($idsToAdd as $identifier)
			{
				$insertStrings[] = '(' . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER)
					. ", " . $dataLink->formatInput($identifier, BM_VT_INTEGER) . ')';
			}

			if (count($insertStrings) > 0)
			{
				$sql = "
					INSERT IGNORE INTO
                  		`{$link}`
                  		(`{$this->objectName}Id`, `{$object}Id`)
					VALUES
                  		" . implode(', ', $insertStrings) . ";";

				$dataLink->query($sql);
			}

			$this->enqueueCache('save' . ucfirst($object) . 's');
			$this->dirty['save' . ucfirst($object) . 's'] = false;

			$this->properties['old' . ucfirst($object) . 'Ids'] = $this->properties[$object . 'Ids'];
		}

		return $this;
	}

	public function __construct(bmApplication $application, $parameters = array())
	{
		$this->map['identifier'] = array(
			'fieldName' => 'id',
			'dataType' => BM_VT_INTEGER,
			'defaultValue' => 0
		);

		$this->map['deleted'] = array(
			'fieldName' => 'deleted',
			'dataType' => BM_VT_INTEGER,
			'defaultValue' => 0
		);

		$this->events = array('save', 'load', 'delete', 'propertyChange');

		foreach ($this->map as $propertyName => $property)
		{
			$this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $property['defaultValue']);
		}

		parent::__construct($application, $parameters);

		foreach ($this->map as $propertyName => $property)
		{
			if (array_key_exists($propertyName, $parameters))
			{
				if (!property_exists($this, $propertyName))
				{
					$this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $parameters[$propertyName]);
				}
			}
		}
		if (array_key_exists('identifier', $parameters) && ($parameters['identifier'] !== 0 && $parameters['identifier'] != ''))
		{
			if (!array_key_exists('load', $parameters) || $parameters['load'] != false)
			{
				$this->load();
			}
		}
		else
		{
			$this->dirty['store'] = true;
		}

	}

	protected function formatProperty($propertyName, $dataType, $value)
	{
		switch ($dataType)
		{
			case BM_VT_DATETIME:
				$result = new bmDateTime($value);
				break;

			case BM_VT_INTEGER:
				$result = intval($value);
				break;

			case BM_VT_FLOAT:
				$result = floatval($value);
				break;

			default:
				$result = $value;
				break;
		}

		return $result;
	}

	public function __destruct()
	{
		$this->invalidate();

		$this->checkDirty();
	}

	protected function checkDirty()
	{
		if (!$this->runningCheckDirty)
		{
			$this->runningCheckDirty = true;

			if (!$this->readonly && $this->updateCount == 0)
			{
				$this->dirty = array_merge($this->dirty, $this->dirtyQueue);
				$this->dirtyQueue = array();

				while (count($this->dirty) !== 0)
				{
					$actions = array();
					if (array_key_exists('store', $this->dirty) && $this->dirty['store'])
					{
						$this->store();
						unset($this->dirty['store']);
					}
					foreach ($this->dirty as $method => $flag)
					{
						if ($flag)
						{
							$this->$method();
						}
					}

					if (count($this->dirtyQueue) > 0)
					{
						$this->dirty = $this->dirtyQueue;
						$this->dirtyQueue = array();
					}
					else
					{
						$this->dirty = array();
					}
				}
			}

			$this->runningCheckDirty = false;
		}
	}

	public function makeDirty($method)
	{
		$this->dirtyQueue[$method] = true;
	}


	private function _isUploadFile($codeError)
	{
		switch ($codeError)
		{
			case UPLOAD_ERR_OK:
				return null;
				break;
			case UPLOAD_ERR_INI_SIZE:
				return 'Размер принятого файла превысил максимально
							допустимый размер, который задан директивой
							upload_max_filesize конфигурационного файла php.ini';
				break;
			case UPLOAD_ERR_FORM_SIZE:
				return 'Размер загружаемого файла превысил значение MAX_FILE_SIZE,
							указанное в HTML-форме';
				break;
			case UPLOAD_ERR_PARTIAL:
				return 'Загружаемый файл был получен только частично.';
				break;
			case UPLOAD_ERR_NO_FILE:
				return 'Файл не был загружен.';
				break;
			case UPLOAD_ERR_NO_TMP_DIR:
				return 'Отсутствует временная папка.';
				break;
			case UPLOAD_ERR_CANT_WRITE:
				return 'Не удалось записать файл на диск.';
				break;
			case UPLOAD_ERR_EXTENSION:
				return 'PHP-расширение остановило загрузку файла.';
				break;
		}
	}


	public function addObjectImage($imageGroup, $file, $local = false)
	{
		$name = $type = $tmp_name = $error = $size = [];
		extract($file);
		if (UPLOAD_ERR_NO_FILE != $error)
		{
			$errorMessage = $this->_isUploadFile($error);
			if ($errorMessage)
			{
				return $errorMessage;
			}

			$nameFile = $name;
			$nameFile = pathinfo($nameFile);
			$extensionFile = mb_strtolower($nameFile['extension']);
			$extensionFile = $extensionFile == 'jpeg' ? 'jpg' : $extensionFile;
			$nameFile = $nameFile['filename'];
			$tmpNameFile = $tmp_name;
			$sizeFile = $size;
			$fileName = sha1(time() . sha1($nameFile)) . '.' . $extensionFile;
			$fileSub = mb_substr($fileName, 0, 2);

			$folder = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $imageGroup . '/originals/' . $fileSub . '/';

			if (!is_dir($folder))
			{
				mkdir($folder, 0777, true);
				bmTools::chmodPath(BM_C_IMAGE_FOLDER . $imageGroup . '/originals/' . $fileSub, 0777);
			}

			if ($local)
			{
				if (!copy($tmpNameFile, $folder . $fileName))
				{
					return "Файл не смог загрузиться в ({$folder})";
				}
			}
			else
			{
				if (!move_uploaded_file($tmpNameFile, $folder . $fileName))
				{
					return "Файл не смог загрузиться в ({$folder})";
				}
			}

			chmod($folder . $fileName, 0777);

			list($width, $height) = getimagesize($folder . $fileName);

			$image = new bmImage($this->application);

			$image->name = $nameFile;
			$image->caption = $nameFile;
			$image->fileName = $fileName;
			$image->size = $sizeFile;
			$image->width = $width;
			$image->height = $height;
			$image->save();
			$image->addLinkObject($this->objectName, $this->properties['identifier'], $imageGroup);

			return $image;
		}

		return null;
	}


	public function addObjectFile($imageGroup, $file, $local = false)
	{
		set_time_limit(0);
		$name = $type = $tmp_name = $error = $size = [];
		extract($file);
		if (UPLOAD_ERR_NO_FILE != $error)
		{
			$errorMessage = $this->_isUploadFile($error);
			if ($errorMessage)
			{
				return $errorMessage;
			}

			$nameFile = $name;
			$nameFile = pathinfo($nameFile);
			$extensionFile = mb_strtolower($nameFile['extension']);
			$nameFile = $nameFile['filename'];
			$tmpNameFile = $tmp_name;
			$sizeFile = $size;
			$fileName = sha1(time() . sha1($nameFile)) . '.' . $extensionFile;
			$fileSub = mb_substr($fileName, 0, 2);

			$folder = rtrim(documentRoot, '/') . BM_C_FILE_FOLDER . $imageGroup . '/' . $fileSub . '/';

			if (!is_dir($folder))
			{
				mkdir($folder, 0777, true);
				chmod(rtrim(documentRoot, '/') . BM_C_FILE_FOLDER, 0777);
				chmod(rtrim(documentRoot, '/') . BM_C_FILE_FOLDER . $imageGroup, 0777);
				chmod(rtrim(documentRoot, '/') . BM_C_FILE_FOLDER . $imageGroup . '/' . $fileSub . "/", 0777);
			}

			if ($local)
			{
				if (!copy($tmpNameFile, $folder . $fileName))
				{
					return "Файл не смог загрузиться в ({$folder})";
				}
			}
			else
			{
				if (!move_uploaded_file($tmpNameFile, $folder . $fileName))
				{
					return "Файл несмог загрузиться в ({$folder})";
				}
			}

			chmod($folder . $fileName, 0777);

			$fileObject = new bmFile($this->application);

			$fileObject->name = $nameFile;
			$fileObject->caption = $nameFile;
			$fileObject->fileName = $fileName;
			$fileObject->size = $sizeFile;
			$fileObject->save();
			$fileObject->addLinkObject($this->objectName, $this->properties['identifier'], $imageGroup);

			return $fileObject;
		}

		return null;
	}

	public function addObjectImages($imageGroup, $file)
	{
		$name = $type = $tmp_name = $error = $size = [];
		extract($file);
		$errors = [];
		if (array_key_exists($imageGroup, $error) && $error[$imageGroup] && !in_array(UPLOAD_ERR_NO_FILE, $error[$imageGroup]))
		{
			foreach ($error[$imageGroup] as $key => $codeError)
			{
				$errorMessage = $this->_isUploadFile($codeError);
				if ($errorMessage)
				{
					$errors[$name[$imageGroup][$key]] = $errorMessage;
					continue;
				}

				$nameFile = $name[$imageGroup][$key];
				$nameFile = pathinfo($nameFile);
				$extensionFile = mb_strtolower($nameFile['extension']);
				$extensionFile = $extensionFile == 'jpeg' ? 'jpg' : $extensionFile;
				$nameFile = $nameFile['filename'];
				$tmpNameFile = $tmp_name[$imageGroup][$key];
				$sizeFile = $size[$imageGroup][$key];
				$fileName = sha1(time() . sha1($nameFile)) . '.' . $extensionFile;
				$fileSub = mb_substr($fileName, 0, 2);

				$folder = rtrim(documentRoot, '/') . BM_C_IMAGE_FOLDER . $imageGroup . '/originals/' . $fileSub . '/';

				if (!is_dir($folder))
				{
					mkdir($folder, 0777, true);
				}

				if (!move_uploaded_file($tmpNameFile, $folder . $fileName))
				{
					$errors[$name[$imageGroup][$key]] = "Файл несмог загрузиться в ({$folder})";
					continue;
				}

				list($width, $height) = getimagesize($folder . $fileName);

				$image = new bmImage($this->application);

				$image->name = $nameFile;
				$image->caption = $nameFile;
				$image->fileName = $fileName;
				$image->size = $sizeFile;
				$image->width = $width;
				$image->height = $height;
				$image->save();
				$image->addLinkObject($this->objectName, $this->properties['identifier'], $imageGroup);

			}

			return $errors;
		}

		return null;
	}

	public function getObjectImages($imageGroup, $isDeleted = false)
	{
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$group = $this->application->dataLink->quoteSmart($imageGroup);
		$identifier = intval($this->properties['identifier']);

		$map = array(0 => 'image', 'image IS image' => 5, 'group' => 1);
		$cacheKey = '';

		$sql = "
			select
				`link_image_object`.`imageId` as `imageId`,
				`link_image_object`.`group` as `group`
			from
				`link_image_object`
				inner join `image` on `image`.`id` = `link_image_object`.`imageId`
			where
				`link_image_object`.`object` = {$objectName}
				and `link_image_object`.`objectId` = {$identifier}
				and `link_image_object`.`group` = {$group}
				and `image`.`deleted` <> " . ($isDeleted ? BM_C_DELETE_OBJECT - 1 : BM_C_DELETE_OBJECT) . "
			order by
				`link_image_object`.`imageId` desc
			";

		return $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, true);
	}

	public function getAnonymous($method)
	{
		return $this->$method();
	}

	public function getObjectImagesGroups()
	{
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$identifier = intval($this->properties['identifier']);

		$map = array(0 => 'image', 'image IS image' => 5, 'group' => 1, 'object' => 2);
		$cacheKey = '';

		$sql = "
			select
				`link_image_object`.`imageId` as `imageId`,
				`link_image_object`.`group` as `group`,
				`link_image_object`.`object` as `object`
			from
				`link_image_object`
			where
				`link_image_object`.`object` = {$objectName}
				and `link_image_object`.`objectId` = {$identifier}
			order by
				`link_image_object`.`imageId` desc
			";

		return $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, true);
	}

	public function getObjectFiles($imageGroup, $isDeleted = false)
	{
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$group = $this->application->dataLink->quoteSmart($imageGroup);
		$identifier = intval($this->properties['identifier']);

		$map = array(0 => 'file', 'file IS file' => 5, 'group' => 1);
		$cacheKey = '';

		$sql = "
			select
				`link_file_object`.`fileId` as `fileId`,
				`link_file_object`.`group` as `group`
			from
				`link_file_object`
				inner join `file` on `file`.`id` = `link_file_object`.`fileId`
			where
				`link_file_object`.`object` = {$objectName}
				and `link_file_object`.`objectId` = {$identifier}
				and `link_file_object`.`group` = {$group}
				and `file`.`deleted` <> " . ($isDeleted ? BM_C_DELETE_OBJECT - 1 : BM_C_DELETE_OBJECT) . "
			order by
				`link_file_object`.`fileId` desc
			";

		return $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, true);
	}

	public function getObjectFilesGroups()
	{
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$identifier = intval($this->properties['identifier']);

		$map = array(0 => 'file', 'file IS file' => 5, 'group' => 1, 'object' => 2);
		$cacheKey = '';

		$sql = "
			select
				`link_file_object`.`fileId` as `fileId`,
				`link_file_object`.`group` as `group`,
				`link_file_object`.`object` as `object`,
			from
				`link_file_object`
			where
				`link_file_object`.`object` = {$objectName}
				and `link_file_object`.`objectId` = {$identifier}
			order by
				`link_file_object`.`fileId` desc
			";

		return $this->getComplexLinks($sql, $cacheKey, $map, E_OBJECTS_NOT_FOUND, true);
	}

	public function deleteObjectImages($imageGroup, $excludeImgs = null)
	{
		if ($excludeImgs)
		{
			if (!is_array($excludeImgs))
			{
				$excludeImgs = (array)$excludeImgs;
			}
			$excludeImgs = implode(',', $excludeImgs);
		}
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$group = $this->application->dataLink->quoteSmart($imageGroup);
		$identifier = intval($this->properties['identifier']);

		$cacheKey = '';

		$sql = "
			update
				`link_image_object`
				inner join `image` on `image`.`id` = `link_image_object`.`imageId`
			set
				`image`.`deleted` = " . BM_C_DELETE_OBJECT . "
			where
				`link_image_object`.`object` = {$objectName}
				and `link_image_object`.`objectId` = {$identifier}
				and `link_image_object`.`group` = {$group}
				and `image`.`id` not in ({$excludeImgs})
				and `image`.`deleted` <> " . BM_C_DELETE_OBJECT . "";

		if(defined('USE_SEPARATE_DB_FOR_WRITE') &&  USE_SEPARATE_DB_FOR_WRITE == 1)
		{
			return $this->application->dataLinkWrite->query($sql);
		} else {
			return $this->application->dataLink->query($sql);
		}

	}

	public function deleteObjectFiles($imageGroup, $excludeImgs = null)
	{
		if ($excludeImgs)
		{
			if (!is_array($excludeImgs))
			{
				$excludeImgs = (array)$excludeImgs;
			}
			$excludeImgs = implode(',', $excludeImgs);
		}
		$objectName = $this->application->dataLink->quoteSmart($this->objectName);
		$group = $this->application->dataLink->quoteSmart($imageGroup);
		$identifier = intval($this->properties['identifier']);

		$cacheKey = '';

		$sql = "
			update
				`link_file_object`
				inner join `file` on `file`.`id` = `link_file_object`.`fileId`
			set
				`file`.`deleted` = " . BM_C_DELETE_OBJECT . "
			where
				`link_file_object`.`object` = {$objectName}
				and `link_file_object`.`objectId` = {$identifier}
				and `link_file_object`.`group` = {$group}
				and `file`.`id` not in ({$excludeImgs})
				and `file`.`deleted` <> " . BM_C_DELETE_OBJECT . "";

		if(defined('USE_SEPARATE_DB_FOR_WRITE') &&  USE_SEPARATE_DB_FOR_WRITE == 1)
		{
			return $this->application->dataLinkWrite->query($sql);
		} else {
			return $this->application->dataLink->query($sql);
		}
	}

	public function getProperty($propertyName)
	{
		$this->checkDirty();
		$result = parent::getProperty($propertyName);
		if (array_key_exists($propertyName, $this->map))
		{
			switch ($this->map[$propertyName]['dataType'])
			{
				case BM_VT_DATETIME:
					return $result;
					break;
				/*case BM_VT_PASSWORD:
					return '';
				  break;*/
				default:
					return $result;
					break;
			}
		}


		if (isset($result))
		{
			return $result;
		}
	}

	public function __get($propertyName)
	{
		return $this->getProperty($propertyName);
	}

	public function __isset($propertyName)
	{
		return array_key_exists($propertyName, $this->map);
	}

	public function __set($propertyName, $value)
	{
		if (array_key_exists($propertyName, $this->map))
		{
			if ($this->map[$propertyName]['dataType'] == BM_VT_DATETIME)
			{
				$value = new bmDateTime($value);
			}
			if ((string)$this->properties[$propertyName] != (string)$value)
			{
				$this->triggerEvent(
					'propertyChange', array(
						'identifier' => $this->properties['identifier'],
						'propertyName' => $propertyName,
						'oldValue' => $this->properties[$propertyName],
						'newValue' => $value
					)
				);
				if ($this->map[$propertyName]['dataType'] == BM_VT_PASSWORD)
				{
					if ($value == '')
					{
						return;
					}
					else
					{
						$value = password_hash($value, PASSWORD_BCRYPT, ['cost' => 10]);
					}
				}
				if ($this->map[$propertyName]['dataType'] == BM_VT_IMAGE)
				{
					if ($value != '')
					{

					}
					else
					{
						$fileName = (string)$this->properties[$propertyName];
						unlink(documentRoot . '/images/' . $this->objectName . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
						unlink(documentRoot . '/images/' . $this->objectName . '/admin/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
					}
				}
				if ($this->map[$propertyName]['dataType'] == BM_VT_FILE)
				{
					if ($value != '')
					{

					}
					else
					{
						$fileName = (string)$this->properties[$propertyName];
						unlink(documentRoot . '/files/' . $this->objectName . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
						unlink(documentRoot . '/files/' . $this->objectName . '/admin/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
					}
				}
				$this->properties[$propertyName] = $value;
				$this->dirty['store'] = true;
			}

		}
	}

	public function getObjectIdByField($fieldName, $value)
	{
		$sql = "SELECT `id` AS `identifier` FROM `" . $this->objectName . "` WHERE `" . $fieldName . "` = '" . $value . "';";
		$result = $this->application->dataLink->getValue($sql);

		return ($result != null) ? $result : 0;
	}

	public function load()
	{
		$objectName = mb_convert_case($this->objectName, MB_CASE_TITLE);
		if ($this->properties['identifier'])
		{
			$cache = $this->getObject();
		}
		else
		{
			$cache = false;
		}


		if ($cache != false)
		{
			foreach ($this->map as $propertyName => $property)
			{
				//ar_dump($propertyName);
				$this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $cache->$propertyName);
			}
			$this->dirty['store'] = false;
			$this->triggerEvent('load', array('identifier' => $this->properties['identifier']));
		}
		else
		{
		}
	}

	public function fieldsToSQL()
	{
		$fields = array();

		foreach ($this->map as $propertyName => $property)
		{
			$fields[] = '`' . $property['fieldName'] . '` AS `' . $propertyName . '`';
		}

		$fields = "" . implode(',', $fields) . "";

		return $fields;

	}

	public function store()
	{
		$saveIdentifier = $this->properties['identifier'];

		if(defined('USE_SEPARATE_DB_FOR_WRITE') &&  USE_SEPARATE_DB_FOR_WRITE == 1)
		{
			$dataLink = $this->application->dataLinkWrite;
		} else {
			$dataLink = $this->application->dataLink;
		}
		if (($this->properties['identifier'] === 0) || ($this->properties['identifier'] == ''))
		{
			$this->properties['identifier'] = 'NULL';
		}

		$cacheObject = new stdClass();

		$fields = array();
		foreach ($this->map as $propertyName => $property)
		{
			$propertyValue = $this->properties[$propertyName];
			$cacheObject->$propertyName = $propertyValue;

			$value = $property['defaultValue'];
			if ($propertyValue !== 'NULL')
			{
				switch ($property['dataType'])
				{
					case BM_VT_STRING:
					case BM_VT_TEXT:
						$value = "'" . $dataLink->formatInput($propertyValue) . "'";
						break;
					case BM_VT_INTEGER:
						$value = intval($propertyValue);
						break;
					case BM_VT_FLOAT:
						$value = floatval($propertyValue);
						break;
					case BM_VT_PASSWORD:
						$value = "'" . (string)$propertyValue . "'";
						break;
					case BM_VT_IMAGE:
						$value = "'" . (string)$propertyValue . "'";
						break;
					case BM_VT_FILE:
						$value = "'" . (string)$propertyValue . "'";
						break;
					case BM_VT_DATETIME:
						$value = "'" . (string)$propertyValue . "'";
						break;
				}
			}
			else
			{
				$value = 'NULL';
			}
			$fields[] = '`' . $property['fieldName'] . '` = ' . $value;
		}

		if ($this->storage == 'rdbs+dods')
		{
			$fields = implode(',', $fields);

			$sql = "INSERT INTO `" . $this->objectName . "` SET " . $fields . " ON DUPLICATE KEY UPDATE " . $fields . ";";
			$objectId = $dataLink->query($sql);
			if (($objectId = $dataLink->insertId()) != 0)
			{
				$this->properties['identifier'] = $objectId;
				$cacheObject->identifier = $objectId;
			}
			else
			{
				$this->properties['identifier'] = $saveIdentifier;
				$cacheObject->identifier = $saveIdentifier;
			}

			$this->application->cacheLink->set($this->objectName . '_' . $this->properties['identifier'], $cacheObject, BM_CACHE_SHORT_TTL);
		}


		if ($this->storage == 'dods')
		{
			$this->application->cacheLink->set($this->objectName . '_' . $this->properties['identifier'], $cacheObject, BM_CACHE_LONG_TTL, true);
			$result = $this->application->cacheLink->get($this->objectName . '_' . $this->properties['identifier']);
		}
	}

	public function save()
	{
		$this->checkDirty();
		$this->triggerEvent('save', array('identifier' => $this->properties['identifier']));
	}

	public function delete()
	{
		$this->readonly = true;
		$this->triggerEvent('delete', array('identifier' => $this->properties['identifier']));
	}

	public function beginUpdate()
	{
		++$this->updateCount;
	}

	public function endUpdate()
	{
		$this->updateCount = $this->updateCount == 0 ? 0 : --$this->updateCount;
	}

	public function invalidate()
	{
		$this->updateCount = 0;
	}

	protected function itemExists($key, $propertyName, &$collection)
	{
		foreach ($collection as $item)
		{
			if ($item->$propertyName == $key)
			{
				return true;
			}
		}

		return false;
	}

	protected function searchItem($key, $propertyName, $collection)
	{
		foreach ($collection as $index => $item)
		{
			if ($item->$propertyName == $key)
			{
				return $index;
			}
		}

		return false;
	}

	public function getObject()
	{
		$objectId = $this->properties['identifier'];
		$objectName = $this->objectName;

		if ($this->application->debug == false || $this->storage == 'dods')
		{
			$result = $this->application->cacheLink->get($objectName . '_' . $objectId);
		}
		else
		{
			$result = false;
		}

		if ($result === false && $this->storage == 'rdbs+dods')
		{
			$fields = $this->fieldsToSQL();

			$sql = "SELECT " . $fields . " FROM `" . $objectName . "` WHERE `id` = '" . $objectId . "' LIMIT 1;";

			$result = $this->application->dataLink->getObject($sql);

			if ($result == null)
			{
				$result = false;
			}
			else
			{
				$this->application->cacheLink->set($objectName . '_' . $objectId, $result, BM_CACHE_SHORT_TTL);
			}
		}

		return $result;
	}

	public function getObjects($objectIds, $objectName)
	{
		$objectsFilter = array();

		$result = array();

		$className = 'bm' . ucfirst($objectName);

		foreach ($objectIds as $order => $objectId)
		{
			if ($this->application->debug == false || $this->storage == 'dods')
			{
				if ($object = $this->application->cacheLink->get($objectName . '_' . $objectId))
				{
					if (is_object($object))
					{
						$object->load = false;
						$result[$order] = new $className($this->application, get_object_vars($object));
					}
				}
				else
				{
					$objectsFilter[$order] = $objectId;
				}
			}
			else
			{
				$objectsFilter[$order] = $objectId;
			}
		}

		if (count($objectsFilter) > 0)
		{

			$objectsFilterSQL = "'" . implode("', '", $objectsFilter) . "'";
			$object = new $className($this->application, array('readonly' => true));

			$fieldsSQL = $object->fieldsToSQL();

			$sql = "SELECT " . $fieldsSQL . " FROM `" . $objectName . "` WHERE `id` IN (" . $objectsFilterSQL . ") ORDER BY FIELD(`identifier`, " . $objectsFilterSQL . ");";

			$orders = array_keys($objectsFilter);

			$qObjects = $this->application->dataLink->select($sql);

			while ($object = $qObjects->nextObject())
			{
				$this->application->cacheLink->set($objectName . '_' . $object->identifier, $object, BM_CACHE_SHORT_TTL);
				$object->load = false;

				foreach ($objectsFilter as $order => $objectId)
				{
					if ($objectId == $object->identifier)
					{
						$result[$order] = new $className($this->application, get_object_vars($object));
					}
				}
			}

			$qObjects->free();
		}

		return $result;
	}

	protected function getSimpleLink($sql, $cacheKey, $objectName, $errorCode, $load)
	{
		$bigResult = $this->getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, 1, 0);

		if (count($bigResult) > 0)
		{
			$result = $bigResult[0];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, $limit = 0, $offset = 0)
	{
		if ($this->application->debug == false || $this->storage == 'dods')
		{
			$result = $this->application->cacheLink->get($cacheKey);
		}
		else
		{
			$result = false;
		}

		if ($result === false)
		{
			$qObjectIds = $this->application->dataLink->select($sql);
			$result = array();

			while ($objectId = $qObjectIds->nextObject())
			{
				$result[] = $objectId->identifier;
			}

			$qObjectIds->free();
			$this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
		}

		if (count($result) > 0)
		{
			if ($offset > 0)
			{
				$result = array_slice($result, $offset);
			}

			if ($limit > 0)
			{
				$result = array_slice($result, 0, $limit);
			}

			if ($load)
			{
				$result = $this->getObjects($result, $objectName);
			}
		}
		else
		{
			return array();
		}

		return $result;
	}

	protected function getComplexLink($sql, $cacheKey, $map, $errorCode, $load)
	{
		$bigResult = $this->getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, 1, 0);

		if (count($bigResult) > 0)
		{
			$result = $bigResult[0];
		}
		else
		{
			$result = null;
		}

		return $result;
	}

	protected function getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, $limit = 0, $offset = 0)
	{
		if ($this->application->debug == false || $this->storage == 'dods')
		{
			$result = $this->application->cacheLink->get($cacheKey);
		}
		else
		{
			$result = false;
		}

		$objectArrays = false;

		// Преобразование строки маппинга в массив маппинга
		// Находится вне кэша и выполняется в любом случае
		$propertyObjectLink = array('propertyName' => array(), 'objectName' => array());
		foreach ($map as $propertyName => $dummy)
		{
			$tempArray = preg_split('/ IS /', $propertyName, 2, PREG_SPLIT_NO_EMPTY);

			$currentPropertyName = $tempArray[0];
			count($tempArray) > 1 ? $currentObjectName = $tempArray[1] : $currentObjectName = $tempArray[0];

			$propertyObjectLink['propertyName'][] = $currentPropertyName;
			$propertyObjectLink['objectName'][] = $currentObjectName;
		}
		// Конец преобразования

		if ($result === false)
		{

			$result = array();
			$qObjects = $this->application->dataLink->select($sql);
			if ($qObjects->rowCount() > 0)
			{
				$objectArrays = array();

				$mapCounter = 0; // Счетчик, чтобы не парсить имя свойства еще раз

				foreach ($map as $propertyName => $type)
				{
					if ($type == BM_VT_OBJECT)
					{
						$objectArrays[$propertyObjectLink['propertyName'][$mapCounter]] = array();
					}

					++$mapCounter; // ++счетчик
				}

				while ($object = $qObjects->nextObject())
				{
					$result[] = $object;

					foreach ($objectArrays as $key => $dummy)
					{
						$objectArrays[$key][] = $object->{$key . 'Id'};
					}
				}
			}

			$qObjects->free();
			$this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
			$this->application->cacheLink->set($cacheKey . '_objectArrays', $objectArrays, BM_CACHE_SHORT_TTL);
		}

		if (count($result) > 0)
		{
			$dateTimePropertyNames = array();

			if ($load)
			{
				if (!$objectArrays)
				{
					if ($this->application->debug == false || $this->storage == 'dods')
					{
						$objectArrays = $this->application->cacheLink->get($cacheKey . '_objectArrays');
					}
					else
					{
						$objectArrays = false;
					}
				}

				if ($offset > 0)
				{
					$result = array_slice($result, $offset);

					foreach ($objectArrays as $key => $dummy)
					{
						$objectArrays[$key] = array_slice($objectArrays[$key], $offset);
					}
				}

				if ($limit > 0)
				{
					$result = array_slice($result, 0, $limit);

					foreach ($objectArrays as $key => $dummy)
					{
						$objectArrays[$key] = array_slice($objectArrays[$key], 0, $limit);
					}
				}

				foreach ($objectArrays as $key => $dummy)
				{
					// Находим objectName по propertyName в созданном нами массиве
					$index = array_search($key, $propertyObjectLink['propertyName']);
					$objectName = $propertyObjectLink['objectName'][$index];
					// Нашли

					$objectArrays[$key] = $this->getObjects($objectArrays[$key], $objectName);
				}

				foreach ($result as $order => $dummy)
				{
					foreach ($objectArrays as $key => $dummy)
					{
						$result[$order]->$key = $objectArrays[$key][$order];
					}
				}
			}
		}
		else
		{
			return array();
		}

		return $result;
	}

	protected function enqueueCache($methodKey)
	{
		$this->cacheQueue[$methodKey][] = $this->properties['identifier'];
		$this->dirty['validateCache'] = true;
	}

	protected function validateCache()
	{
		$this->dirty['validateCache'] = false;
	}

	protected function itemDiff($sourceArray, $subtractedArray, $propertyName)
	{
		return array_udiff(
			$sourceArray, $subtractedArray, function ($a, $b) use ($propertyName)
		{
			if ($a->$propertyName == $b->$propertyName)
			{
				return 0;
			}
			else
			{
				return $a->$propertyName > $b->$propertyName ? 1 : -1;
			}
		}
		);
	}

	protected function itemImplode($itemArray, $propertyName)
	{
		$idArray = array();

		foreach ($itemArray as $item)
		{
			$idArray[] = $item->$propertyName;
		}

		return implode(', ', $idArray);
	}

	/**
	 * значение полей объекта в виде массива
	 * @return array
	 */
	public function toArray()
	{
		$output = [];
		foreach ($this->map as $field => $fieldInfo)
		{
			$output[$field] = $this->__get($field);
		}

		return $output;
	}
}

