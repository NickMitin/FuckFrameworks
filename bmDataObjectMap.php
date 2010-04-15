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
    private $savedPropertyValues = array();
    private $addedFieldIds = array();
    private $droppedFieldIds = array();
    
    /**
    * элемент массива - stdClass со свойствами fieldId, oldFieldName
    */
    private $renamedFields = array();
    
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
      
      if (!$this->itemExists($fieldId, 'dataObjectFieldId', $fieldIds))
      {
        $field = new stdClass();
        $field->dataObjectFieldId = $fieldId;
        $field->type = $type;
        $this->properties['fieldIds'][] = $field;
        
        $this->addedFieldIds[] = $fieldId;
      }
      
      $this->dirty['saveFields'] = true;
      $this->dirty['updateTableFields'] = true;
    }
    
    public function removeField($fieldId)
    {
      $fieldIds = $this->fieldIds;
      
      foreach ($fieldIds as $key => $item)
      {
        if ($item->dataObjectFieldId == $fieldId)
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
        $item->dataObjectField->delete();
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
      $insertStrings = array();
      foreach ($this->properties['fieldIds'] as $item)
      { 
        if (!in_array($item->dataObjectFieldId, $this->droppedFieldIds))
        {
          $insertStrings[] = "(" . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ", " . $dataLink->formatInput($item->dataObjectFieldId, BM_VT_INTEGER) . ", " . $dataLink->formatInput($item->type, BM_VT_INTEGER) . ")";
        }
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
      
      $this->dirty['saveFields'] = false;
    }
    
    protected function updateTableFields()
    {
      $dataLink = $this->application->dataLink; 
      
      $insertStrings = array();  
      
      foreach ($this->addedFieldIds as $id)
      {
        $dataObjectField = new bmDataObjectField($this->application, array('identifier' => $id));
        
        $insertStrings[] = 'ADD COLUMN `' . $dataObjectField->fieldName . '` ' . $dataLink->ffTypeToNativeType($dataObjectField->type, $dataObjectField->defaultValue);
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
                
        $dataLink->query($sql);
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
      }  
      
      $insertStrings = array();  
      foreach ($this->renamedFields as $item)
      {
        $dataObjectField = new bmDataObjectField($this->application, array('identifier' => $item->fieldId));
        $insertStrings[] = 'CHANGE `' . $item->oldFieldName . '` `' . $dataObjectField->fieldName . '` ' . $dataLink->ffTypeToNativeType($dataObjectField->type, $dataObjectField->defaultValue);
      }
      
      if (count($insertStrings) > 0)
      {
        $sql = "ALTER TABLE
                  `" . $this->name . "`" .
                implode(', ', $insertStrings) . ";";
        
        $dataLink->query($sql);
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
        case BM_VT_PASSWORD:
          $result = 'BM_VT_PASSWORD';
        break;
        case BM_VT_IMAGE:
          $result = 'BM_VT_IMAGE';
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
        $defaultValue = $field->dataObjectField->defaultValue;
        if ($field->dataObjectField->type == BM_VT_STRING || $field->dataObjectField->type == BM_VT_DATETIME || $field->dataObjectField->type == BM_VT_ANY || $field->dataObjectField->type == BM_VT_IMAGE)
        {
          $defaultValue = "'" . $defaultValue . "'";
        }
        if (($this->properties['type'] == 1 && $field->type == 0) || $this->properties['type'] == 0)
        {
          $mappingItems[] = "        '" . $field->dataObjectField->propertyName . "' => array(\n          'fieldName' => '" . $field->dataObjectField->fieldName . "',\n          'dataType' => " . $this->dataTypeToString($field->dataObjectField->type) . ",\n          'defaultValue' => " . $defaultValue . "\n        )";
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
      $licence = file_get_contents(projectRoot . '/conf/licence.conf');
      $class = "<?php\n" . $licence . "\n\n\n  final class bm" . ucfirst($this->properties['name']) . " extends bmDataObject\n  {\n\n    public function __construct(\$aplication, \$parameters = array())\n    {\n\n      " . $this->toMapping() . "\n\n      parent::__construct(\$aplication, \$parameters);\n    }\n\n  }\n?>";
      return $class;
    }
    
    public function toEditorFields()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $emptyFields = array();
      $existingFields = array(); 
      foreach($fields as $field)
      {
        if ($field->dataObjectField->propertyName != 'identifier')
        {
          switch ($field->dataObjectField->type)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = ' . $field->dataObjectField->defaultValue . ';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = $' . $this->properties['name'] . '->' . $field->dataObjectField->propertyName . ';';
            break;
            case BM_VT_STRING:
            case BM_VT_IMAGE:
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = \'' . htmlspecialchars($field->dataObjectField->defaultValue) . '\';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = htmlspecialchars($' . $this->properties['name'] . '->' . $field->dataObjectField->propertyName . ');';
            break;
            case BM_VT_PASSWORD:
              $emptyFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = \'\';';
              $existingFields[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = \'\';';
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
      $licence = file_get_contents(projectRoot . '/conf/licence.conf');
      list($emptyFields, $existingFields) = $this->toEditorFields();
      $editor = "<?php\n" . $licence . "\n\n\n  final class bm" . ucfirst($this->properties['name']) . "EditPage extends " . $ancestorClass . "\n  {\n\n    public \$" . $this->properties['name'] . "Id = 0;\n\n\n    public function generate()\n    {\n\n      if (\$this->" . $this->properties['name'] . "Id == 0)\n      {\n\n        " . $emptyFields . "\n\n      }\n      else\n      {\n        \$" . $this->properties['name'] . " = new bm" . ucfirst($this->properties['name']) . "(\$this->application, array('identifier' => \$this->" . $this->properties['name'] . "Id));\n        if (\$this->application->errorHandler->getLast() != E_SUCCESS)\n        {\n          //TODO Error;\n          exit;\n        }\n\n        " . $existingFields . "\n\n      }\n      eval('\$this->content = \"' . \$this->application->getTemplate('/admin/" . $this->properties['name'] . "/" . $this->properties['name'] . "') . '\";');\n      \$page = parent::generate();\n      return \$page;\n    }\n  }\n?>";
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
        if ($field->dataObjectField->propertyName != 'identifier') 
        {
          switch ($field->dataObjectField->type)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->dataObjectField->propertyName . '\', ' . $field->dataObjectField->defaultValue . ', ' . $this->dataTypeToString($field->dataObjectField->type) . ');';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->dataObjectField->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ';';
            break;
            case BM_VT_STRING:
            case BM_VT_IMAGE:
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->dataObjectField->propertyName . '\', \'' . $field->dataObjectField->defaultValue . '\', ' . $this->dataTypeToString($field->dataObjectField->type) . ');';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->dataObjectField->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ';';
            break;
            case BM_VT_PASSWORD:
              $cgiProperies[] = '$' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ' = $this->application->cgi->getGPC(\'' . $field->dataObjectField->propertyName . '\', \'\', BM_VT_STRING);';
              $objectProperies[] = '$' . $this->properties['name'] . '->' . $field->dataObjectField->propertyName . ' = $' . $this->properties['name'] . ucfirst($field->dataObjectField->propertyName) . ';';
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
      $licence = file_get_contents(projectRoot . '/conf/licence.conf');
      list($cgiProperies, $objectProperies) = $this->toSaveProcedureProperties();
      $saveProcedure = file_get_contents(projectRoot . '/templates/admin/code/save.php');
      $saveProcedure = str_replace(array('%objectName%', '%upperCaseObjectName%', '%cgiProperties%', '%objectProperties%', '%licence%'), array($this->properties['name'], ucfirst($this->properties['name']), $cgiProperies, $objectProperies, $licence), $saveProcedure);
      return $saveProcedure;
    }
    
    public function toEditorTemplateEditors()
    {
      $this->checkDirty();
      $fields = $this->fields;
      $editors = array();
      foreach($fields as $field)
      {
        if ($field->dataObjectField->propertyName != 'identifier') 
        {
          switch ($field->dataObjectField->type)
          {
            case BM_VT_INTEGER:
            case BM_VT_FLOAT:
            case BM_VT_STRING:
            case BM_VT_PASSWORD: 
            case BM_VT_DATETIME:
            case BM_VT_ANY:
              $editor = file_get_contents(projectRoot . '/templates/admin/code/textBox.html');
              $propertyName = $field->dataObjectField->propertyName;
              $upperCasePropertyName = ucfirst($field->dataObjectField->propertyName);
              $propertyTitle = $field->dataObjectField->propertyName;
              $editors[] = str_replace(array('%propertyTitle%', '%propertyName%', '%objectName%', '%upperCasePropertyName%'), array($propertyTitle, $propertyName, $this->properties['name'], $upperCasePropertyName), $editor);
            break;
            case BM_VT_IMAGE:
              $editor = file_get_contents(projectRoot . '/templates/admin/code/imageBox.html');
              $propertyName = $field->dataObjectField->propertyName;
              $upperCasePropertyName = ucfirst($field->dataObjectField->propertyName);
              $propertyTitle = $field->dataObjectField->propertyName;
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
            case 'BM_VT_IMAGE':
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
    
    public function generateFiles($ancestorPage)
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
        $content = preg_replace('/\/\*FF::AC::MAPPING::\{\*\/(.+)\/\*FF::AC::MAPPING::\}\*\//ism', $this->toMapping(), $content);
        file_put_contents($fileName, $content);
      }
      $fileName = projectRoot . '/controllers/bm' . ucfirst($this->properties['name']) . 'Cache.php';
      if (!file_exists($fileName))
      {
        $content = "<?php\n  final class bm" . ucfirst($this->properties['name']) . "Cache extends bmCustomCache\n  {\n\n\n  }\n?>";
        file_put_contents($fileName, $content);
      }
      
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
      
      $generator = $this->application->generator;
      $generator->addRoute('~^/admin/' . $this->properties['name'] . '/rp/save/(\d+)/?$~', '/modules/admin/' . $this->properties['name'] . '/rp/save.php', 'bmSave' . ucfirst($this->properties['name']), array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      $generator->addRoute('~^/admin/' . $this->properties['name'] . '/(new|\d+)/?$~', '/modules/admin/' . $this->properties['name'] . '/index.php', 'bm' . ucfirst($this->properties['name']) . 'EditPage', array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      $generator->addRoute('~^/' . $this->properties['name'] . '/(.+)/?$~', '/modules/view/' . $this->properties['name'] . '/index.php', 'bm' . ucfirst($this->properties['name']) . 'Page', array($this->properties['name'] . 'Id' => BM_VT_INTEGER));
      $generator->serialize();
      
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
      $this->removeFields();
      $this->checkDirty();
      $this->dirty = array();
      
      $sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
      $this->application->dataLink->query($sql);
      
      $sql = "DROP TABLE `" . $this->name . "`;";
      $this->application->dataLink->query($sql);
          
      $this->deleteFiles();
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
        // $dataObjectField = new bmDataObjectField($this->application, array('propertyName' => 'identifier', 'fieldName' => 'id', 'type' => BM_VT_INTEGER, 'defaultValue' => 0));
        // $this->addField($dataObjectField->identifier, 1);
        // unset($dataObjectField);   
      }  
      
      parent::store();
    }
    
    public function save()
    {
      $this->checkDirty();                                                 
    }
    
    public function renameTable()
    {
      $sql = "RENAME TABLE `" . $this->savedPropertyValues['name'] . "` TO `" . $this->properties['name'] . "`;";
      $this->application->dataLink->query($sql);
    }                            
  }

?>