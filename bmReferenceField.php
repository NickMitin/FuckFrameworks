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

  final class bmReferenceField extends bmDataObject
  {
    
    public function __construct($application, $parameters = array())
    {
      $this->objectName = 'referenceField';
      
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
        )
      );
      
      parent::__construct($application, $parameters);  
    }
    
    public function __get($propertyName)
    {
      $this->checkDirty();
      switch ($propertyName)
      {
        case 'referenceMapId':
          if (!array_key_exists('referenceMapId', $this->properties))
          {
            $this->properties['referenceMapId'] = $this->getReferenceMap(false);
          }
          return $this->properties['referenceMapId'];
        break;
        case 'referenceMap':
          return $this->getReferenceMap();
        break;
        case 'referencedObjectId':
          if (!array_key_exists('referencedObjectId', $this->properties))
          {
            $this->properties['referencedObjectId'] = $this->getRerefencedObject(false);
          }
          return $this->properties['referencedObjectId'];
        break;
        case 'referencedObject':
          return $this->getRerefencedObject();
        break;
        case 'localNames':
          if (!array_key_exists('localNames', $this->properties))
          {
            $this->properties['localNames'] = unserialize($this->properties['localName']);
          }
          return $this->properties['localNames'];
        break;
        default:
          return parent::__get($propertyName);
        break;
      }
    }
    
    public function setReferencedObject($dataObjectMapId)
    {
      $this->dirty['saveReferencedObject'] = true;
      
      if ($dataObjectMapId != 0 || $this->dataType != BM_VT_OBJECT)
      {
        $this->properties['referencedObjectId'] = $dataObjectMapId;
      }
    }
    
    public function removeReferencedObject()
    {
      unset($this->properties['referencedObjectId']);
      
      $this->dirty['saveReferencedObject'] = true;
    }
    
    public function saveReferencedObject()
    {
      $cacheLink = $this->application->cacheLink;
      $dataLink = $this->application->dataLink;
      
      $sql = "DELETE FROM `link_referenceField_dataObjectMap` WHERE `referenceFieldId` = " . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ";";
      $dataLink->query($sql);
      
      if (isset($this->properties['referencedObjectId']) && $this->properties['referencedObjectId'] != 0)
      {
        $sql = "INSERT IGNORE INTO
                  `link_referenceField_dataObjectMap`
                  (`dataObjectMapId`, `referenceFieldId`)  
                  VALUES
                    (" . $dataLink->formatInput($this->properties['referencedObjectId'], BM_VT_INTEGER) . ", " . $dataLink->formatInput($this->properties['identifier'], BM_VT_INTEGER) . ");";
                    
        $dataLink->query($sql);
      }
      
      $cacheLink->delete('referenceField_dataObjectMap_' . $this->properties['identifier']);
      $cacheLink->delete('referenceField_dataObjectMap_' . $this->properties['identifier'] . '_objectArrays');
      $this->dirty['saveReferencedObject'] = false;            
    }
    
    public function store()
    {
      if ($this->properties['dataType'] == BM_VT_OBJECT)
      {
        if (!isset($this->properties['referencedObjectId']) || $this->properties['referencedObjectId'] == 0)
        {
          echo '<b>Ошибка уровня ядра:</b> нет ссылки на dataObjectMap для объекта связи referenceField';
          exit;
        }
      }
      
      $this->dirty['generateFiles'] = true;
      parent::store();
    }
    
    public function generateFiles()
    {                                                         
      $dataObjectMap = $this->referencedObject;
      
      if ($dataObjectMap !== null)
      {
        $dataObjectMap->generateFiles();
      } 
    }
    
    public function save()
    {
      $this->dirty['generateFiles'] = true;
      $this->checkDirty();                                                 
    }
    
    public function delete()
    {
      $this->removeReferencedObject();
      $this->checkDirty();
      
      $sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
      $this->application->dataLink->query($sql);
      
      $this->dirty = array();
    }
    
    public function getReferenceMap($load = true)
    {
      $cacheKey = null;
      
      $sql = "
        SELECT 
          `link_referenceMap_referenceField`.`referenceMapId` AS `identifier`
        FROM 
          `link_referenceMap_referenceField`
        WHERE 
          `link_referenceMap_referenceField`.`referenceFieldId` = " . $this->properties['identifier'] . "
        LIMIT 1;
      ";
      
      return $this->getSimpleLink($sql, $cacheKey, 'referenceMap', E_REFERENCEMAP_NOT_FOUND, $load);
    }
    
    public function getRerefencedObject($load = true)
    {
      $cacheKey = null;
      
      $sql = "
        SELECT 
          `link_referenceField_dataObjectMap`.`dataObjectMapId` AS `identifier`
        FROM 
          `link_referenceField_dataObjectMap`
        WHERE 
          `link_referenceField_dataObjectMap`.`referenceFieldId` = " . $this->properties['identifier'] . "
        LIMIT 1;
      ";
      
      return $this->getSimpleLink($sql, $cacheKey, 'dataObjectMap', E_DATAOBJECTMAP_NOT_FOUND, $load);
    }
    
  }
  
?>