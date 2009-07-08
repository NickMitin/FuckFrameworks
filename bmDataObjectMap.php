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

  final class bmDataObjectMap extends bmDataObject
  {
    
    public function __construct($aplication, $parameters = array())
    {
      
      $this->objectName = 'dataObjectMap';
      
      $this->map = array(
        'identifier' => array(
          'fieldName' => 'id',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
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
      parent::__construct($aplication, $parameters);
      
    }
    
    public function __get($propertyName)
    {
      $this->checkDirty();
      switch ($propertyName)
      {
        case 'fieldIds':
          if (!array_key_exists('fieldIds', $this->properties))
          {
            $this->properties['fieldIds'] = $this->application->dataObjectMapCache->getFields($this, false);
          }
          return $this->properties['fieldIds'];
        break;
        case 'fields':
          return $this->application->dataObjectMapCache->getFields($this);
        break;
        default:
          return parent::__get($propertyName);
        break;
      }
    }
    
    public function addField($fieldId, $type)
    {
      $fieldIds = $this->fieldIds;
      
      if (!$this->itemExists($fieldId, 'dataObjectFieldId', $fieldIds))
      {
        $field = new stdClass();
        $field->dataObjectFieldId = $fieldId;
        $field->type = $type;
        $this->properties['fieldIds'][] = $field;
      }
      $this->dirty['saveFields'] = true;
    }
    
    public function removeFields()
    {
      foreach ($this->fields as $item)
      { 
        $item->dataObjectField->delete();
      }
      $this->properties['fieldIds'] = array();
      
      $this->application->cacheLink->delete('dataObjectMap_dataObjectFields_' . $this->properties['identifier']);
      $this->dirty['saveFields'] = true;
    }
    
    public function removeField($fieldId)
    {
      
    }
    
    protected function saveFields()
    {
      $dataLink = $this->application->dataLink;
      $cacheLink = $this->application->cacheLink;
      
      $sql = "DELETE FROM `link_dataObjectMap_dataObjectField` WHERE `dataObjectMapId` = " . $this->properties['identifier'] . ";";
      $dataLink->query($sql);
      $insertStrings = array();
      foreach ($this->properties['fieldIds'] as $item)
      { 
        $insertStrings[] = "(" . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ", " . $dataLink->formatInput($item->dataObjectFieldId, BM_VT_INTEGER) . ", " . $dataLink->formatInput($item->type, BM_VT_INTEGER) . ")";
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "INSERT IGNORE INTO
                  `link_dataObjectMap_dataObjectField`
                  (`dataObjectMapId`, `dataObjectFieldId`, `type`)
                VALUES
                  " . implode(', ', $insertStrings) . ";";
                  
        $dataLink->query($sql);
      }
      
      $cacheLink->delete('dataObjectMap_dataObjectFields_' . $this->properties['identifier']);
      $this->dirty['saveFields'] = false;
      
    }
    
    private function dataTypeToString($dataType)
    {
      $result = 'BM_VT_ANY';
      switch ($dataType)
      {
        case BM_VT_INTEGER:
          $result = 'BM_VT_INTEGER';
        break;
        case BM_VT_FLOAT:
          $result = 'BM_VT_FLOAT';
        break;
        case BM_VT_STRING:
          $result = 'BM_VT_STRING';
        break;
        case BM_VT_DATETIME:
          $result = 'BM_VT_DATETIME';
        break;
      }
      return $result;
    }
    
    public function toMapping()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $mapping = "      /*AUTOGENERATED CODE START*/\n\n     \$this->objectName = '" . $this->properties['name'] . "';\n     \$this->map = array_merge(\$this->map, array(\n";
      $mappingItems = array();
      foreach($fields as $field)
      {
        $mappingItems[] = "        '" . $field->dataObjectField->propertyName . "' => array(\n          'fieldName' => '" . $field->dataObjectField->fieldName . "',\n          'dataType' => " . $this->dataTypeToString($field->dataObjectField->type) . ",\n          'defaultValue' => " . $field->dataObjectField->fefaultValue . "\n       )";
      }
      
      $mapping .= implode(",\n", $mappingItems) . "\n     ));\n\n     /*AUTOGENERATED CODE END*/";
      return $mapping;
    }
    
    private function getPropertyNameByFieldName($fieldName)
    {

      return $fieldName;
      
    }
    
    private function mysqlTypeToFFType($mysqlType)
    {
      if (mb_strpos($mysqlType, 'int') === 0)
      {
        $result = 'BM_VT_INTEGER';
      }
      elseif (mb_strpos($mysqlType, 'float') === 0)
      {
        $result = 'BM_VT_FLOAT';
      }
      elseif (mb_strpos($mysqlType, 'date') === 0)
      {
        $result = 'BM_VT_DATETIME';
      }
      elseif (mb_strpos($mysqlType, 'char') !== false)
      {
        $result = 'BM_VT_STRING';
      }
      elseif (mb_strpos($mysqlType, 'text') !== false)
      {
        $result = 'BM_VT_STRING';
      }
      else
      {
        $result = 'BM_VT_ANY';
      }
      return $result;
      
    }
    
    public function generateFields()
    {
      $qTableFields = $this->application->dataLink->select("DESCRIBE `" . $this->name . "`;");
      while ($tableField = $qTableFields->nextObject())
      {
        $tableField->Property = $this->getPropertyNameByFieldName($tableField->Field);
        $tableField->FFType = $this->mysqlTypeToFFType($tableField->Type);
        if ($tableField->Default === null)
        {
          switch ($tableField->FFType)
          {
            case 'BM_VT_INTEGER':
            case 'BM_VT_FLOAT':
              $tableField->FFDefault = 0;
            break;
            case 'BM_VT_STRING':
              $tableField->FFDefault = '';
            break;
            case 'BM_VT_DATETIME':
              $tableField->FFDefault = '0000-01-01 00:00:00';
            break;
            case 'BM_VT_ANY':
              $tableField->FFDefault = '';
            break;
          }
        }
        else
        {
          $tableField->FFDefault = $tableField->Default;
        }
        $dataField = new bmDataObjectField($this->application);
        if ($tableField->Property == 'id')
        {
          $tableField->Property = 'identifier';
        }
        $dataField->propertyName = $tableField->Property;
        $dataField->fieldName = $tableField->Field;
        $dataField->type = constant($tableField->FFType);
        $dataField->defaultValue = $tableField->FFDefault;
        $dataField->setDataObjectMap($this->properties['identifier'], $tableField->Property == 'identifier' ? 1 : 0);
        unset($dataField);
      }
    }
    
    public function delete()
    {
      $this->removeFields();
      $this->checkDirty();
      $this->dirty = array();
      $sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
      $this->application->dataLink->query($sql);
      
    }
    
  }

?>