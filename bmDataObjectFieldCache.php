<?php

  final class bmDataObjectFieldCache extends bmCustomCache
  {
    public function getDataObjectMap(bmDataObjectField $dataObjectField, $load = true)
    {
      $cacheKey = 'dataObjectField_dataObjectMap_' . $dataObjectField->identifier;
      
      $sql = "
        SELECT 
          `link_dataObjectMap_dataObjectField`.`dataObjectMapId` AS `dataObjectMapId`,
          `link_dataObjectMap_dataObjectField`.`brickColorId` AS `brickColorId`,
          `link_set_brick`.`quantity` AS `quantity`
        FROM 
          `link_set_brick` JOIN `set` ON (`link_set_brick`.`setId` = `set`.`id`)
          JOIN `brickColor` ON (`link_set_brick`.`brickColorId` = `brickColor`.`id`)
        WHERE 
          `link_set_brick`.`brickId` = " . $brick->identifier . $typeFilter . "
        ORDER BY " . $orderBy . "
        ;
      ";
      
      $map = array('set' => BM_VT_OBJECT, 'brickColor' => BM_VT_OBJECT, 'quantity' => BM_VT_INTEGER);
      
      return $this->getComplexLinks($sql, $cacheKey, $map, E_BRICKS_SETS_NOT_FOUND, $load);
    }
  }

?>