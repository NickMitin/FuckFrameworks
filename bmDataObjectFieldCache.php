<?php

  final class bmDataObjectFieldCache extends bmCustomCache
  {
    public function getDataObjectMap(bmDataObjectField $dataObjectField, $load = true)
    {
      $cacheKey = 'dataObjectField_dataObjectMap_' . $dataObjectField->identifier;
      
      $sql = "
        SELECT 
          `link_dataObjectMap_dataObjectField`.`dataObjectMapId` AS `dataObjectMapId`,
          `link_dataObjectMap_dataObjectField`.`type` AS `type`
        FROM 
          `link_dataObjectMap_dataObjectField`
        WHERE 
          `link_dataObjectMap_dataObjectField`.`dataObjectFieldId` = " . $dataObjectField->identifier . "
        LIMIT 1;
      ";
      
      $map = array('dataObjectMap' => BM_VT_OBJECT, 'type' => BM_VT_INTEGER);
      
      return $this->getComplexLinks($sql, $cacheKey, $map, E_DATAOBJECTMAP_NOT_FOUND, $load);
    }
  }

?>