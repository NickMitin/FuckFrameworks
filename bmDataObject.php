<?php

	abstract class bmDataObject extends bmFFObject implements IDataObject {
		
		public $map = array();
		public $objectName = '';
		public $dirty = array();
		public $dirtyQueue = array();
		protected $readonly = false;
		public $updateCount = 0;
		public $runningCheckDirty = false;
		
		
		public function __construct($application, $parameters = array())
		{
			
      $this->events = array('save', 'load', 'delete');
      
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
            if (get_class($result) == "DateTime") {
              return $result;
            } else {
						  return $result->getValue();
            }
					break;
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
					$this->properties[$propertyName] = new bmDateTime($value);
				}
				else
				{
					$this->properties[$propertyName] = $value;          
				}
				$this->dirty['store'] = true;
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
				$cache = $this->application->{$this->objectName . 'Cache'}->getObject($this->properties['identifier'], $this->objectName, $this->fieldsToSQL());
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
				$this->application->errorHandler->add(E_SUCCESS);    
				$this->dirty['store'] = false; 
        $this->triggerEvent('load', array('identifier' => $this->properties['identifier'])); 
			}
			else
			{
				$this->application->errorHandler->add(E_DATA_OBJECT_NOT_EXISTS);    
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
							$value = "'" . $dataLink->formatInput($propertyValue) . "'";
						break;
						case BM_VT_INTEGER:
							$value = intval($propertyValue);
						break;
						case BM_VT_FLOAT:
							$value = floatval($propertyValue);
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
		
	}
?>