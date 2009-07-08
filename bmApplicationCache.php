<?php

  class bmApplicationCache extends bmCustomCache
  {
    public function getDataObjectMaps(bmApplication $application, $load = true)
    {
      $cacheKey = 'dataObjectMaps';
      $sql = "SELECT 
                `id` AS `identifier` 
              FROM 
                 `dataObjectMap`
              WHERE 1;
             ";
      return $this->getSimpleLinks($sql, $cacheKey, 'dataObjectMap', E_DATAOBJECTMAP_NOT_FOUND, $load); 
    }
  }

?>