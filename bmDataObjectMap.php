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

  final class bmDataObjectMap extends bmMetaDataObject
  {
    private $savedPropertyValues = array();
    private $addedFieldIds = array();
    private $droppedFieldIds = array();
    
    /**
    * элемент массива - stdClass со свойствами fieldId, oldFieldName
    */
    private $renamedFields = array();
    
    public function __construct($application, $parameters = array())
    {
      $this->objectName = 'dataObjectMap';
      
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
          return $this->getFields();
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
    
    public function addField($fieldId)
    {
      $fieldIds = $this->fieldIds;
      
      if (!in_array($fieldId, $fieldIds))
      {
        $this->properties['fieldIds'][] = $fieldId;
        $this->addedFieldIds[] = $fieldId;
      }
      
      $this->dirty['saveFields'] = true;
      $this->dirty['updateTableFields'] = true;
    }
    
    public function removeField($fieldId)
    {
      $fieldIds = $this->fieldIds;
      
      foreach ($fieldIds as $key => $identifier)
      {
        if ($identifier == $fieldId)
        {
          unset($this->properties['$fieldIds'][$key]);
          $this->droppedFieldIds[] = $fieldId;
        }
      }
      
      $this->dirty['saveFields'] = true;
      $this->dirty['updateTableFields'] = true;
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
      foreach ($this->fields as $item)
      { 
        $item->delete();
      }
      $this->properties['fieldIds'] = array();
      
      $this->application->cacheLink->delete('dataObjectMap_dataObjectFields_' . $this->properties['identifier']);
      
      $this->dirty['saveFields'] = true;
      $this->dirty['updateTableFields'] = true;
    }
    
    protected function saveFields()
    {
      $dataLink = $this->application->dataLink;
      $cacheLink = $this->application->cacheLink;
      
      $sql = "DELETE FROM `link_dataObjectMap_dataObjectField` WHERE `dataObjectMapId` = " . $this->properties['identifier'] . ";";
      $dataLink->query($sql);
      $this->application->log->add($sql);
      
      $insertStrings = array();
      foreach ($this->properties['fieldIds'] as $fieldId)
      { 
        if (!in_array($fieldId, $this->droppedFieldIds))
        {
          $insertStrings[] = "(" . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ", " . $dataLink->formatInput($fieldId, BM_VT_INTEGER) . ")";
        }
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "INSERT IGNORE INTO
                  `link_dataObjectMap_dataObjectField`
                  (`dataObjectMapId`, `dataObjectFieldId`)
                VALUES
                  " . implode(', ', $insertStrings) . ";";
                  
        $dataLink->query($sql);
        $this->application->log->add($sql);
      }
      
      $this->dirty['saveFields'] = false;
    }
    
    protected function updateTableFields()
    {
      $dataLink = $this->application->dataLink; 
      
      $insertStrings = array();  
      
      foreach ($this->addedFieldIds as $id)
      {
        $dataObjectField = new bmDataObjectField($this->application, array('identifier' => $id));
        
        $insertStrings[] = 'ADD COLUMN `' . $dataObjectField->fieldName . '` ' . $dataLink->ffTypeToNativeType($dataObjectField->dataType, $dataObjectField->defaultValue);
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
      
      foreach ($this->droppedFieldIds as $id)
      {
        $dataObjectField = new bmDataObjectField($this->application, array('identifier' => $id));
        
        $insertStrings[] = 'DROP COLUMN `' . $dataObjectField->fieldName . '`';
        
        $dataObjectField->delete();
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
        $dataObjectField = new bmDataObjectField($this->application, array('identifier' => $item->fieldId));
        $insertStrings[] = 'CHANGE `' . $item->oldFieldName . '` `' . $dataObjectField->fieldName . '` ' . $dataLink->ffTypeToNativeType($dataObjectField->dataType, $dataObjectField->defaultValue);
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
        
        $dataLink->query($sql);
        $this->application->log->add($sql);
      }
      
      $this->dirty['updateTableFields'] = false;
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
        case BM_VT_TEXT:
          $result = 'BM_VT_TEXT';
        break;
        case BM_VT_PASSWORD:
          $result = 'BM_VT_PASSWORD';
        break;
        case BM_VT_IMAGE:
          $result = 'BM_VT_IMAGE';
        break;
        case BM_VT_FILE:
          $result = 'BM_VT_FILE';
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
      $mapping = "/*FF::AC::MAPPING::{*/\n\n      \$this->objectName = '" . $this->properties['name'] . "';\n";
      $mappingItems = array();
      foreach($fields as $field)
      {
        $defaultValue = $field->defaultValue;
        if ($field->dataType == BM_VT_STRING || $field->dataType == BM_VT_TEXT || $field->dataType == BM_VT_DATETIME || $field->dataType == BM_VT_ANY || $field->dataType == BM_VT_IMAGE || $field->dataType == BM_VT_FILE || $field->dataType == BM_VT_PASSWORD)
        {
          $defaultValue = "'" . $defaultValue . "'";
        }
        if (($this->properties['type'] == 1 && $field->type == 0) || $this->properties['type'] == 0) // TODO: Зачем? // Андрей Колпаков
        {
          $mappingItems[] = "        '" . $field->propertyName . "' => array(\n          'fieldName' => '" . $field->fieldName . "',\n          'dataType' => " . $this->dataTypeToString($field->dataType) . ",\n          'defaultValue' => " . $defaultValue . "\n        )";
        }
      }
      
      if (count($mappingItems) > 0)
      {
        $mapping .= "      \$this->map = array_merge(\$this->map, array(\n" . implode(",\n", $mappingItems) . "\n      ));\n\n";
      }
      $mapping = $mapping . "      /*FF::AC::MAPPING::}*/";
      return $mapping;
    }
    
    
    public function toClass()
    {
      $this->checkDirty();
      $license = file_get_contents(projectRoot . '/conf/license.conf');
      $className = 'bm' . ucfirst($this->properties['name']);
      $mapping = $this->toMapping();
      $getterCasesBlock = $this->toGetterCases();
      $referenceFunctionsBlock = $this->toReferenceFunctions();
      $deleteFunction = $this->toDeleteFunction();
      
      eval('$class  = "' . $this->application->getTemplate('/autogeneration/class') . '";');
      return $class;
    }
    
    public function toGetterCases()
    {
      $getterCases = '';
      
      $referenceMaps = $this->getReferenceMaps();
      
      $handledReferenceMapIds = array();
      
      foreach ($referenceMaps as $referenceMapItem)
      {
        $fields = $referenceMapItem->referenceMap->fields;
        
        $selfObjectCounter = 0;
        $propertyNames = array();
        
        if (!in_array($referenceMapItem->referenceMapId, $handledReferenceMapIds))
        {
          foreach ($fields as $item)
          {
            if ($item->referenceField->referencedObjectId == $this->properties['identifier'] && ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED))
            {
              ++$selfObjectCounter;
              if (!in_array($item->referenceField->propertyName, $propertyNames))
              {
                $propertyNames[] = $item->referenceField->propertyName;
              }
            }
            
            if ($selfObjectCounter == 2 && count($propertyNames) == 2)
            {
              foreach ($propertyNames as $propertyName)
              {
                $ucPropertyName = ucfirst($propertyName);
                eval('$getterCases .= "        " . "' . $this->application->getTemplate('/autogeneration/getterCase') . '" . "\n";');      
              }
              
              break;
            }
          }
          
          $handledReferenceMapIds[] = $referenceMapItem->referenceMapId;
          unset($propertyNames);
        }
        
        foreach ($fields as $item)
        {
          if (($item->referenceField->referencedObjectId != $this->properties['identifier']) ) 
          {
            if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
            {
              $propertyName = $item->referenceField->propertyName;
              $ucPropertyName = ucfirst($propertyName);
              eval('$getterCases .= "        " . "' . $this->application->getTemplate('/autogeneration/getterCase') . '" . "\n";');  
            }    
          }
        } 
      }
      
      $getterCasesBlock = '';
      
      eval('$getterCasesBlock .= "' . $this->application->getTemplate('/autogeneration/getterCasesBlock') . '";');  
      
      return $getterCasesBlock;
    }
    
    public function toReferenceFunctions()
    {
      $referenceFunctions = '';
      
      $referenceMaps = $this->getReferenceMaps();
          
      foreach ($referenceMaps as $referenceMapItem)   
      {
        $referenceFunctions .= '    ' . $this->toReferenceFunction($referenceMapItem) . "\n";
      }
      
      $referenceFunctionsBlock = '';
      
      eval('$referenceFunctionsBlock .= "' . $this->application->getTemplate('/autogeneration/referenceFunctionsBlock') . '";');  
      
      return $referenceFunctionsBlock;
    }
    
    public function analyze(bmReferenceMap $referenceMap)
    {
      $self = new stdClass();
      $active = new stdClass();
      
      $fields = $referenceMap->fields; 
        
      foreach ($fields as $item)
      {
        // if ($item->referenceField->referencedObjectId != $this->properties['identifier']) 
        // {
          if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
          {
            $active->referenceField = $item->referenceField;  
            $active->type = $item->type;
          }
          else
          {
            $self->referenceField = $item->referenceField;  
            $self->type = $item->type;
          }
        // }
      }  
      
      $result = array('self' => $self, 'active' => $active);
      
      return $result;
    }
    
    // $neededFieldType указываются только для связей типа link_resort_subResort,
    // где главный и зависимый объект являются экземлярами одного класса
    public function toReferenceFunction($referenceMapItem) 
    {                                   
      $referenceMap = $referenceMapItem->referenceMap;
      $neededFieldType = $referenceMapItem->referenceFieldType;
      
      $currentPropertyName = '';
      $getterFunction = '';
      $manipulation = '';
      $result = '';
      $fields = $referenceMap->fields; 
      $selfObjectCounter = 0;
        
      foreach ($fields as $item)
      {
        if ($item->referenceField->referencedObjectId != $this->properties['identifier']) 
        {
          if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
          {
            $currentPropertyName = $item->referenceField->propertyName;
            
            $getterFunction .= $this->toGetterFunction($item, $referenceMap, $currentPropertyName);
            $manipulation .= $this->toManipulation($item, $referenceMap, $currentPropertyName);
          }
        }
        else
        {
          if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
          {
            ++$selfObjectCounter;  
          }
        }
      }
    
      if ($selfObjectCounter == 2 && $neededFieldType != null)
      {
        foreach ($fields as $item)
        {
          if ($item->type == $neededFieldType) 
          {
            $currentPropertyName = $item->referenceField->propertyName;
            
            $getterFunction .= $this->toGetterFunction($item, $referenceMap, $currentPropertyName);
            
            if ($item->type == BM_RT_REFERRED)
            {
              $manipulation .= $this->toManipulation($item, $referenceMap, $currentPropertyName);  
            }
          }
        }
      }

      eval('$result .= "' . $this->application->getTemplate('/autogeneration/referenceFunction') . '";');  
      
      return $result; 
    }
    
    public function toGetterFunction($item, $referenceMap, $currentPropertyName)
    {
      $result = '';
      
      $objectName = $this->properties['name']; 
      $propertyName = $item->referenceField->propertyName;
      $ucPropertyName = ucfirst($propertyName);
      $tableName = $referenceMap->name;
      
      foreach ($referenceMap->fields as $referenceFieldItem)
      {
        if ($referenceFieldItem->referenceField->referencedObjectId == $this->properties['identifier'])
        {
          if ($referenceFieldItem->referenceField->propertyName != $currentPropertyName)
          {
            $thisObjectFieldName = $referenceFieldItem->referenceField->fieldName . 'Id';
          }
        }
      }
      
      if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
      {
        if ($referenceMap->isComplex())
        {
          $selectFieldsArray = array();
          $mapArray = array();
          
          foreach ($referenceMap->fields as $referenceFieldItem)
          {
            if ($referenceFieldItem->referenceField->dataType == BM_VT_OBJECT)
            {
              $selectFieldsArray[] = '          `' . $tableName . '`.`' . $referenceFieldItem->referenceField->fieldName . 'Id` AS `' . $referenceFieldItem->referenceField->propertyName . 'Id`';
              $mapArray[] = '\'' . $referenceFieldItem->referenceField->propertyName . ' IS ' . $referenceFieldItem->referenceField->referencedObject->name . '\' => ' . $referenceFieldItem->referenceField->dataType;
            }
            else
            { 
              $selectFieldsArray[] = '          `' . $tableName . '`.`' . $referenceFieldItem->referenceField->fieldName . '` AS `' . $referenceFieldItem->referenceField->propertyName . '`';
              $mapArray[] = '\'' . $referenceFieldItem->referenceField->propertyName . '\' => ' . $referenceFieldItem->referenceField->dataType;  
            }
          }
          
          $selectFields = implode(",\n", $selectFieldsArray);
          
          $map = '$map = array(' . implode(', ', $mapArray). ');';
          
          eval('$result .= "' . $this->application->getTemplate('/autogeneration/getterComplexFunction') . '";');    
        }
        else
        {
          if ($referenceFieldItem->referenceField->dataType == BM_VT_OBJECT)
          {
            $fieldName = $item->referenceField->fieldName . 'Id';  
          }
          else
          {
            $fieldName = $item->referenceField->fieldName;
          }
          
          $referencedObjectName = $item->referenceField->referencedObject->name;
          
          eval('$result .= "' . $this->application->getTemplate('/autogeneration/getterSimpleFunction') . '";');    
        }
      }
      
      return $result;  
    }
    
    public function toManipulation($item, $referenceMap, $currentPropertyName)
    {
      $result = '';
      
      $propertyName = $item->referenceField->propertyName;
      $ucPropertyName = ucfirst($propertyName);
      $tableName = $referenceMap->name;
        
      foreach ($referenceMap->fields as $referenceFieldItem)
      {
        if ($referenceFieldItem->referenceField->referencedObjectId == $this->properties['identifier'] && $referenceFieldItem->type == BM_RT_MAIN)
        {
          $thisObjectFieldName = $referenceFieldItem->referenceField->fieldName . 'Id';
          $thisPropertyName = $referenceFieldItem->referenceField->propertyName;
        }
        
        if ($referenceMap->isComplex())
        {
          if ($referenceFieldItem->type == BM_RT_REFERRED)
          {
            $otherObjectPropertyName = $referenceFieldItem->referenceField->referencedObject->name;    
          } 
        }
        else
        {
          $otherObjectPropertyName = $referenceFieldItem->referenceField->propertyName;  
        } 
      }                                    
      
      if ($item->type == BM_RT_REFERRED)
      {
        $objectName = $this->properties['name']; 
        
        if ($referenceMap->isComplex())
        {
          $parametersArray = array();
          $itemParametersArray = array();
          $insertStringsArray = array();
          $sqlFieldsArray = array();
          $selfObjectCount = 0;
          
          foreach ($referenceMap->fields as $referenceFieldItem)
          {
            $propertyNameValue = $referenceFieldItem->referenceField->propertyName;
            $fieldNameValue = $referenceFieldItem->referenceField->fieldName . 'Id';   
            $dataTypeValue = $referenceFieldItem->referenceField->dataType;
            
            if ($referenceFieldItem->referenceField->referencedObjectId != $this->properties['identifier'])
            {
              if ($referenceFieldItem->referenceField->dataType == BM_VT_OBJECT)
              {
                $fieldNameValue = $referenceFieldItem->referenceField->fieldName . 'Id';
                $parametersArray[] = '$' . $propertyNameValue . 'Id';
                $itemParametersArray[] = '        $item->' . $propertyNameValue . 'Id = ' . '$' . $propertyNameValue . 'Id';
                $insertStringsArray[] = '$dataLink->formatInput($item->' . $propertyNameValue . 'Id, ' . $dataTypeValue . ')';
              }
              elseif ($referenceFieldItem->referenceField->dataType == BM_VT_STRING || $referenceFieldItem->referenceField->dataType == BM_VT_TEXT || $referenceFieldItem->referenceField->dataType == BM_VT_DATETIME || $referenceFieldItem->referenceField->dataType == BM_VT_PASSWORD || $referenceFieldItem->referenceField->dataType == BM_VT_FLOAT)
              { 
                $fieldNameValue = $referenceFieldItem->referenceField->fieldName;
                $parametersArray[] = '$' . $propertyNameValue;
                $itemParametersArray[] = '        $item->' . $propertyNameValue . ' = ' . '$' . $propertyNameValue;
                $insertStringsArray[] = '\'\\\'\' . ' . '$dataLink->formatInput($item->' . $propertyNameValue . ', ' . $dataTypeValue . ')' . ' . \'\\\'\'';
              }
              else
              {
                $fieldNameValue = $referenceFieldItem->referenceField->fieldName;
                $parametersArray[] = '$' . $propertyNameValue;
                $itemParametersArray[] = '        $item->' . $propertyNameValue . ' = ' . '$' . $propertyNameValue;
                $insertStringsArray[] = '$dataLink->formatInput($item->' . $propertyNameValue . ', ' . $dataTypeValue . ')';
              }
              $sqlFieldsArray[] = '`' . $fieldNameValue . '`';
            }
            else
            {
              if ($selfObjectCount == 0)
              {
                $insertStringsArray[] = "\$dataLink->formatInput(\$this->properties['identifier'], BM_VT_INTEGER)";
                $sqlFieldsArray[] =  '`' . $fieldNameValue . '`';
                ++$selfObjectCount;
              }
              else
              {
                $insertStringsArray[] = '$dataLink->formatInput($item->' . $propertyNameValue . 'Id, ' . $dataTypeValue . ')';
                $sqlFieldsArray[] =  '`' . $fieldNameValue . '`';
                $parametersArray[] = '$' . $propertyNameValue . 'Id';
                $itemParametersArray[] = '        $item->' . $propertyNameValue . 'Id = ' . '$' . $propertyNameValue . 'Id';  
                
                ++$selfObjectCount;
              }
            }
          } 
          
          
          
          $parameters = implode(', ', $parametersArray);
          $itemParameters = implode(";\n", $itemParametersArray);
          $insertStrings = implode(' . \', \' . ', $insertStringsArray);
          $sqlFields = implode(', ', $sqlFieldsArray);
          
          eval('$result .= "' . $this->application->getTemplate('/autogeneration/manipulationComplexItem') . '";');  
        }
        else
        {
          $sqlFieldsArray = array();
          foreach ($referenceMap->fields as $referenceFieldItem)
          {
            $fieldNameValue = $referenceFieldItem->referenceField->fieldName . 'Id';   
            $sqlFieldsArray[] = $fieldNameValue;  
          } 
          
          $sqlFields = implode(', ', $sqlFieldsArray);
          
          if (!isset($otherObjectPropertyName))
          {
            $otherObjectPropertyName = $this->properties['name'];
          }
          
          eval('$result .= "' . $this->application->getTemplate('/autogeneration/manipulationSimpleItem') . '";');  
        }
      }
      
      return $result;
    }
        
    public function toDeleteFunction()
    {
      $result = '';
      
      $deleteMainReferences = '';
      
      $deleteDependentReferences = '';
      
      $referenceMaps = $this->getReferenceMaps();
          
      foreach ($referenceMaps as $referenceMapItem)   
      {
        $referenceMap = $referenceMapItem->referenceMap;
        $neededFieldType = $referenceMapItem->referenceFieldType;
        
        $result = '';
        $fields = $referenceMap->fields; 
        $selfObjectCounter = 0;
        
        foreach ($fields as $item)
        {
          if ($item->referenceField->referencedObjectId != $this->properties['identifier']) 
          {
            if ($item->type == BM_RT_MAIN)
            {
              $currentPropertyName = $item->referenceField->propertyName;
              
              foreach ($fields as $subItem)
              {
                if ($subItem->type == BM_RT_REFERRED)
                {
                  $thisPropertyName = $subItem->referenceField->propertyName;
                  break;
                }
              }
              
              $thisPropertyName = ucfirst($thisPropertyName);
              
              if ($referenceMap->isComplex())
              {
                $deleteDependentReferences .= $this->toDeleteDependentComplexReference($currentPropertyName, $thisPropertyName);  
              }
              else
              {
                $deleteDependentReferences .= $this->toDeleteDependentSimpleReference($currentPropertyName, $thisPropertyName);  
              }                                                                                                                 
            }
            elseif ($item->type == BM_RT_REFERRED)
            {
              $currentPropertyName = $item->referenceField->propertyName;
              
              $deleteMainReferences .= $this->toDeleteMainReference($currentPropertyName);
            }
          }
          else
          {
            if ($item->type == BM_RT_MAIN || $item->type == BM_RT_REFERRED)
            {
              ++$selfObjectCounter;  
            }
          }
        }
      
        if ($selfObjectCounter == 2 && $neededFieldType != null)
        {
          foreach ($fields as $item)
          {
            if ($item->type == $neededFieldType) 
            {
              $currentPropertyName = $item->referenceField->propertyName;
              
              if ($item->type == BM_RT_REFERRED)
              {
                $deleteMainReferences .= $this->toDeleteMainReference($currentPropertyName);
                
                foreach ($fields as $subItem)
                {
                  if ($subItem->type == BM_RT_REFERRED)
                  {
                    $thisPropertyName = $subItem->referenceField->propertyName;
                    break;
                  }
                }
                
                $thisPropertyName = ucfirst($thisPropertyName);
                
                if ($referenceMap->isComplex())
                {
                  $deleteDependentReferences .= $this->toDeleteDependentComplexReference($currentPropertyName, $thisPropertyName);  
                }
                else
                {
                  $deleteDependentReferences .= $this->toDeleteDependentSimpleReference($currentPropertyName, $thisPropertyName);  
                }      
              }
            }
          }
        }
      }
              
      eval('$result .= "' . $this->application->getTemplate('/autogeneration/deleteFunction') . '";');  
      
      return $result; 
    }
    
    public function toDeleteMainReference($referredObjectName)
    {
      $result = '\$this->remove' . ucfirst($referredObjectName) . 's();' . "\n\n      ";
      
      return $result;
    }
    
    public function toDeleteDependentSimpleReference($referredObjectName, $thisPropertyName)
    {
      $result = '';
      
      eval('$result .= "' . $this->application->getTemplate('/autogeneration/deleteDependentSimpleReference') . '";');  
      
      $result .= "\n\n      ";
      
      return $result; 
    }
    
        public function toDeleteDependentComplexReference($referredObjectName, $thisPropertyName)
    {
      $result = '';
      
      eval('$result .= "' . $this->application->getTemplate('/autogeneration/deleteDependentComplexReference') . '";');  
      
      $result .= "\n\n      ";
      
      return $result; 
    }
    
    public function toEditorFields()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $emptyFields = array();
      $existingFields = array(); 
      foreach($fields as $field)
      {
        if ($field->propertyName != 'identifier')
        {
          switch ($field->dataType)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = ' . $field->defaultValue . ';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = $' . $this->properties['name'] . '->' . $field->propertyName . ';';
            break;
            case BM_VT_STRING:
            case BM_VT_TEXT:
            case BM_VT_IMAGE:
            case BM_VT_FILE:
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = \'' . htmlspecialchars($field->defaultValue) . '\';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = htmlspecialchars($' . $this->properties['name'] . '->' . $field->propertyName . ');';
            break;
            case BM_VT_PASSWORD:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = \'\';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = \'\';';
            break;
          }
        }
      }
      $emptyFields = "/*FF::EDITOR::NEWFIELDS::{*/\n        " . implode("\n        ", $emptyFields) . "\n        /*FF::EDITOR::NEWFIELDS::}*/";
      $existingFields = "/*FF::EDITOR::EXISTINGFIELDS::{*/\n        " . implode("\n        ", $existingFields) . "\n        /*FF::EDITOR::EXISTINGFIELDS::}*/";
      return array($emptyFields, $existingFields);
    }
    
    public function toEditor($ancestorClass)
    {
      $this->checkDirty();
      $license = file_get_contents(projectRoot . '/conf/license.conf');
      list($emptyFields, $existingFields) = $this->toEditorFields();
      $editor = "<?php\n" . $license . "\n\n\n  final class bm" . ucfirst($this->properties['name']) . "EditPage extends " . $ancestorClass . "\n  {\n\n    public \$" . $this->properties['name'] . "Id = 0;\n\n\n    public function generate()\n    {\n\n      if (\$this->" . $this->properties['name'] . "Id == 0)\n      {\n\n        " . $emptyFields . "\n\n      }\n      else\n      {\n        \$" . $this->properties['name'] . " = new bm" . ucfirst($this->properties['name']) . "(\$this->application, array('identifier' => \$this->" . $this->properties['name'] . "Id));\n        if (\$this->application->errorHandler->getLast() != E_SUCCESS)\n        {\n          //TODO Error;\n          exit;\n        }\n\n        " . $existingFields . "\n\n      }\n      eval('\$this->content = \"' . \$this->application->getTemplate('/admin/" . $this->properties['name'] . "/" . $this->properties['name'] . "') . '\";');\n      \$page = parent::generate();\n      return \$page;\n    }\n  }\n?>";
      return $editor;
    }
    
    public function toSaveProcedureProperties()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $cgiProperies = array();
      $objectProperies = array(); 
      foreach($fields as $field)
      {
        if ($field->propertyName != 'identifier') 
        {
          switch ($field->dataType)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->propertyName . '\', ' . $field->defaultValue . ', ' . $this->dataTypeToString($field->dateType) . ');';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->propertyName) . ';';
            break;
            case BM_VT_STRING:
            case BM_VT_TEXT:
            case BM_VT_IMAGE:
            case BM_VT_FILE:
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->propertyName . '\', \'' . $field->defaultValue . '\', ' . $this->dataTypeToString($field->dataType) . ');';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->propertyName) . ';';
            break;
            case BM_VT_PASSWORD:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->propertyName . '\', \'\', BM_VT_STRING);';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->propertyName) . ';';
            break;
          }
        }
      }
      $cgiProperies = "/*FF::SAVE::CGIPROPERTIES::{*/\n      " . implode("\n      ", $cgiProperies) . "\n      /*FF::SAVE::CGIPROPERTIES::}*/";
      $objectProperies = "/*FF::SAVE::OBJECTPROPERTIES::{*/\n      " . implode("\n      ", $objectProperies) . "\n      /*FF::SAVE::OBJECTPROPERTIES::}*/";
      return array($cgiProperies, $objectProperies);
    }
    
    public function toSaveProcedure()
    {
      $this->checkDirty();
      $license = file_get_contents(projectRoot . '/conf/license.conf');
      list($cgiProperies, $objectProperies) = $this->toSaveProcedureProperties();
      $saveProcedure = file_get_contents(projectRoot . '/templates/admin/code/save.php');
      $saveProcedure = str_replace(array('%objectName%', '%upperCaseObjectName%', '%cgiProperties%', '%objectProperties%', '%licence%'), array($this->properties['name'], ucfirst($this->properties['name']), $cgiProperies, $objectProperies, $license), $saveProcedure);
      return $saveProcedure;
    }
    
    public function toEditorTemplateEditors()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $editors = array();
      foreach($fields as $field)
      {
        if ($field->propertyName != 'identifier') 
        {
          switch ($field->dataType)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
            case BM_VT_STRING:
            case BM_VT_TEXT:
            case BM_VT_PASSWORD: 
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $editor = file_get_contents(projectRoot . '/templates/admin/code/textBox.html');
              $propertyName = $field->propertyName;
              $upperCasePropertyName = ucfirst($field->propertyName);
              $propertyTitle = $field->propertyName;
              $editors[] = str_replace(array('%propertyTitle%', '%propertyName%', '%objectName%', '%upperCasePropertyName%'), array($propertyTitle, $propertyName, $this->properties['name'], $upperCasePropertyName), $editor);
            break;
            case BM_VT_IMAGE:
              $editor = file_get_contents(projectRoot . '/templates/admin/code/imageBox.html');
              $propertyName = $field->propertyName;
              $upperCasePropertyName = ucfirst($field->propertyName);
              $propertyTitle = $field->propertyName;
              $editors[] = str_replace(array('%propertyTitle%', '%propertyName%', '%objectName%', '%upperCasePropertyName%'), array($propertyTitle, $propertyName, $this->properties['name'], $upperCasePropertyName), $editor);
            break;
            case BM_VT_FILE:
              $editor = file_get_contents(projectRoot . '/templates/admin/code/fileBox.html');
              $propertyName = $field->propertyName;
              $upperCasePropertyName = ucfirst($field->propertyName);
              $propertyTitle = $field->propertyName;
              $editors[] = str_replace(array('%propertyTitle%', '%propertyName%', '%objectName%', '%upperCasePropertyName%'), array($propertyTitle, $propertyName, $this->properties['name'], $upperCasePropertyName), $editor);
            break;
            
          }
        }
      }
      $editors = "<!-- FF::AC::EDITORS::{ --> \n      " . implode("\n      ", $editors) . "\n      <!-- FF::AC::EDITORS::} -->";
      return $editors;
    }
    
    public function toEditorTemplate()
    {
      $this->checkDirty();
      $editors = $this->toEditorTemplateEditors();
      $editorTemplate = file_get_contents(projectRoot . '/templates/admin/code/edit.html');
      $editorTemplate = str_replace(array('%objectName%', '%upperCaseObjectName%', '%editors%'), array($this->properties['name'], ucfirst($this->properties['name']), $editors), $editorTemplate);
      return $editorTemplate;
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
        $result = 'BM_VT_TEXT';
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
            case 'BM_VT_TEXT':
              $tableField->FFDefault = '';
            break;
            case 'BM_VT_IMAGE':
              $tableField->FFDefault = '';
            break;
            case 'BM_VT_FILE':
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
    
    public function generateFiles()
    {
      $this->checkDirty();
      $fileName = projectRoot . '/controllers/bm' . ucfirst($this->properties['name']) . '.php';
        
      if (!file_exists($fileName))
      {
        file_put_contents($fileName, $this->toClass());   
      }
      else
      {
        $content = file_get_contents($fileName);
        
        $getterCaseProperties = array();
        $referenceFunctionProperties = array();
        
        $content = preg_replace('/\/\*FF::AC::MAPPING::\{\*\/(.+)\/\*FF::AC::MAPPING::\}\*\//ism', $this->toMapping(), $content);        
        preg_match_all('/\/\*FF::AC::GETTER_CASE::(.+)::\{\*\//', $content, $getterCaseProperties, PREG_PATTERN_ORDER);
        preg_match_all('/\/\*FF::AC::REFERENCE_FUNCTIONS::(.+)::\{\*\//', $content, $referenceFunctionProperties, PREG_PATTERN_ORDER);
        
        $customizedReferenceFunctionProperties = array_diff($getterCaseProperties[1], $referenceFunctionProperties[1]);
        
        $content = preg_replace('/\/\*FF::AC::TOP::GETTER::\{\*\/(.+)\/\*FF::AC::TOP::GETTER::\}\*\//ism', $this->toGetterCases(), $content);        
        $content = preg_replace('/\/\*FF::AC::DELETE_FUNCTION::\{\*\/(.+)\/\*FF::AC::DELETE_FUNCTION::\}\*\//ism', $this->toDeleteFunction(), $content);        
        
        $referenceFunctions = '';
        $referenceMaps = $this->getReferenceMaps();
        
        foreach ($referenceMaps as $referenceMapItem)   
        {
          $referenceFieldsPropertyNames = array();
          
          $referenceFieldItems = $referenceMapItem->referenceMap->fields;
          
          foreach ($referenceFieldItems as $referenceFieldItem)
          {
            $referenceFieldsPropertyNames[] = $referenceFieldItem->referenceField->propertyName;
          }
          
          $matchArray = array_intersect($referenceFieldsPropertyNames, $customizedReferenceFunctionProperties);
          
          if (count($matchArray) == 0)
          {
            $referenceFunctions .= '    ' . $this->toReferenceFunction($referenceMapItem) . "\n\n";  
          } 
        }
        
        $referenceFunctionsBlock = '';
        eval('$referenceFunctionsBlock .= "' . $this->application->getTemplate('/autogeneration/referenceFunctionsBlock') . '";');  
        
        $content = preg_replace('/\/\*FF::AC::TOP::REFERENCE_FUNCTIONS::\{\*\/(.+)\/\*FF::AC::TOP::REFERENCE_FUNCTIONS::\}\*\//ism', $referenceFunctionsBlock, $content);        
        
        file_put_contents($fileName, $content);   
      }
      
      
      
      //if (!file_exists($fileName))
//      {
//        file_put_contents($fileName, $this->toClass()); 
//      }
//      else
//      {
//        $content = file_get_contents($fileName);
//        $content = preg_replace('/\/\*FF::AC::MAPPING::\{\*\/(.+)\/\*FF::AC::MAPPING::\}\*\//ism', $this->toMapping(), $content);
        // $content = preg_replace('/\/\*FF::AC::GETTER::\{\*\/(.+)\/\*FF::AC::GETTER::\}\*\//ism', $this->toGetter(), $content);
//        
//        echo $content; exit;
//        
//        file_put_contents($fileName, $content);
//      }
      /*
      $fileName = documentRoot . '/modules/admin/' . $this->properties['name'] . '/';
      if (!file_exists($fileName))
      {
        mkdir($fileName, 0755, true);
      }
      $fileName .= 'index.php';
      if (!file_exists($fileName))
      {
        file_put_contents($fileName, $this->toEditor($ancestorPage));
      }
      else
      {
        $content = file_get_contents($fileName);
        list($newFields, $existingFields) = $this->toEditorFields();
        $content = preg_replace('/\/\*FF::EDITOR::NEWFIELDS::\{\*\/.+\/\*FF::EDITOR::NEWFIELDS::\}\*\//ism', $newFields, $content);
        $content = preg_replace('/\/\*FF::EDITOR::EXISTINGFIELDS::\{\*\/.+\/\*FF::EDITOR::EXISTINGFIELDS::\}\*\//ism', $existingFields, $content);
        file_put_contents($fileName, $content);
      }
      
      $fileName = documentRoot . '/modules/admin/' . $this->properties['name'] . '/rp/';
      if (!file_exists($fileName))
      {
        mkdir($fileName, 0755, true);
      }
      
      $fileName .= 'save.php';
      if (!file_exists($fileName))
      {
        file_put_contents($fileName, $this->toSaveProcedure());
      }
      else
      {
        $content = file_get_contents($fileName);
        list($cgiProperties, $objectProperties) = $this->toSaveProcedureProperties();
        $content = preg_replace('/\/\*FF::SAVE::CGIPROPERTIES::\{\*\/.+\/\*FF::SAVE::CGIPROPERTIES::\}\*\//ism', $cgiProperties, $content);
        $content = preg_replace('/\/\*FF::SAVE::OBJECTPROPERTIES::\{\*\/.+\/\*FF::SAVE::OBJECTPROPERTIES::\}\*\//ism', $objectProperties, $content);
        file_put_contents($fileName, $content);
      }
      
      $fileName = projectRoot . '/templates/admin/' . $this->properties['name'] . '/';
      if (!file_exists($fileName))
      {
        mkdir($fileName, 0755, true);
      }
            
      $fileName .= $this->properties['name'] . '.html';
      if (!file_exists($fileName))
      {
        file_put_contents($fileName, $this->toEditorTemplate());
      }
      else
      {
        $content = file_get_contents($fileName);
        $editors = $this->toEditorTemplateEditors();
        $content = preg_replace('/<!--\s+FF::AC::EDITORS::\{\s+-->.+<!--\s+FF::AC::EDITORS::\}\s+-->/ism', $editors, $content);
        file_put_contents($fileName, $content);
      }
      */
      //$generator = $this->application->generator;
      //$generator->addRoute('~^/admin/' . $this->properties['name'] . '/rp/save/(\d+)/?$~', '/modules/admin/' . $this->properties['name'] . '/rp/save.php', 'bmSave' . ucfirst($this->properties['name']), array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      //$generator->addRoute('~^/admin/' . $this->properties['name'] . '/(new|\d+)/?$~', '/modules/admin/' . $this->properties['name'] . '/index.php', 'bm' . ucfirst($this->properties['name']) . 'EditPage', array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      //$generator->addRoute('~^/' . $this->properties['name'] . '/(.+)/?$~', '/modules/view/' . $this->properties['name'] . '/index.php', 'bm' . ucfirst($this->properties['name']) . 'Page', array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      //$generator->serialize();
      
    }
    
    private function deleteFiles()
    {
      $this->checkDirty();
      $fileName = projectRoot . '/controllers/bm' . ucfirst($this->properties['name']) . '.php';
      if (file_exists($fileName))
      {
        unlink($fileName); 
      }
      $fileName = projectRoot . '/controllers/bm' . ucfirst($this->properties['name']) . 'Cache.php';
      if (file_exists($fileName))
      {
        unlink($fileName);
      }
    }
    
    /**
    * Удаляет информацию из служебных таблиц dataObjectMap и DataObjectFields,
    * а также удаляет саму таблицу датаобъекта
    * 
    */
    public function delete()
    {
      if ($this->type !== 1)     
      {
        $this->removeFields();
        $this->checkDirty();
        $this->dirty = array();
        
        $sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
        $this->application->dataLink->query($sql);
        $this->application->log->add($sql);
                
        $sql = "DROP TABLE `" . $this->name . "`;";
        $this->application->dataLink->query($sql);
        $this->application->log->add($sql);
            
        $this->deleteFiles();
      }
      else
      {
        echo '<b>Ошибка уровня ядра:</b> Нельзя удалять системный объект ($type == 1) класса bmDataObjectMap';
        exit;
      }
    }
    
    
    public function store()
    {
      if ($this->properties['identifier'] == 0)
      {
        $sql = "CREATE TABLE `" . $this->properties['name'] . "` (`id` int(10) unsigned NOT NULL AUTO_INCREMENT, PRIMARY KEY (`id`)) ENGINE=InnoDB  DEFAULT CHARSET=utf8 COLLATE=utf8_unicode_ci;";
        if (!$this->application->dataLink->query($sql))
        {
          throw new Exception();
        }
                
        $this->application->log->add($sql);
        $this->application->log->add($this->prepareSQL());
        
        // $dataObjectField = new bmDataObjectField($this->application, array('propertyName' => 'identifier', 'fieldName' => 'id', 'type' => BM_VT_INTEGER, 'defaultValue' => 0));
        // $this->addField($dataObjectField->identifier, 1);
        // unset($dataObjectField);   
      }  
      
      $this->dirty['generateFiles'] = true;
      parent::store();
    }
    
    public function save()
    {
      $this->dirty['generateFiles'] = true;
      $this->checkDirty();                                                 
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
          `link_dataObjectMap_dataObjectField`.`dataObjectFieldId` AS `identifier`
        FROM 
          `link_dataObjectMap_dataObjectField`
        WHERE 
          `link_dataObjectMap_dataObjectField`.`dataObjectMapId` = " . $this->properties['identifier'] . ";
      ";
      
      return $this->getSimpleLinks($sql, $cacheKey, 'dataObjectField', E_DATAOBJECTFIELDS_NOT_FOUND, $load);
    }                             
    
    public function getReferenceMaps($load = true)
    {
      $cacheKey = null;
      
      $sql = "
        SELECT 
          `link_referenceMap_referenceField`.`referenceMapId` AS `referenceMapId`,
          `link_referenceMap_referenceField`.`referenceFieldType` AS `referenceFieldType`
        FROM 
          `link_referenceField_dataObjectMap`
          INNER JOIN `link_referenceMap_referenceField`
          ON (`link_referenceField_dataObjectMap`.`referenceFieldId` = `link_referenceMap_referenceField`.`referenceFieldId`)
        WHERE 
          `link_referenceField_dataObjectMap`.`dataObjectMapId` = " . $this->properties['identifier'] . "
          AND `link_referenceMap_referenceField`.`referenceFieldType` IN (1, 2)
        ORDER BY 
          `referenceFieldType`;
      ";
        
      $map = array('referenceMap' => BM_VT_OBJECT, 'referenceFieldType' => BM_VT_INTEGER);
      
      return $this->getComplexLinks($sql, $cacheKey, $map, E_REFERENCEMAP_NOT_FOUND, $load);      
    }
  }

?>