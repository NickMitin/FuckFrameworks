<?php

  class bmApplicationCache extends bmCustomCache
  {
    public function getDataObjectMaps(bmApplication $application, $load = true)
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
    
    public function getReferenceMaps(bmApplication $application, $load = true)
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