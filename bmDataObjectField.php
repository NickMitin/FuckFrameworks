<?php

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
    
  }
  
?>