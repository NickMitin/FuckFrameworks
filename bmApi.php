<?php

  class bmApi extends bmFFObject 
  {
    public $lastString;
    public $logFile;
    public $isLogMode;
    
    public function __construct(bmApplication $application, $parameters = array())  
    {
      parent::__construct($application, $parameters); 
      
      $this->lastString = '  echo "\n";';
      $this->logFile = projectRoot . 'logs/api.php';
      $this->isLogMode = false;
    }
    
    public function log($text)
    {
      $text = '  ' . $text . "\n" . $this->lastString;
      
      $log = file_get_contents($this->logFile);
      $log = str_replace($this->lastString, $text, $log);
      file_put_contents($this->logFile, $log);
    }
    
    // Обо всех неожиданных ситуациях надо сообщать
    
    // Проверяем при каждой операции. Рапортуем, если лажа
    public function checkDataObjectFieldConsistency()
    {
      
    }
    
    // Проверяем при каждой операции. Рапортуем, если лажа
    public function checkDataObjectMapConsistency()
    {
      
    }
    
    // ИМ: создать, если нет
    // ИМ: пропустить, если есть
    // БД: создать, если нет
    // БД: пропустить, если есть
    public function createDataObjectMap($name)
    {
      if ($this->application->dataLink->tableExists($name))
      {
        if ($this->isLogMode)
        {
          echo 'DB table "' . $name . '" already exists.' . "\n";
        }
      }
      
      $dataObjectExists = false;
      
      foreach ($this->application->data->dataObjectMaps as $existingDataObjectMap)
      {
        if ($existingDataObjectMap->name == $name)
        {
          if ($this->isLogMode)
          {
            echo 'DataObject "' . $name . '" already exists.' . "\n";
          }
          
          $dataObjectExists = true;
          break;
        }
      }
      
      if (!$dataObjectExists)
      {
        $dataObjectMap = new bmDataObjectMap($this->application);
        $dataObjectMap->name = $name;
        $dataObjectMap->type = 0; // type == isSystemField
        $dataObjectMap->save(); 
        
        if ($this->isLogMode !== true)
        {
          $this->log('$application->api->createDataObjectMap(\'' . $name . '\');');
        }
      }
    }
    
    // ИМ: пропустить, если нет
    // ИМ: переименовать, если есть
    // БД: пропустить, если нет
    // БД: переименовать, если есть
    public function renameDataObjectMap($oldName, $newName)
    {
      if (!$this->application->dataLink->tableExists($oldName))
      {
        if ($this->isLogMode)
        {
          echo 'DB table "' . $oldName . '" does not exist.' . "\n";
        }
      }
      
      if ($this->application->dataLink->tableExists($newName))
      {
        if ($this->isLogMode)
        {
          echo 'DB table "' . $newName . '" already exists.' . "\n";
        }
      }
      
      $dataObjectMapId = $this->application->getObjectIdByFieldName('dataObjectMap', 'name', $oldName);
      $dataObjectMap = new bmDataObjectMap($this->application, array('identifier' => $dataObjectMapId));      
      
      $newDataObjectMapId = $this->application->getObjectIdByFieldName('dataObjectMap', 'name', $newName);
      
      if ($dataObjectMap && !$newDataObjectMapId)
      {
        $dataObjectMap->name = $newName;
        $dataObjectMap->save();   
        
        if ($this->isLogMode !== true)
        {
          $this->log('$application->api->renameDataObjectMap(\'' . $oldName . '\', \'' . $newName . '\');');
        }
      }
      else
      {
        if ($this->isLogMode)
        {
          if (!$dataObjectMap)
          {
            echo 'DataObject "' . $oldName . '" does not exist.' . "\n";
          }
          
          if ($newDataObjectMapId)
          {
            echo 'DataObject "' . $newName . '" already exists.' . "\n";
          }
        }
      }
    }
    
    // ИМ: пропустить, если нет
    // ИМ: удалить, если есть
    // БД: пропустить, если нет
    // БД: удалить, если есть
    public function deleteDataObjectMap($name)
    {
      if (!$this->application->dataLink->tableExists($name))
      {
        if ($this->isLogMode)
        {
          echo 'DB table "' . $name . '" does not exist.' . "\n";
        }
      }
      
      $dataObjectMapId = $this->application->getObjectIdByFieldName('dataObjectMap', 'name', $name);
      
      if ($dataObjectMapId)
      {
        $dataObjectMap = new bmDataObjectMap($this->application, array('identifier' => $dataObjectMapId));      
        
        $dataObjectMap->delete();
        
        if ($this->isLogMode !== true)
        {
          $this->log('$application->api->deleteDataObjectMap(\'' . $name . '\');');
        }
      }
      else
      {
        if ($this->isLogMode)
        {
          echo 'DataObject "' . $name . '" does not exist.' . "\n";
        }
      } 
    }
    
    // ИМ: создать поле и добавить, если нет
    // ИМ: ничего не делаем, если есть
    // БД: создаем, если нет
    // БД: ничего не делаем, если есть
    public function addFieldToDataObjectMap()
    {
      
    }
    
    // ИМ: ничего не делать, если нет
    // ИМ: изменить, если есть
    // БД: ничего не делать, если нет
    // БД: изменить, если есть
    public function changeFieldOfDataObjectMap()
    {
      
    }
    
    // ИМ: ничего не делаем, если нет
    // ИМ: удаляем, если есть
    // БД: ничего не делаем, если нет
    // БД: удаляем, если есть
    public function removeFieldFromDataObjectMap()
    {
      
    }

    // ИМ: создаем, если нет
    // ИМ: ничего не делаем, если есть
    // БД: создаем, если нет
    // БД: ничего не делаем, если есть
    public function createReferenceMap()
    {
      
    }
    
    // ИМ: пропускаем, если нет
    // ИМ: переименоваываем, если есть
    // БД: пропускаем, если нет       
    // БД: переименоваываем, если есть
    public function renameReferenceMap()
    {
      
    }
    
    // ИМ: пропускаем, если нет
    // ИМ: удаляем, если есть
    // БД: пропускаем, если нет
    // БД: удаляем, если есть
    public function deleteReferenceMap()
    {
      
    }
    
    // ИМ: создать поле и добавить, если нет
    // ИМ: ничего не делаем, если есть
    // БД: созадем, если нет
    // БД: ничего не делаем, если есть                                                             
    public function addFieldToReferenceMap()
    {
      
    }
    
    // ИМ: ничего не делать, если нет
    // ИМ: изменить, если есть
    // БД: ничего не делать, если нет
    // БД: изменить, если есть
    public function changeFieldOfReferenceMap() 
    {
      
    }
    
    // ИМ: ничего не делаем, если нет
    // ИМ: удаляем, если есть
    // БД: ничего не делаем, если нет
    // БД: удаляем, если есть
    public function removeFieldFromReferenceMap()
    {
      
    }
  }

?>