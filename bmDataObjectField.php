<?php

  final class bmDataObjectMap extends bmDataObject
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
    
    public function setDataObjectMap($dataObjectMapId)
    {
      $this->dirty['saveDataObjectMap'] = true;
      $this->properties['dataObjectMapId'] = $dataObjectMapId;
    }
    
    private function saveDataObjectMap()
    {
      if (array_key_exists('dataObjectMapId', $this->properties))
      {
        $objectDataMapId = $this->properties['dataObjectMapId'];
        $sql = "DELETE FROM `link_dataObjectMap_dataObjectField` WHERE `dataObjectMapId` = " . $this->application->dataLink->formatInput($this->identifier, BM_VT_INTEGER);
        $this->application->dataLink->query($sql);
      
        foreach($categoryIds['categoryIds'] as $order => $categoryId)
        {
          $sql = "INSERT INTO `link_brickCategory_brick` (`brickId`, `brickCategoryId`, `type`) VALUES (" . $this->application->dataLink->formatInput($this->identifier, BM_VT_INTEGER) . ", " . $this->application->dataLink->formatInput($categoryId, BM_VT_INTEGER) . ", " . $this->application->dataLink->formatInput($categoryIds['types'][$order], BM_VT_INTEGER) . ");";
          $this->application->dataLink->query($sql);
        }
      
        $this->application->cacheLink->delete('brickCategories_' . $this->identifier);
        $this->application->cacheLink->delete('brickCategory_' . $this->identifier);
        $this->application->cacheLink->delete('categoryBricks_' . $this->identifier);
      }
    }
    
  }
  
?>