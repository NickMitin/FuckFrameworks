<?php
  /**
  * Класс для работы экземпляра текущего приложения с кешем
  */
  class bmApplicationCache extends bmCustomCache
  {
    /**
    * Функция выполняет sql запрос, в котором получает идентификаторы dataObjectMap и загружает объект с ними
    * В зависимости от параметра $load, функция или возвращает идентификатор объекта или загруженный объект со всеми полями
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