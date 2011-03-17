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

  final class bmReferenceMap extends bmDataObject
  {
    
    private $savedPropertyValues = array();
    
    private $addedFieldIds = array();
    private $droppedFields = array();
    private $renamedFields = array();
    
    public function __construct($application, $parameters = array())
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
    
    public function addField($fieldId, $type)
    {
      $fieldIds = $this->fieldIds;
      
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
    
    public function changeFieldType($fieldId, $type)
    {
      $fieldIds = $this->fieldIds;
      
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
        
        $field = new bmReferenceField($this->application, array('identifier' => $fieldId));
        $fieldName = ($field->dataType == BM_VT_OBJECT) ? $field->fieldName . 'Id' : $field->fieldName;
        $this->droppedFields[$fieldId] = $field->fieldName;  
      }
      
      $this->dirty['updateTableFields'] = true;
      $this->dirty['saveFields'] = true;       
    }
    
    public function renameField($fieldId, $oldFieldName)
    {
      $item = new stdClass();
      $item->fieldId = $fieldId;
      $item->oldFieldName = $oldFieldName;
      
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
      
      foreach ($this->addedFieldIds as $id)
      {
        $referenceField = new bmReferenceField($this->application, array('identifier' => $id));
        
        $tableFieldName = ($referenceField->dataType == BM_VT_OBJECT) ? $referenceField->fieldName . 'Id' : $referenceField->fieldName;
        $insertStrings[] = 'ADD COLUMN `' . $tableFieldName . '` ' . $dataLink->ffTypeToNativeType($referenceField->dataType, $referenceField->defaultValue);
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
                
        $dataLink->query($sql);
        $this->application->log->add($sql);
      }
      
      $insertStrings = array();  
      
      foreach ($this->droppedFields as $id => $droppedFieldName)
      {
        $insertStrings[] = 'DROP COLUMN `' . $droppedFieldName . '`';
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE 
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
        
        $dataLink->query($sql);
        $this->application->log->add($sql);
      }  
      
      $insertStrings = array();  
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
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
                
        $dataLink->query($sql);
        $this->application->log->add($sql);
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
        
        $sql = "DROP TABLE `" . $this->name . "`;";
        $this->application->dataLink->query($sql);
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
        $this->application->log->add($sql);
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