<?php

  final class bmDataObjectMapCache extends bmCustomCache
  {
    
    public function getFields(bmDataObjectMap $dataObjectMap, $load = true)
    {
      
      $cacheKey = 'dataObjectMap_dataObjectFields_' . $dataObjectMap->identifier;
      
      $sql = "
        SELECT 
          `link_dataObjectMap_dataObjectField`.`dataObjectFieldId` AS `dataObjectFieldId`,
          `link_dataObjectMap_dataObjectField`.`type` AS `type`
        FROM 
          `link_dataObjectMap_dataObjectField`
        WHERE 
          `link_dataObjectMap_dataObjectField`.`dataObjectMapId` = " . $dataObjectMap->identifier . ";
      ";
      
      $map = array('dataObjectField' => BM_VT_OBJECT, 'type' => BM_VT_INTEGER);
      
      return $this->getComplexLinks($sql, $cacheKey, $map, E_DATAOBJECTFIELDS_NOT_FOUND, $load);
      
    }  
  }
  
  

?>