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

final class bmReferenceMap extends bmMetaDataObject
{

	private $savedPropertyValues = array();

	private $addedFieldIds = array();
	private $droppedFields = array();
	private $renamedFields = array();

	/**
	 * @var bmMigration
	 */
	protected $migration;

	public function __construct($application, $parameters = array(), $migration = null)
	{
		$this->objectName = 'referenceMap';

		$this->map = array(
			'name' => array(
				'fieldName' => 'name',
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

	public function __get($propertyName)
	{
		$this->checkDirty();
		switch ($propertyName)
		{
			case 'fieldIds':
				if (!array_key_exists('fieldIds', $this->properties))
				{
					$this->properties['fieldIds'] = $this->getFields(false);
				}
				return $this->properties['fieldIds'];
				break;
			case 'fields':
				return $this->getFields(true);
				break;
			default:
				return parent::__get($propertyName);
				break;
		}
	}

	public function __set($propertyName, $value)
	{
		if ($this->properties['name'] != '' && $propertyName == 'name' && $this->properties['name'] != $value)
		{
			$this->savedPropertyValues['name'] = $this->properties['name'];
			$this->dirty['renameTable'] = true;
		}

		parent::__set($propertyName, $value);
	}

	public function addField($fieldId, $type, $referencedObjectId)
	{
		$fieldIds = $this->fieldIds;
		$this->properties['referencedObjectIdMigrate'] = $referencedObjectId;

		if (!$this->itemExists($fieldId, 'referenceFieldId', $fieldIds))
		{
			$item = new stdClass();
			$item->referenceFieldId = $fieldId;
			$item->type = $type;
			$this->properties['fieldIds'][] = $item;

			$this->addedFieldIds[] = $fieldId;

			$this->dirty['updateTableFields'] = true;
			$this->dirty['saveFields'] = true;
		}
	}

	public function changeFieldType($fieldId, $type, $oldReferenceField)
	{
		$fieldIds = $this->fieldIds;
		$this->properties['oldReferenceField'] = $oldReferenceField;

		$key = $this->searchItem($fieldId, 'referenceFieldId', $fieldIds);

		if ($key !== 0)
		{
			$item = $this->properties['fieldIds'][$key];
			$item->type = $type;
			$item->fieldId = $fieldId;

			$this->renamedFields[] = $item;
			$this->dirty['updateTableFields'] = true;
			$this->dirty['saveFields'] = true;
		}
	}

	public function removeField($fieldId)
	{
		$fieldIds = $this->fieldIds;

		$key = $this->searchItem($fieldId, 'referenceFieldId', $fieldIds);

		if ($key !== false)
		{
			unset($this->properties['fieldIds'][$key]);

			$field = new bmReferenceField($this->application, array('identifier' => $fieldId), $this->migration);
			$fieldName = ($field->dataType == BM_VT_OBJECT) ? $field->fieldName . 'Id' : $field->fieldName;
			$this->droppedFields[$fieldId] = $field->fieldName;
		}

		$this->dirty['updateTableFields'] = true;
		$this->dirty['saveFields'] = true;
	}

	public function renameField($fieldId, $oldFieldName, $oldReferenceField)
	{
		$item = new stdClass();
		$item->fieldId = $fieldId;
		$item->oldFieldName = $oldFieldName;
		$this->properties['oldReferenceField'] = $oldReferenceField;

		$this->renamedFields[] = $item;

		$this->dirty['updateTableFields'] = true;
	}

	public function removeFields()
	{
		$this->beginUpdate();

		foreach ($this->fieldIds as $fieldId)
		{
			$this->removeField($fieldId);
		}

		$this->endUpdate();
	}

	protected function saveFields()
	{
		$referencedObjectTypesCount = array();

		foreach ($this->properties['fieldIds'] as $item)
		{
			array_key_exists($item->type, $referencedObjectTypesCount) ? ++$referencedObjectTypesCount[$item->type] : $referencedObjectTypesCount[$item->type] = 1;
		}


		if ((array_key_exists(BM_RT_MAIN, $referencedObjectTypesCount) && $referencedObjectTypesCount[BM_RT_MAIN] > 1) || (array_key_exists(BM_RT_REFERRED, $referencedObjectTypesCount) && $referencedObjectTypesCount[BM_RT_REFERRED] > 1))
		{
			echo '<b>Ошибка уровня ядра:</b> в связи не может быть двух главных или двух зависимых объектов';
			exit;
		}

		$dataLink = $this->application->dataLink;
		$cacheLink = $this->application->cacheLink;

		$sql = "DELETE FROM `link_referenceMap_referenceField` WHERE `referenceMapId` = " . $this->properties['identifier'] . ";";

		$dataLink->query($sql);
		$this->application->log->add($sql);

		if (count($this->droppedFields) > 0)
		{
			foreach ($this->droppedFields as $fieldId => $fieldName)
			{
				$field = new bmReferenceField($this->application, array('identifier' => $fieldId));
				$field->delete();
			}
		}

		$insertStrings = array();
		$insertObjectStrings = array();

		foreach ($this->properties['fieldIds'] as $item)
		{
			$fieldId = $item->referenceFieldId;
			$type = $item->type;

			$droppedFieldIds = array_keys($this->droppedFields);

			if (!in_array($fieldId, $droppedFieldIds))
			{
				$insertStrings[] = "(" . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ", " . $dataLink->formatInput($fieldId, BM_VT_INTEGER) . ", " . $dataLink->formatInput($type, BM_VT_INTEGER) . ")";
			}
		}

		if (count($insertStrings) > 0)
		{
			$sql = "INSERT IGNORE INTO
                  `link_referenceMap_referenceField`
                  (`referenceMapId`, `referenceFieldId`, `referenceFieldType`)
                VALUES
                  " . implode(', ', $insertStrings) . ";";

			$dataLink->query($sql);
			$this->application->log->add($sql);
		}

		$this->dirty['saveFields'] = false;
	}

	protected function updateTableFields()
	{
		if (array_key_exists('saveFields', $this->dirty))
		{
			if ($this->dirty['saveFields'] == true)
			{
				$this->saveFields();
			}
		}

		$dataLink = $this->application->dataLink;

		$insertStrings = array();
		$indexStrings = array();

		foreach ($this->addedFieldIds as $id)
		{
			$referenceField = new bmReferenceField($this->application, array('identifier' => $id));

			$tableFieldName = ($referenceField->dataType == BM_VT_OBJECT) ? $referenceField->fieldName . 'Id' : $referenceField->fieldName;
			$insertStrings[] = 'ADD COLUMN `' . $tableFieldName . '` ' . $dataLink->ffTypeToNativeType($referenceField->dataType, $referenceField->defaultValue);

			$key = $this->searchItem($id, 'referenceFieldId', $this->properties['fieldIds']);

			$typeMigrate = 4;

			if ($key !== false)
			{
				$item = $this->properties['fieldIds'][$key];
				
				$typeMigrate = $item->type;
				
				if (in_array($item->type, array(1, 2)))
				{
					$indexStrings[] = 'ADD INDEX `' . $tableFieldName . '` (`' . $tableFieldName . '`)';
				}
			}

			if ($this->migration)
			{
				$this->migration->addSql(
					"
					insert ignore into
						referenceField
						(`propertyName`, `fieldName`, `dataType`, `localName`, `defaultValue`)
					values
						(
							" . $dataLink->quoteSmart($referenceField->propertyName) . ",
							" . $dataLink->quoteSmart($referenceField->fieldName) . ",
							" . $dataLink->quoteSmart($referenceField->dataType) . ",
							" . $dataLink->quoteSmart($referenceField->localName) . ",
							" . $dataLink->quoteSmart($referenceField->defaultValue) . "
						)"
				);
				$this->migration->addSql(
					"
						INSERT IGNORE INTO
							`link_referenceMap_referenceField`
							select p1.referenceMapId, p2.referenceFieldId, {$typeMigrate} as referenceFieldType from
								(
									select id as referenceMapId from `referenceMap` where name = " . $dataLink->quoteSmart($this->properties['name']) . "
								) p1,
								( select max(id) as referenceFieldId from referenceField ) p2


				"
				);
				if ($typeMigrate !== 4 && array_key_exists('referencedObjectIdMigrate', $this->properties) && array_key_exists($referenceField->fieldName, $this->properties['referencedObjectIdMigrate']))
				{
					$this->migration->addField($this->properties['name'], $referenceField->fieldName . "Id");
					$this->migration->addCommit("Добавление поля `{$referenceField->fieldName}Id` в связь `{$this->properties['name']}`");
					$typeText = "дополнительным";
					if ($typeMigrate === 1)
					{
						$typeText = 'главным';
					}
					elseif ($typeMigrate === 2)
					{
						$typeText = 'зависемое';
					}
					$dataObjectMap = new bmDataObjectMap($this->application, ['identifier' => $this->properties['referencedObjectIdMigrate'][$referenceField->fieldName]], null);
					$this->migration->addCommit("Поле `{$referenceField->fieldName}` {$typeText} в связи `{$this->properties['name']}` для объекта `{$dataObjectMap->name}`");
					$this->migration->addSql(
						"
												INSERT IGNORE INTO
													`link_referenceField_dataObjectMap`
												select p.dataObjectMapId, p1.referenceFieldId from
													(
														select id as dataObjectMapId from dataObjectMap where name = " . $dataLink->quoteSmart($dataObjectMap->name) . "
							) p,
							(
								select
									dof.id as referenceFieldId
								from
									referenceField dof
									inner join link_referenceMap_referenceField ldd on ldd.referenceFieldId = dof.id
									inner join referenceMap dom on dom.id = ldd.referenceMapId
								where
									dof.fieldName = " . $dataLink->quoteSmart($referenceField->properties['fieldName']) . "
									and dom.name = " . $dataLink->quoteSmart($this->properties['name']) . "
							) p1
					"
					);
				}
				else
				{
					$this->migration->addField($this->properties['name'], $referenceField->fieldName);
					$this->migration->addCommit("Добавление поля `{$referenceField->fieldName}` в связь `{$this->properties['name']}`");
				}
			}
		}

		if (count($insertStrings) > 0)
		{
			$sql = "ALTER TABLE
                  `" . $this->name . "`" .
				implode(', ', $insertStrings) . ";";

			$dataLink->query($sql);
			if ($this->migration)
			{
				$this->migration->addSql($sql);
			}
			$this->application->log->add($sql);
		}

		if (count($indexStrings) > 0)
		{
			$sql = "ALTER TABLE
                  `" . $this->name . "`" .
				implode(', ', $indexStrings) . ";";

			$dataLink->query($sql);
			if ($this->migration)
			{
				$this->migration->addSql($sql);
			}
			$this->application->log->add($sql);
		}

		$insertStrings = array();

		foreach ($this->droppedFields as $id => $droppedFieldName)
		{
			$insertStrings[] = 'DROP COLUMN `' . $droppedFieldName . '`';
			if ($this->migration)
			{
				$this->migration->addCommit("Удаление поля `{$droppedFieldName}` в связи `{$this->properties['name']}`");
				$this->migration->addSql(
					"
						set @idData = (
									select dof.id from
										referenceField dof
										inner join link_referenceMap_referenceField ldd on ldd.referenceFieldId = dof.id
										inner join referenceMap dom on dom.id = ldd.referenceMapId
									where
										dof.fieldName = " . $dataLink->quoteSmart($droppedFieldName) . "
										and dom.name = " . $dataLink->quoteSmart($this->properties['name']) . "
						)"
				);

				$this->migration->addSql(
					"
										DELETE FROM
													`link_referenceField_dataObjectMap`
												WHERE
													`referenceFieldId` = @idData
										"
				);

				$this->migration->addSql(
					"
										delete from
											`referenceField`
										where
											`id` = @idData
											"
				);
				$this->migration->addSql(
					"
												DELETE FROM
													`link_referenceMap_referenceField`
												WHERE
													`referenceMapId` = (select id as referenceMapId from `referenceField` where name = " . $dataLink->quoteSmart($this->properties['name']) . ")
								and `referenceFieldId` = @idData
						"
				);
			}
		}

		if (count($insertStrings) > 0)
		{
			$sql = "ALTER TABLE
                  `" . $this->name . "`" .
				implode(', ', $insertStrings) . ";";

			$dataLink->query($sql);
			if ($this->migration)
			{
				$this->migration->addSql($sql);
			}
			$this->application->log->add($sql);
		}

		$insertStrings = $insertStringsMigrate = array();
		foreach ($this->renamedFields as $item)
		{
			$referenceField = new bmReferenceField($this->application, array('identifier' => $item->fieldId));
			$tableFieldName = ($referenceField->dataType == BM_VT_OBJECT) ? $referenceField->fieldName . 'Id' : $referenceField->fieldName;
			if (property_exists($item, 'oldFieldName'))
			{
				$oldTableFieldName = ($referenceField->dataType == BM_VT_OBJECT) ? $item->oldFieldName . 'Id' : $item->oldFieldName;
			}
			else
			{
				$oldTableFieldName = $tableFieldName;
			}

			$insertStrings[] = ' CHANGE `' . $oldTableFieldName . '` `' . $tableFieldName . '` ' . $dataLink->ffTypeToNativeType($referenceField->dataType, $referenceField->defaultValue);
			if ($this->migration
				&& array_key_exists('oldReferenceField', $this->properties)
				&& array_key_exists($oldTableFieldName, $this->properties['oldReferenceField'])
				&& !$referenceField->compere($this->properties['oldReferenceField'][$oldTableFieldName])
			)
			{
				$insertStringsMigrate[] = ' CHANGE `' . $oldTableFieldName . '` `' . $tableFieldName . '` ' . $dataLink->ffTypeToNativeType($referenceField->dataType, $referenceField->defaultValue);
				$this->migration->addCommit("Переименование поля `{$oldTableFieldName}` в `{$referenceField->fieldName}`, в связи `{$this->properties['name']}`");
				$this->migration->addSql(
					"
					update
						referenceField dof
						inner join link_referenceMap_referenceField ldd on ldd.referenceFieldId = dof.id
						inner join referenceMap dom on dom.id = ldd.referenceMapId
					set
						dof.propertyName = " . $dataLink->quoteSmart($referenceField->propertyName) . ",
					dof.fieldName = " . $dataLink->quoteSmart($referenceField->fieldName) . ",
					dof.dataType = " . $dataLink->quoteSmart($referenceField->dataType) . ",
					dof.localName = " . $dataLink->quoteSmart($referenceField->localName) . ",
					dof.defaultValue = " . $dataLink->quoteSmart($referenceField->defaultValue) . "
				where
					dof.fieldName = " . $dataLink->quoteSmart($oldTableFieldName) . "
					and dom.name = " . $dataLink->quoteSmart($this->properties['name']) . "
				"
				);
			}
		}

		if (count($insertStrings) > 0)
		{
			$sql = "ALTER TABLE
                  `" . $this->name . "`" .
				implode(', ', $insertStrings) . ";";

			$dataLink->query($sql);
			$this->application->log->add($sql);
		}

		if ($this->migration && count($insertStringsMigrate) > 0)
		{
			$sql = "ALTER TABLE
                  `" . $this->name . "`" .
				implode(', ', $insertStringsMigrate) . ";";
			$this->migration->addSql($sql);
		}

		$this->addedFieldIds = array();
		$this->droppedFieldIds = array();
		$this->renamedFields = array();
		$this->dirty['updateTableFields'] = false;
	}

	public function delete()
	{
		if ($this->type != 1)
		{
			$referenceFields = $this->fields;
			$this->removeFields();
			$this->checkDirty();
			$this->dirty = array();

			foreach ($referenceFields as $item)
			{
				$item->referenceField->delete();
			}

			$sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
			$this->application->dataLink->query($sql);
			$this->application->log->add($sql);

			$sql = "DROP TABLE `" . $this->name . "`;";
			$this->application->dataLink->query($sql);
			if ($this->migration)
			{
				$this->migration->addCommit("Удаление связи `{$this->properties['name']}`");
				$this->migration->addSql(
					"
					DELETE FROM
						`link_referenceField_dataObjectMap`
					WHERE
						`referenceFieldId` in (
									select
										dof.*
									from
										referenceField dof
										inner join link_referenceMap_referenceField ldd on ldd.referenceFieldId = dof.id
										inner join referenceMap dom on dom.id = ldd.referenceMapId
									where
										dom.name = " . $this->application->dataLink->quoteSmart($this->properties['name']) . "
					)
					"
				);

				$this->migration->addSql(
					"
					DELETE FROM
						referenceField
					where
						id in (
								select
									dof.*
								from
									referenceField dof
									inner join link_referenceMap_referenceField ldd on ldd.referenceFieldId = dof.id
									inner join referenceMap dom on dom.id = ldd.referenceMapId
								where
									dom.name = " . $this->application->dataLink->quoteSmart($this->properties['name']) . "
					)
					"
				);

				$this->migration->addSql(
					"
					DELETE FROM
						`link_referenceMap_referenceField`
					WHERE
						`referenceMapId` in (select id as referenceMapId from `referenceMap` where name = " . $this->application->dataLink->quoteSmart($this->properties['name']) . ")
					"
				);

				$this->migration->addSql(
					"
					delete from
						referenceMap
					values
						`name` = " . $this->application->dataLink->quoteSmart($this->properties['name']) . "
					"
				);
				$this->migration->addSql($sql);
			}
			$this->application->log->add($sql);

			// $this->deleteFiles();
		}
		else
		{
			echo '<b>Ошибка уровня ядра:</b> Нельзя удалять системный объект ($type == 1) класса bmReferenceMap';
			exit;
		}
	}


	public function store()
	{
		if ($this->properties['identifier'] == 0)
		{
			$sql = "CREATE TABLE `" . $this->properties['name'] . "` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
			$this->application->dataLink->query($sql);
			if ($this->migration)
			{
				$this->migration->addCommit("Добавление связи `{$this->properties['name']}`");
				$this->migration->addObject($this->properties['name']);
				$this->migration->addSql("
					insert ignore into
						referenceMap
						(`name`, `type`)
					values
						(" . $this->application->dataLink->quoteSmart($this->properties['name']) . ",
						 " . $this->application->dataLink->quoteSmart($this->properties['type']) . ")
				");
				$this->migration->addSql($sql);
			}
			$this->application->log->add($sql);
			$this->application->log->add($this->prepareSQL());
		}

		$this->dirty['generateFiles'] = true;
		parent::store();
	}

	public function save()
	{
		$this->dirty['generateFiles'] = true;
		$this->checkDirty();
	}

	public function generateFiles()
	{
		$fields = $this->fields;

		foreach ($fields as $fieldItem)
		{
			$dataObjectMap = $fieldItem->referenceField->referencedObject;

			if ($dataObjectMap !== null)
			{
				$dataObjectMap->generateFiles();
			}
		}
	}

	public function renameTable()
	{
		$sql = "RENAME TABLE `" . $this->savedPropertyValues['name'] . "` TO `" . $this->properties['name'] . "`;";
		$this->application->dataLink->query($sql);
		if ($this->migration)
		{
			$this->migration->addCommit("Переименование связи `{$this->properties['name']}`");
			$this->migration->addObject($this->properties['name']);
			$this->migration->addSql("update referenceMap set `name` = "
				. $this->application->dataLink->quoteSmart($this->properties['name'])
				. " where `name` = " . $this->application->dataLink->quoteSmart($this->savedPropertyValues['name']) . ""
			);
			$this->migration->addSql($sql);
		}
		$this->application->log->add($sql);
	}

	public function getFields($load = true)
	{
		$cacheKey = null;

		$sql = "
        SELECT 
          `link_referenceMap_referenceField`.`referenceFieldId` AS `referenceFieldId`,
          `link_referenceMap_referenceField`.`referenceFieldType` AS `type`
        FROM 
          `link_referenceMap_referenceField`
        WHERE 
          `link_referenceMap_referenceField`.`referenceMapId` = " . $this->properties['identifier'] . "
        ORDER BY
          `link_referenceMap_referenceField`.`referenceFieldType`;
      ";

		$map = array('referenceField' => BM_VT_OBJECT, 'type' => BM_VT_INTEGER);

		return $this->getComplexLinks($sql, $cacheKey, $map, E_REFERENCEFIELDS_NOT_FOUND, $load);
	}

	public function isComplex()
	{
		if (count($this->fieldIds) > 2)
		{
			return true;
		}
		else
		{
			return false;
		}
	}
}

?>
