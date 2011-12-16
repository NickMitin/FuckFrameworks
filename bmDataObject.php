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

  /* Константы типа связанного объекта 
  *  BM_ROT = BM ReferencedObjectType
  */
  
  define('BM_ROT_MAIN', 1); // Главный объект связи
  define('BM_ROT_DEPENDED', 2); // Зависимый объект связи
  define('BM_ROT_ADDITIONAL', 3); // Добавочные, справочные объекты связи
  define('BM_ROT_NOT_AN_OBJECT', 4); // Простое свойство, а не объект
  
  abstract class bmDataObject extends bmFFObject {
    
    public $map = array();
    public $objectName = '';
    public $dirty = array();
    public $dirtyQueue = array();
    protected $readonly = false;
    public $updateCount = 0;
    public $runningCheckDirty = false;
    public $storage = 'rdbs+dods';
    private $cacheQueue = array();
    
    public function __construct(bmApplication $application, $parameters = array())
    {
      $this->map['identifier'] = array(
        'fieldName' => 'id',
        'dataType' => BM_VT_INTEGER,
        'defaultValue' => 0
      );
      
      $this->events = array('save', 'load', 'delete', 'propertyChange');
      
      foreach($this->map as $propertyName => $property)
      {
        $this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $property['defaultValue']);
      }
      
      parent::__construct($application, $parameters);
      
      foreach($this->map as $propertyName => $property)
      {
        if (array_key_exists($propertyName, $parameters)) 
        {
          if (!property_exists($this, $propertyName))
          {
            $this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $parameters[$propertyName]);
          }
        }
      }
      if (array_key_exists('identifier', $parameters) && ($parameters['identifier'] !== 0 && $parameters['identifier'] != ''))
      {           
        if (!array_key_exists('load', $parameters) || $parameters['load'] != false)
        {
          $this->load();
        }                                                                 
      }
      else
      {
        $this->dirty['store'] = true;
      }

    }
    
   protected function formatProperty($propertyName, $dataType, $value)
   {
      switch ($dataType)
      {
        case BM_VT_DATETIME:       
          $result = new bmDateTime($value);
        break;
        
        case BM_VT_INTEGER:
          $result = intval($value);
        break;
        
        case BM_VT_FLOAT:
          $result = floatval($value);
        break;
        
        default:
          $result = $value;            
        break;
      }
      return $result;
   } 
    
    public function __destruct()
    { 
      $this->invalidate();
      
      $this->checkDirty();
    }
    
    protected function checkDirty()
    {  
      if (!$this->runningCheckDirty)
      {
        $this->runningCheckDirty = true;
        
        if (!$this->readonly && $this->updateCount == 0)
        { 
          $this->dirty = array_merge($this->dirty, $this->dirtyQueue);
          $this->dirtyQueue = array();
          
          while (count($this->dirty) !== 0)
          {
            $actions = array();
            if (array_key_exists('store', $this->dirty) && $this->dirty['store'])
            {
              $this->store();
              unset($this->dirty['store']);
            }
            foreach($this->dirty as $method => $flag)
            { 
              if ($flag)
              { 
                $this->$method(); 
              }
            }
            
            if (count($this->dirtyQueue) > 0)
            {
              $this->dirty = $this->dirtyQueue;
              $this->dirtyQueue = array();            
            }
            else
            {
              $this->dirty = array();
            }
          }
        } 
        
        $this->runningCheckDirty = false;
      }
    }
    
    public function makeDirty($method)
    {
      $this->dirtyQueue[$method] = true;
    }
    
    public function __get($propertyName)
    { 
      $this->checkDirty();
      $result = parent::__get($propertyName);
      if (array_key_exists($propertyName, $this->map))
      {
        switch ($this->map[$propertyName]['dataType'])
        {
          case BM_VT_DATETIME:
            return $result;
          break;
          /*case BM_VT_PASSWORD:
            return '';
          break;*/
          default:
            return $result;
          break;
        }
      }
      
      
      
      if (isset($result))
      {
        return $result;
      }
    }
    
    public function __set($propertyName, $value)
    {
      if (array_key_exists($propertyName, $this->map))
      {
        if ($this->map[$propertyName]['dataType'] == BM_VT_DATETIME)
        {
          $value = new bmDateTime($value);
        }
        if ((string)$this->properties[$propertyName] != (string)$value)
        {
          $this->triggerEvent('propertyChange', array('identifier' => $this->properties['identifier'], 'propertyName' => $propertyName, 'oldValue' => $this->properties[$propertyName], 'newValue' => $value));
          if ($this->map[$propertyName]['dataType'] == BM_VT_PASSWORD)
          {
            if ($value == '')
            {
              return;
            }
            else
            {
              $value = md5($value);
            }
          }
          if ($this->map[$propertyName]['dataType'] == BM_VT_IMAGE)
          {
            if ($value != '')
            {
              
            }
            else
            {
              $fileName = (string)$this->properties[$propertyName];
              unlink(documentRoot . '/images/' . $this->objectName . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
              unlink(documentRoot . '/images/' . $this->objectName . '/admin/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
            }
          }
          if ($this->map[$propertyName]['dataType'] == BM_VT_FILE)
          {
            if ($value != '')
            {
              
            }
            else
            {
              $fileName = (string)$this->properties[$propertyName];
              unlink(documentRoot . '/files/' . $this->objectName . '/originals/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
              unlink(documentRoot . '/files/' . $this->objectName . '/admin/' . mb_substr($fileName, 0, 2) . '/' . $fileName);
            }
          }
          $this->properties[$propertyName] = $value;
          $this->dirty['store'] = true;
        }
        
      }
    }
    
    public function getObjectIdByField($fieldName, $value)
    {
      $sql = "SELECT `id` AS `identifier` FROM `" . $this->objectName . "` WHERE `" . $fieldName . "` = '" . $value . "';";
      $result = $this->application->dataLink->getValue($sql);
      return ($result != null) ? $result : 0;
    }
    
    public function load()
    {
      $objectName = mb_convert_case($this->objectName, MB_CASE_TITLE);
      if ($this->properties['identifier'])
      {        
        $cache = $this->getObject();
      }
      else
      {
        $cache = false;
      }
      
      
      if ($cache != false) 
      {
        foreach ($this->map as $propertyName => $property) 
        {                                                                                                                          
          $this->properties[$propertyName] = $this->formatProperty($propertyName, $property['dataType'], $cache->$propertyName);   
        }  
        $this->dirty['store'] = false; 
        $this->triggerEvent('load', array('identifier' => $this->properties['identifier'])); 
      }
      else
      { 
      } 
    }
    
    public function fieldsToSQL()
    {
      $fields = array();
      
      foreach ($this->map as $propertyName => $property)
      {
        $fields[] =  '`' . $property['fieldName'] . '` AS `' . $propertyName . '`';
      }
        
      $fields = "" . implode(',', $fields) . "";
      
      return $fields;
      
    }
    
    public function store() 
    {
      $saveIdentifier = $this->properties['identifier'];
      $dataLink = $this->application->dataLink;
      if ( ($this->properties['identifier'] === 0) || ($this->properties['identifier'] == '') )  
      {
        $this->properties['identifier'] = 'NULL';
      }
      
      $cacheObject = new stdClass();
      
      $fields = array();
      foreach ($this->map as $propertyName => $property) 
      {     
        $propertyValue = $this->properties[$propertyName];
        $cacheObject->$propertyName = $propertyValue;
        
        $value = $property['defaultValue'];
        if ($propertyValue !== 'NULL')
        {
          switch ($property['dataType']) 
          {
            case BM_VT_STRING:
            case BM_VT_TEXT:
              $value = "'" . $dataLink->formatInput($propertyValue) . "'";
            break;
            case BM_VT_INTEGER:
              $value = intval($propertyValue);
            break;
            case BM_VT_FLOAT:
              $value = floatval($propertyValue);
            break;
            case BM_VT_PASSWORD:
              $value = "'" . (string)$propertyValue . "'";
            break;
            case BM_VT_IMAGE:
              $value = "'" . (string)$propertyValue . "'";
            break;
            case BM_VT_FILE:
              $value = "'" . (string)$propertyValue . "'";
            break;
            case BM_VT_DATETIME:
              $value = "'" . (string)$propertyValue . "'";
            break;
          }
        }
        else
        {
          $value = 'NULL';
        }
        $fields[] = '`' . $property['fieldName'] . '` = ' . $value;
      }
      
      if ($this->storage == 'rdbs+dods')
      {
        $fields = implode(',', $fields);
        
        $sql = "INSERT INTO `" . $this->objectName . "` SET " . $fields . " ON DUPLICATE KEY UPDATE " . $fields . ";";
        $objectId = $this->application->dataLink->query($sql);
        if (($objectId = $this->application->dataLink->insertId()) != 0)
        {
          $this->properties['identifier'] = $objectId;
          $cacheObject->identifier = $objectId;
        }
        else 
        {
          $this->properties['identifier'] = $saveIdentifier;
          $cacheObject->identifier = $saveIdentifier;
        }
        
        $this->application->cacheLink->set($this->objectName . $this->properties['identifier'], $cacheObject, BM_CACHE_SHORT_TTL);
      }
      
      
      if ($this->storage == 'dods')
      {
        $this->application->cacheLink->set($this->objectName . $this->properties['identifier'], $cacheObject, BM_CACHE_LONG_TTL, true);  
        $result = $this->application->cacheLink->get($this->objectName . $this->properties['identifier']); 
      }
    }
    
    public function save() 
    { 
      $this->checkDirty();
      $this->triggerEvent('save', array('identifier' => $this->properties['identifier']));
    }
    
    public function delete()
    {
      $this->readonly = true;
      $this->triggerEvent('delete', array('identifier' => $this->properties['identifier']));
    }
    
    public function beginUpdate()
    {
      ++$this->updateCount;
    }
    
    public function endUpdate()
    {
      $this->updateCount = $this->updateCount == 0 ? 0 : --$this->updateCount;
    }
    
    public function invalidate()
    {
      $this->updateCount = 0;
    }
    
    protected function itemExists($key, $propertyName, &$collection)
    {
      foreach ($collection as $item)
      {
        if ($item->$propertyName == $key)
        {
          return true;
        }
      }
      return false;
    }
    
    protected function searchItem($key, $propertyName, $collection)
    {
      foreach ($collection as $index => $item)
      {
        if ($item->$propertyName == $key)
        {
          return $index;
        }
      }
      return false;
    }
    
    public function getObject()
    {                                             
      $objectId = $this->properties['identifier'];
      $objectName = $this->objectName;
      
      if ($this->application->debug == false || $this->storage == 'dods')
      {
        $result = $this->application->cacheLink->get($objectName . $objectId); 
      }
      else
      {
        $result = false;                           
      }
      
      if ($result === false && $this->storage == 'rdbs+dods')
      {   
        $fields = $this->fieldsToSQL();
        
        $sql = "SELECT " . $fields . " FROM `" . $objectName . "` WHERE `id` = '" . $objectId . "' LIMIT 1;";

        $result = $this->application->dataLink->getObject($sql);
        
        if ($result == null)
        {
          $result = false;
        } 
        else 
        {
          $this->application->cacheLink->set($objectName . $objectId, $result, BM_CACHE_SHORT_TTL);
        }
      }

      return $result;
    }
    
    public function getObjects($objectIds, $objectName)
    {
      $objectsFilter = array();
      
      $result = array();
      
      $className = 'bm' . ucfirst($objectName);
      
      foreach ($objectIds as $order => $objectId)
      {
        if ($this->application->debug == false || $this->storage == 'dods')
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
        else
        {
          $objectsFilter[$order] = $objectId;                           
        }
      }

      if (count($objectsFilter) > 0)
      {
        
        $objectsFilterSQL = "'" . implode("', '", $objectsFilter) . "'";
        $object = new $className($this->application, array('readonly' => true));
        
        $fieldsSQL = $object->fieldsToSQL();
       
        $sql = "SELECT " . $fieldsSQL . " FROM `" . $objectName . "` WHERE `id` IN (" . $objectsFilterSQL . ") ORDER BY FIELD(`identifier`, " . $objectsFilterSQL . ");";
        
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
    
    protected function getSimpleLink($sql, $cacheKey, $objectName, $errorCode, $load) 
    {
      $bigResult = $this->getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, 1, 0);
      
      if (count($bigResult) > 0)
      {
        $result = $bigResult[0];
      }
      else
      {
        $result = null;
      }
      
      return $result;
    }      
    
    protected function getSimpleLinks($sql, $cacheKey, $objectName, $errorCode, $load, $limit = 0, $offset = 0) 
    {
      if ($this->application->debug == false || $this->storage == 'dods')
      {
        $result = $this->application->cacheLink->get($cacheKey);
      }
      else
      {
        $result = false;                           
      }
                                                                
      if ($result === false) 
      {
        $qObjectIds = $this->application->dataLink->select($sql);
        $result = array();
        
        while ($objectId = $qObjectIds->nextObject()) 
        {
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
        
        if ($load)
        {
          $result = $this->getObjects($result, $objectName);
        }
      }
      else
      {
        return array();
      }
      return $result; 
    }
    
    protected function getComplexLink($sql, $cacheKey, $map, $errorCode, $load)
    {
      $bigResult = $this->getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, 1, 0);

      if (count($bigResult) > 0)
      {
        $result = $bigResult[0];
      }
      else
      {
        $result = null;
      }
      
      return $result;
    }
    
    protected function getComplexLinks($sql, $cacheKey, $map, $errorCode, $load, $limit = 0, $offset = 0)
    {
      if ($this->application->debug == false || $this->storage == 'dods')
      {
        $result = $this->application->cacheLink->get($cacheKey);
      }
      else
      {
        $result = false;                           
      }
      
      $objectArrays = false;
      
      // Преобразование строки маппинга в массив маппинга
      // Находится вне кэша и выполняется в любом случае
      $propertyObjectLink = array('propertyName' => array(), 'objectName' => array());
      foreach($map as $propertyName => $dummy)
      {
        $tempArray = preg_split('/ IS /', $propertyName, 2, PREG_SPLIT_NO_EMPTY);
        
        $currentPropertyName = $tempArray[0];
        count($tempArray) > 1 ? $currentObjectName = $tempArray[1] : $currentObjectName = $tempArray[0];
        
        $propertyObjectLink['propertyName'][] = $currentPropertyName;
        $propertyObjectLink['objectName'][] = $currentObjectName;
      }
      // Конец преобразования
      
      if ($result === false) {
        
        $result = array();
        $qObjects = $this->application->dataLink->select($sql);
        if ($qObjects->rowCount() > 0)
        {
          $objectArrays = array();
          
          $mapCounter = 0; // Счетчик, чтобы не парсить имя свойства еще раз
          
          foreach($map as $propertyName => $type)
          {
            if ($type == BM_VT_OBJECT)
            {
              $objectArrays[$propertyObjectLink['propertyName'][$mapCounter]] = array();
            }
            
            ++$mapCounter; // ++счетчик
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
        $dateTimePropertyNames = array();
          
        if ($load)
        {
          if (!$objectArrays)
          {
            if ($this->application->debug == false || $this->storage == 'dods')
            {
              $objectArrays = $this->application->cacheLink->get($cacheKey . '_objectArrays');
            }
            else
            {
              $objectArrays = false;                           
            }                                                  
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
            // Находим objectName по propertyName в созданном нами массиве
            $index = array_search($key, $propertyObjectLink['propertyName']);
            $objectName = $propertyObjectLink['objectName'][$index];
            // Нашли
            
            $objectArrays[$key] = $this->getObjects($objectArrays[$key], $objectName);
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
        return array();
      } 
                 
      return $result;
    }
    
    protected function enqueueCache($methodKey)
    {
      $this->cacheQueue[$methodKey][] = $this->properties['identifier'];
      $this->dirty['validateCache'] = true;
    }
    
    protected function validateCache()
    {
      foreach ($this->cacheQueue as $methodKey => $objectIds)
      {
        switch ($methodKey)
        {
          case 'country__store':
            foreach ($objectIds as $objectId)
            {
              $this->application->cacheLink->delete('country_synonym_' . $objectId);
            }
          break;
          case 'resort__store':
            foreach ($objectIds as $objectId)
            {
              $this->application->cacheLink->delete('resort_synonym_' . $objectId);
            }
          break;
          case 'hotel__store':
            foreach ($objectIds as $objectId)
            {
              $this->application->cacheLink->delete('hotel_synonym_' . $objectId);
            }
          break;
        }
      }
      $this->dirty['validateCache'] = false;
    } 
  }
?>
