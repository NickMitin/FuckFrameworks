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
  * Базовый класс для дата объектов
  */
	abstract class bmFFObject
	{
		protected $application = null;
		protected $properties = array();
    protected $events = array();
    protected $eventHandlers = array();
		
    /**
    * Конструктор
    * 
    * @param bmApplication $application экземпляр класса текущего приложения
    * @param array $parameters параметры
    * @return bmFFObject
    */
		public function __construct($application, $parameters = array())
		{
			$this->application = $application;
			
      if (!is_array($parameters))
      {
        $parameters = array();
      }
      			
			foreach($parameters as $name => &$value)
			{        
				if (property_exists($this, $name))
				{
					$this->$name = $value;
				}
			}
		}

    /**
    * Возвращает элемент массива с заданным ключом или значение по умолчанию
    * 
    * @param array $parameters массив
    * @param mixed $parameter ключ элемента
    * @param mixed $defaultValue значение по умолчанию
    * @return mixed
    */
		protected function getParameter(&$parameters, $parameter, $defaultValue = null)
		{
			if (array_key_exists($parameter, $parameters))
			{
				return $parameters[$parameter];
			}
			else
			{
				return $defaultValue;
			}
		}
		
    /**
    * Добавляет свойство в объект
    * Добавление производится в том случае, если в объекте нет свойства с заданным именем
    * @param mixed $propertyName имя добавляемого свойства
    * @param mixed $value значение
    */
		protected function addProperty($propertyName, $value = null) 
		{
			if (!array_key_exists($propertyName, $this->properties)) 
			{
				$this->properties[$propertyName] = $value;
			}
		}
		
    /**
    * Проверяет существование свойства у объекта
    * 
    * @param mixed $propertyName имя свойства
    * @return bool true, если свойство существует
    */
		protected function propertyExists($propertyName) {
		
			return array_key_exists($propertyName, $this->properties);
			
		}
		
    /**
    * Магический метод, возвращает свойство с заданным именем
    * Обеспечивает синтаксис $object->propertyName
    * @param mixed $propertyName имя свойства
    * @return mixed значение свойства
    */
		public function __get($propertyName)
		{
			if (array_key_exists($propertyName, $this->properties)) 
			{
				return $this->properties[$propertyName];
			}
		}
    
    /**
    * Добавляет обработчик события
    *
    * @param string $eventName имя события
    * @param array $handler массив, содержащий пару объект-метод - обработчик события. Метод должен принимать 1 параметр = массив аргументов.
    */
    public function addEventListener($eventName, $handler)
    {
      $handlerObject = $handler[0];
      $handlerMethod = $handler[1];
      $key = spl_object_hash($handlerObject) . $handlerMethod;
      $eventHandlers = &$this->eventHandlers;
      
      $result = true;
      
      if (array_key_exists($eventName, $eventHandlers))
      {
        if (array_key_exists($key, $eventHandlers[$eventName]))
        {
          $result = false;
        }
      }
      else
      {
        $eventHandlers[$eventName] = array();
      }
      if ($result)
      {
        $eventInfo = new stdClass();
        $eventInfo->handlerObject = $handlerObject;
        $eventInfo->handlerMethod = $handlerMethod;
        $eventHandlers[$eventName][$key] = $eventInfo;
      }
      return $result;
    }
    
    /**
    * Удаляет обработчик события
    * 
    * @param string $eventName имя события
    * @param array $handler массив, содержащий пару объект-метод - обработчик события.
    */
    public function removeEventListener($eventName, $handler)
    {
      $handlerObject = $handler[0];
      $handlerMethod = $handler[1];
      $key = spl_object_hash($handlerObject) . $handlerMethod;
      
      $eventHandlers = &$this->eventHandlers;
      
      $result = true;
      
      if (array_key_exists($eventName, $eventHandlers))
      {
        if (array_key_exists($key, $eventHandlers[$eventName]))
        {
          unset($eventHandlers[$eventName][$key]);
        }
        else
        {
          $result = false;
        }
      }
      return $result;      
    }
    
    /**
    * Запускает обработку события
    * 
    * @param string $eventName имя события
    * @param array $parameters параметры, передаваемые обработчикам события
    */
    protected function triggerEvent($eventName, $parameters = array())
    {
      $eventHanlders = &$this->eventHandlers;
      if (in_array($eventName, $this->events) && array_key_exists($eventName, $eventHanlders))
      {
        foreach($eventHanlders[$eventName] as $key => $eventInfo)
        {
          $eventInfo->handlerObject->{$eventInfo->handlerMethod}($parameters);
        }
      }
    }
		
	}
	
?>