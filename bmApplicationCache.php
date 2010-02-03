<?php
  /**
  * Класс для работы экземпляра текущего приложения с кешем
  */
  class bmApplicationCache extends bmCustomCache
  {
    /**
    * В методе составляется sql запрос к базе данных, с помощью которого происходит получение идентификаторов dataObjectMap. Затем загружаются объекты с этими идентификаторами
    * В зависимости от параметра $load, метод или возвращает только идентификаторы объектов или загруженные объекты со всеми полями
    * @param bmApplication $application экземпляр текущего приложения
    * @param boolean $load флаг, указывающий необходимо ли загружать полученные объекты. По умолчанию true
    */
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