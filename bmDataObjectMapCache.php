<?php
  /*
  * Copyright (c) 2009, "The Blind Mice Studio"
  * All rights reserved.
  * 
  * Redistribution and use in source and binary forms, with or without
  * modification, are permitted provided that the following conditions are met:
  * - Redistributions of source code must retain the above copyright
  *   notice, this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright
  *   notice, this list of conditions and the following disclaimer in the
  *   documentation and/or other materials provided with the distribution.
  * - Neither the name of the "The Blind Mice Studio" nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY "The Blind Mice Studio" ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL "The Blind Mice Studio" BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
  * 
  */

  /**
  * Класс для взаимодействия объекта типа bmDataObjectMap с кэшем.
  * Позволяет получить поля объекта из базы данных или из кэша.
  */
  final class bmDataObjectMapCache extends bmCustomCache
  {
    /**
    * Метод возвращает поля объекта по идентификатору объекта. 
    * В методе создается ключ, по которому поля объекта могут находиться в кэше, также составляется sql-запрос для их загрузки из базы данных. 
    * В случае, если в кэше поля отсутствуют, они загружаются в кэш из базы данных в соответствии с составленным sql-запросом.
    * @param bmDataObjectMap $dataObjectMap объект.
    * @param boolean $load флаг, указывающий необходимо ли загружать полученные поля или только их идентификаторы. По умолчанию true.
    * @return array массив, содержащий поля объекта и его тип. 
    */
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