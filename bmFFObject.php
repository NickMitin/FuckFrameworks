<?php
/**
	* Copyright (c) 2009, tbms.ru
	* All rights reserved.
	* 
	* Redistribution and use in source and binary forms, with or without
	* modification, are permitted provided that the following conditions are met:
	* - Redistributions of source code must retain the above copyright
	*   notice, this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright
  *   notice, this list of conditions and the following disclaimer in the
  *   documentation and/or other materials provided with the distribution.
  * - Neither the name of the tbms.ru nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY tbms.ru ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL tbms.ru BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	* 
	*/
		
	abstract class bmFFObject
	{
		protected $application = null;
		protected $properties = array();
    protected $events = array();
    protected $eventHandlers = array();
		
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
		
		protected function addProperty($propertyName, $value = null) 
		{
			if (!array_key_exists($propertyName, $this->properties)) 
			{
				$this->properties[$propertyName] = $value;
			}
		}
		
		protected function propertyExists($propertyName) {
		
			return array_key_exists($propertyName, $this->properties);
			
		}
		
		public function __get($propertyName)
		{
			if (array_key_exists($propertyName, $this->properties)) 
			{
				return $this->properties[$propertyName];
			}
		}
    
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