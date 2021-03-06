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

final class bmDataObjectField extends bmMetaDataObject
{

	/**
	 * @var bmMigration
	 */
	protected $migration;

	public function __construct($application, $parameters = array(), $migration = null)
	{
		$this->objectName = 'dataObjectField';

		$this->map = array(
			'propertyName' => array(
				'fieldName' => 'propertyName',
				'dataType' => BM_VT_STRING,
				'defaultValue' => ''
			),
			'fieldName' => array(
				'fieldName' => 'fieldName',
				'dataType' => BM_VT_STRING,
				'defaultValue' => ''
			),
			'dataType' => array(
				'fieldName' => 'dataType',
				'dataType' => BM_VT_INTEGER,
				'defaultValue' => 0
			),
			'localName' => array(
				'fieldName' => 'localName',
				'dataType' => BM_VT_STRING,
				'defaultValue' => ''
			),
			'defaultValue' => array(
				'fieldName' => 'defaultValue',
				'dataType' => BM_VT_STRING,
				'defaultValue' => ''
			),
			'type' => array(
				'fieldName' => 'type',
				'dataType' => BM_VT_INTEGER,
				'defaultValue' => 0
			)
		);

		parent::__construct($application, $parameters);
		$this->migration = $migration;
	}

	public function compere(bmDataObjectField $object)
	{
		if ($this->properties['propertyName'] != $object->propertyName
			|| $this->properties['fieldName'] != $object->fieldName
			|| $this->properties['dataType'] != $object->dataType
			|| $this->properties['localName'] != $object->localName
			|| $this->properties['defaultValue'] != $object->defaultValue
			|| $this->properties['type'] != $object->type
		)
		{
			return false;
		}

		return true;
	}

	public function __get($propertyName)
	{
		$this->checkDirty();
		switch ($propertyName)
		{
			case 'dataObjectMapId':
				if (!array_key_exists('dataObjectMapId', $this->properties))
				{
					$this->properties['dataObjectMapId'] = $this->getDataObjectMap();
				}
				return $this->properties['dataObjectMapId'];
				break;
			case 'dataObjectMap':
				$this->properties['dataObjectMap'] = $this->getDataObjectMap();
				break;
			case 'localNames':
				if (!array_key_exists('localNames', $this->properties))
				{
					if (trim($this->properties['localName'] != ''))
					{
						$this->properties['localNames'] = @unserialize($this->properties['localName']);
					}
					else
					{
						$this->properties['localNames'] = array('nominative' => '');
					}
				}
				return $this->properties['localNames'];
				break;
			default:
				return parent::__get($propertyName);
				break;
		}
	}

	public function setDataObjectMap($dataObjectMapId, $type)
	{
		$this->dirty['saveDataObjectMap'] = true;
		$item = new stdClass();
		$item->dataObjectMapId = $dataObjectMapId;
		$item->type = $type;
		$this->properties['dataObjectMapId'][] = $item;
	}

	public function saveDataObjectMap()
	{

		$cacheLink = $this->application->cacheLink;
		$dataLink = $this->application->dataLink;

		$objectDataMapId = $this->properties['dataObjectMapId'];
		$sql = "DELETE FROM `link_dataObjectMap_dataObjectField` WHERE `dataObjectMapId` = " . $dataLink->formatInput($this->identifier, BM_VT_INTEGER);
		$dataLink->query($sql);
		$this->application->log->add($sql);

		$sql = "INSERT IGNORE INTO
                `link_dataObjectMap_dataObjectField`
                (`dataObjectFieldId`, `dataObjectMapId`, `type`)  
                VALUES
                  (" . $this->properties['identifier'] . ", " . $dataLink->formatInput($objectDataMapId[0]->dataObjectMapId, BM_VT_INTEGER) . ", " . $dataLink->formatInput($objectDataMapId[0]->type, BM_VT_INTEGER) . ");";

		$dataLink->query($sql);
		$this->application->log->add($sql);

		$cacheLink->delete('dataObjectField_dataObjectMap_' . $this->properties['identifier']);
		$cacheLink->delete('dataObjectField_dataObjectMap_' . $this->properties['identifier'] . '_objectArrays');
		$this->dirty['saveDataObjectMap'] = false;
	}

	public function delete()
	{
		$this->dirty = array();
		$sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
		$this->application->dataLink->query($sql);
		$this->application->log->add($sql);
	}

	public function getDataObjectMap($load = true)
	{
		$cacheKey = null;

		$sql = "
        SELECT 
          `link_dataObjectMap_dataObjectField`.`dataObjectMapId` AS `identifier`
        FROM 
          `link_dataObjectMap_dataObjectField`
        WHERE 
          `link_dataObjectMap_dataObjectField`.`dataObjectFieldId` = " . $this->properties['identifier'] . "
        LIMIT 1;
      ";

		return $this->getSimpleLink($sql, $cacheKey, 'dataObjectMap', E_DATAOBJECTMAP_NOT_FOUND, $load);
	}

	public function generateFiles()
	{
		$dataObjectMap = $this->dataObjectMap;

		if ($dataObjectMap !== null)
		{
			$dataObjectMap->generateFiles();
		}
	}

	public function store()
	{
		$this->dirty['generateFiles'] = true;
		$this->application->log->add($this->prepareSQL());
		parent::store();
	}

	public function save()
	{
		$this->dirty['generateFiles'] = true;
		$this->checkDirty();
	}

}

?>