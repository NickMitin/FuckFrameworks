<?php 
  
  abstract class bmFFData extends bmDataObject
  {
    public function __construct($application, $parameters = array())
    {
      $this->readonly = true;
      parent::__construct($application, $parameters);
    }

    public function __get($propertyName)
    {
      switch ($propertyName)
      {
        case 'dataObjectMapIds':
          if (!array_key_exists('dataObjectMapIds', $this->properties))
          {
            $this->properties['dataObjectMapIds'] = $this->getDataObjectMaps(false);
          }
          return $this->properties['dataObjectMapIds'];
        break;
        case 'dataObjectMaps':
          return $this->getDataObjectMaps();
        break;
        case 'referenceMapIds':
          if (!array_key_exists('referenceMapIds', $this->properties))
          {
            $this->properties['referenceMapIds'] = $this->getReferenceMaps(false);
          }
          return $this->properties['referenceMapIds'];
        break;
        case 'referenceMaps':
          return $this->getReferenceMaps();
        break;
        default:
          return parent::__get($propertyName);
        break;
      }
    }
    
    public function getDataObjectMaps($load = true)
    {
      $cacheKey = null;
      
      $sql = "SELECT 
                `id` AS `identifier` 
              FROM 
                 `dataObjectMap`
              WHERE 1;
             ";
             
      return $this->getSimpleLinks($sql, $cacheKey, 'dataObjectMap', E_DATAOBJECTMAP_NOT_FOUND, $load); 
    }
    
    public function getReferenceMaps($load = true)
    {
      $cacheKey = null;
      
      $sql = "SELECT 
                `id` AS `identifier` 
              FROM 
                 `referenceMap`
              WHERE 1;
             ";
      
      return $this->getSimpleLinks($sql, $cacheKey, 'referenceMap', E_REFERENCEMAP_NOT_FOUND, $load); 
    }       
  }
  
?>