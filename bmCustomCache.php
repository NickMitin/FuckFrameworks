<?php

  class bmCustomCache extends bmFFObject
  {
    
    public function getObject($objectId, $objectName, $fields)
    {                                             
      $result = $this->application->cacheLink->get($objectName . $objectId); 
      if ($result === false)
      {   
        $sql = "SELECT " . $fields . " FROM `" . $objectName . "` WHERE `id` = '" . $objectId . "' LIMIT 1;";

        $result = $this->application->dataLink->getObject($sql);
        
        if ($result == null)
        {
          $result = false;
          $this->application->errorHandler->add(E_DATA_OBJECT_NOT_EXISTS); 
        } 
        else 
        {
          $this->application->cacheLink->set($objectName . $objectId, $result, BM_CACHE_SHORT_TTL);
        }
      }

      return $result;
    }
    
    public function getObjects($objectIds, $objectName, $load = true) // TODO: $load - неиспользуемый параметр
    {
      $objectsFilter = array();
      
      $result = array();
      
      $className = 'bm' . ucfirst($objectName);
      
      foreach ($objectIds as $order => $objectId)
      {
        if ($object = $this->application->cacheLink->get($objectName . $objectId))
        {
          if (is_object($object))
          {
            $object->load = false;
            $result[$order] = new $className($this->application, get_object_vars($object));
          }
        }
        else
        {
          $objectsFilter[$order] = $objectId;
        }
      }

      if (count($objectsFilter) > 0)
      {
        $object = new $className($this->application, array('readonly' => true));
        
        $objectsFilterSQL = "'" . implode("', '", $objectsFilter) . "'";
        $fieldsSQL = $object->fieldsToSQL();
       
        $sql = "SELECT " . $fieldsSQL . " FROM `" . $objectName . "` WHERE `id` IN (" . $objectsFilterSQL . ") ORDER BY FIELD(`identifier`, " . $objectsFilterSQL . ");";
       
        $object = null;
        
        $orders = array_keys($objectsFilter); 
        
        $qObjects = $this->application->dataLink->select($sql);
        
        while ($object = $qObjects->nextObject())
        {                    
          $this->application->cacheLink->set($objectName . $object->identifier, $object, BM_CACHE_SHORT_TTL);  
          $object->load = false;
          
          foreach ($objectsFilter as $order => $objectId) 
          {
            if ($objectId == $object->identifier)
            {
              $result[$order] = new $className($this->application, get_object_vars($object)); 
            }
          }
        }
        
        $qObjects->free();
      }
      
      return $result;
    }
    
    public function getSimpleLink($sql, $cacheKey, $objectName, $errorCode, $load) {
      
      $result = $this->application->cacheLink->get($cacheKey);
      
      if ($result === false) {
        $result = $this->application->dataLink->getValue($sql);
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);

      }
      
      if ($result)
      {
        $this->application->errorHandler->add(E_SUCCESS);
        
        if ($load)
        {
          $className = 'bm' . ucfirst($objectName);
          $result = new $className($this->application, array('identifier' => $result, 'load' => true));
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return null;
      }
      return $result; 
    }    
    
    public function getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, $limit = 0, $offset = 0) {
      
      $result = $this->application->cacheLink->get($cacheKey);

      if ($result === false) {
        $qObjectIds = $this->application->dataLink->select($sql);
        $result = array();
        while ($objectId = $qObjectIds->nextObject()) {
          $result[] = $objectId->identifier;
        }
        $qObjectIds->free();
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
        

      }
    
      if (count($result) > 0)
      {
        if ($offset > 0)
        {           
          $result = array_slice($result, $offset);          
        }          
        
        if ($limit > 0)
        {         
          $result = array_slice($result, 0, $limit);         
        }
        
        $this->application->errorHandler->add(E_SUCCESS);
        
        if ($load)
        {
          $result = $this->getObjects($result, $objectName);
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return array();
      }
      return $result; 
    }
    
    protected function getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, $limit = 0, $offset = 0)
    {
      $result = $this->application->cacheLink->get($cacheKey);
      
      $objectArrays = false;
      if ($result === false) {
        
        $result = array();
        $qObjects = $this->application->dataLink->select($sql);
        if ($qObjects->rowCount() > 0)
        {
          $objectArrays = array();
          foreach($map as $propertyName => $type)
          {
            if ($type == BM_VT_OBJECT)
            {
              $objectArrays[$propertyName] = array();
            }
          }
          while ($object = $qObjects->nextObject()) 
          {
            $result[] = $object;
            
            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key][] = $object->{$key . 'Id'};
               
            }
          }
        }
   
        $qObjects->free();
        $this->application->cacheLink->set($cacheKey, $result, BM_CACHE_SHORT_TTL);
        $this->application->cacheLink->set($cacheKey . '_objectArrays', $objectArrays, BM_CACHE_SHORT_TTL);
      }
 
      if (count($result) > 0)
      {
        $this->application->errorHandler->add(E_SUCCESS);
        
        $dateTimePropertyNames = array();
        
        foreach($map as $propertyName => $type)
        {
          if ($type == BM_VT_DATETIME)
          {
            $dateTimePropertyNames[] = $propertyName;
          }
        }
        
        if (count($dateTimePropertyNames) > 0)
        {
          foreach ($dateTimePropertyNames as $dateTimePropertyName)
          {
            foreach ($result as $key => $value)
            {
              $result[$key]->$dateTimePropertyName = new bmDateTime($result[$key]->$dateTimePropertyName);
            }
          }
        }
          
        
        if ($load)
        {
          if (!$objectArrays)
          {
            $objectArrays = $this->application->cacheLink->get($cacheKey . '_objectArrays');               
          }
          
          if ($offset > 0)
          {           
            $result = array_slice($result, $offset);          

            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key] = array_slice($objectArrays[$key], $offset);
            }
          }          
          
          if ($limit > 0)
          {         
            $result = array_slice($result, 0, $limit);         
            
            foreach ($objectArrays as $key => $dummy)
            {               
              $objectArrays[$key] = array_slice($objectArrays[$key], 0, $limit);
            }
          }
          
          foreach ($objectArrays as $key => $dummy)
          {
            $objectArrays[$key] = $this->getObjects($objectArrays[$key], $key);
          }
          
                                                  
          foreach ($result as $order => $dummy)
          {
            foreach ($objectArrays as $key => $dummy)
            {  
              $result[$order]->$key = $objectArrays[$key][$order];              
            }
          }                
        }
      }
      else
      {
        $this->application->errorHandler->add($errorCode);
        return array();
      } 
                 
      return $result;
    }
    
  }
?>