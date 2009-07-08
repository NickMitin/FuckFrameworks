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

  final class bmDataObjectField extends bmDataObject
  {
    
    public function __construct($aplication, $parameters = array())
    {
      
      $this->objectName = 'dataObjectField';
      
      $this->map = array(
        'identifier' => array(
          'fieldName' => 'id',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
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
        'type' => array(
          'fieldName' => 'type',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'defaultValue' => array(
          'fieldName' => 'defaultValue',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        )
      );
      parent::__construct($aplication, $parameters);
      
    }
    
    public function __get($propertyName)
    {
      $this->checkDirty();
      switch ($propertyName)
      {
        case 'dataObjectMapId':
          if (!array_key_exists('dataObjectMapId', $this->properties))
          {
            $this->properties['dataObjectMapId'] = $this->application->dataObjectFieldCache->getDataObjectMap($this, false);
          }
          return $this->properties['dataObjectMapId'];
        break;
        case 'dataObjectMap':
          $this->properties['dataObjectMap'] = $this->application->dataObjectFieldCache->getDataObjectMap($this);
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
      
      $sql = "INSERT IGNORE INTO
                `link_dataObjectMap_dataObjectField`
                (`dataObjectFieldId`, `dataObjectMapId`, `type`)  
                VALUES
                  (" . $this->properties['identifier'] . ", " . $dataLink->formatInput($objectDataMapId[0]->dataObjectMapId, BM_VT_INTEGER) . ", " . $dataLink->formatInput($objectDataMapId[0]->type, BM_VT_INTEGER) . ");";
                  
      $dataLink->query($sql);
      
      $cacheLink->delete('dataObjectField_dataObjectMap_' . $this->properties['identifier']);
      $cacheLink->delete('dataObjectField_dataObjectMap_' . $this->properties['identifier'] . '_objectArrays');
      $this->dirty['saveDataObjectMap'] = false;      
    }
    
    public function delete()
    {
      $this->dirty = array();
      $sql = "DELETE FROM `" . $this->objectName . "` WHERE `id` = " . $this->properties['identifier'] . ";";
      $this->application->dataLink->query($sql);
    }
    
  }
  
?>