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
  * Операция завершилась успешно
  * @name E_SUCCESS
  */
  define('E_SUCCESS', 0);
  define('E_DATA_OBJECT_NOT_EXISTS', 106);   
  define('E_DATAOBJECTMAP_NOT_FOUND', 107);   
  define('E_DATAOBJECTFIELDS_NOT_FOUND', 108);   
  
  define('E_REFERENCEMAP_NOT_EXISTS', 109);   
  define('E_REFERENCEMAP_NOT_FOUND', 110);   
  define('E_REFERENCEFIELDS_NOT_FOUND', 111);   
  
  define('E_OBJECTS_NOT_FOUND', 112);   
  
  define('BM_VT_ANY', 0);
	/**
	* Строка
	*/
	define('BM_VT_STRING', 1);
	/**
	* Целое число
	*/
	define('BM_VT_INTEGER', 2);
	/**
	* Число с плавающей точкой
	*/
	define('BM_VT_FLOAT', 3);
	/**
	* Дата
	*/
	define('BM_VT_DATETIME', 4);
	/**	
	* Объект
	*/
	define('BM_VT_OBJECT', 5);
  /**  
  * Пароль
  */
  define('BM_VT_PASSWORD', 6);
  /**  
  * Изображение
  */
  define('BM_VT_IMAGE', 7);
  /**  
  * 
  * Файл
  */
  define('BM_VT_FILE', 8);
  /**  
  * Длинный текст
  */
  define('BM_VT_TEXT', 9);
  
  
  
	
	/**
	* Базовый класс приложений
	*/
	abstract class bmCustomApplication extends bmFFObject
	{
		public $templatecache = array();
		public $doctype = '<DOCTYPE html>';
		public $action = '';
		public $dataLink = null;
		public $user = null;		
		public $session = null;
		public $debug = BM_C_DEBUG;
    public $locale = ''; 
		
		/**
		* Конструктор класса
		* 
		* @param bmApplication $application указатель на приложение. должен быть null
		* @param array $parameters параметры, необходимые для инициализации экземпляра приложения
		* @return bmApplication
		*/
		public function __construct($application, $parameters = array())
		{
			$this->locale = C_LOCALE;
      parent::__construct($application, $parameters);
			$this->action = $this->cgi->getGPC('action', '');
			
			$this->dataLink = new bmMySQLLink($this);
			register_shutdown_function(array($this, 'save'));
		}
		
		/**
		* Выполняет авторизацию пользователя. 
		* В случае, если проверка пары email/пароль прошла успешно, то функция обновляет $this->user и сохраняет информацию о пользователе в $this->session
		* -
		* 
		* @param string $email e-mail пользователя
		* @param string $password пароль пользователя в открытом виде
		* @param bool $isMD5 флаг, указывающий на тип параметра $password - plaintext или md5
		* @return bool флаг успеха авторизации
		*/
		public function login($email, $password, $isMD5 = false)
		{
      $result = false;
			if (($this->session->userId != C_DEFAULT_USER_ID) && ($this->session->userId != 0))
			{
				$this->user->identifier = $this->session->userId;
				$this->user->lastVisit = $this->session->createTime;
				$this->timeOnline = time() - $this->session->createTime;        
				$this->user->store();
				$result = true;
			}
			
			$dataLink = $this->dataLink;
			
			$password = $dataLink->formatInput($password);
			if(!$isMD5)
			{
				$password = md5($password);
			}
			
			$sql = "SELECT 
								`id` AS `id`
							FROM
								`user`
							WHERE
								`email` = '" . $dataLink->formatInput($email) . "' AND 
								`password` = '" . $password . "'
							LIMIT 1;";
			$user = $dataLink->getObject($sql);
			
      if ($user)
			{
				$result = true;
				$this->session->userId = $user->id;
				$this->session->createTime = time();
				$this->session->store();
				
        $this->user->identifier = $user->id;
				$this->user->load();
				/*
        $sql = "INSERT IGNORE INTO 
								`link_user_session`
								SET 
									`userId` = '" . $dataLink->formatInput($this->session->userId) . "',
									`sessionHash` = '" . $dataLink->formatInput($this->session->identifier) . "',
									`ipAddress` = '" . $dataLink->formatInput($this->session->ipAddress) . "';";
									
				$dataLink->query($sql);
        */
			}
		 
			return $result;
		}
		
		/**
		* Выполняет деавторизацию пользователя.
		* Сбрасывает $this->user в гостя
		* 
		* @return bool флаг успеха: false, если пользователь является гостем, иначе true
		*/
		public function logout()
		{
			$result = false;
			if ($this->session->userId != C_DEFAULT_USER_ID)
			{
				$result = true;
				
				$this->user->lastVisit = $this->session->createTime;
				$this->user->timeOnline = time() - $this->session->createTime;
        //$this->user->password = '';
				//$this->user->store();
				
				$dataLink = $this->dataLink;
				/*
        $sql = "DELETE FROM 
									`link_user_session`
								WHERE 
									`userId` = '" . $dataLink->formatInput($this->session->userId) . "';"; 
				$dataLink->query($sql);
				*/
				$this->session->userId = C_DEFAULT_USER_ID;
				$this->session->createTime = time();
				$this->session->save();      
				$this->user->identifier = C_DEFAULT_USER_ID;
				$this->user->load();
			}
			return $result;
		}
		
		public function __get($propertyName)
		{
		  switch ($propertyName)
      {
        case 'dataObjectMapIds':
          if (!array_key_exists('dataObjectMapIds', $this->properties))
          {
            $this->properties['dataObjectMapIds'] = $this->data->getDataObjectMaps($this, false);
          }
          return $this->properties['dataObjectMapIds'];
        break;
        case 'dataObjectMaps':
          return $this->data->getDataObjectMaps($this);
        break;
        case 'referenceMapIds':
          if (!array_key_exists('referenceMapIds', $this->properties))
          {
            $this->properties['referenceMapIds'] = $this->data->getReferenceMaps($this, false);
          }
          return $this->properties['referenceMapIds'];
        break;
        case 'referenceMaps':
          return $this->data->getReferenceMaps($this);
        break;
        default:
          $className = 'bm' . ucfirst($propertyName);
          if (class_exists($className))
          {
            if (!$this->propertyExists($propertyName)) {
              $this->addProperty($propertyName, new $className($this));
            }
          }
          return parent::__get($propertyName);
        break;
      }
		}
		
		/**
		* Сохраняет состояние объекта. Не реализована.
		* @todo определить необходимость присутствия этой функции
		*/
		public function save()
		{
 
		}

		/**
		* Выполняет конвертацию переданной строки в UTF-8 
		* 
		* @param string $string исходная строка
		* @return string результат конвертации переданной строки
		*/
		public function decodeUrl($string)
		{
			$encoding = mb_detect_encoding($string, 'UTF-8, CP1251');
			if ($encoding != 'UTF-8')
			{
				$string = mb_convert_encoding($string, 'UTF-8', $encoding);
			}
			return $string;
		}
  
    /**
    * Возвращает содержимое шаблона
    * Функция пытается прочесть шаблон из кеша, если невозможно, то (если это разрешено параметром $read), читает с диска
    * 
    * @param string $templateName имя шаблона
    * @param bool $escape необходимо ли выполнить экранирование. по умолчанию - true
    * @param bool $read разрешено ли чтение шаблона с диска. по умолчанию - false
    * @param string $path путь к корню проекта. по умолчанию - значение константы projectRoot
    * @todo проверить логику функции
    */
		public function getTemplate($templateName, $escape = true, $read = false, $path = projectRoot)
		{
			//$template = $this->debug ? false : $this->cacheLink->get(C_TEMPLATE_PREFIX . $templateName);
			$template = (BM_C_CACHE_TEMPLATES !== true)? false : $this->cacheLink->get(C_TEMPLATE_PREFIX . $templateName);
      
      if ($template === false || $read !== false)
			{
				$template = file_get_contents($path . '/templates/' . $templateName . '.html');

				$this->cacheLink->set(C_TEMPLATE_PREFIX . $templateName, $template);
			}
			if ($escape)
			{
				$template = addcslashes($template, '"');
			}
			return $template;
		}
		
		/**
		* Создает stdObject по переданному ассоциативному массиву
		* 
		* @param array $parameters 
		* @return stdClass
		*/		
		public function createObject($parameters)
		{
			$result = new stdClass();
			
			foreach($parameters as $parameterName => $parameterValue)
			{
				$result->$parameterName = $parameterValue;
			}
			return $result;
		}
    
    /**
    * Выполняет фильтрацию(валидацию) переданного значения в соответствии с его типом
    * 
    * @param string $value значение, подлежащее конвертации
    * @param mixed $type код типа значения
    * @return string
    */
    public function validateValue($value, $type = BM_VT_ANY) {
      switch ($type) {
        case BM_VT_INTEGER:
          $value = intval($value);
        break;
        
        case BM_VT_FLOAT:
          $value = floatval($value);
        break;
        case BM_VT_STRING:
        case BM_VT_TEXT:  
          $value = trim($value);
        break;
      }
      return $value;
    }
    
    public function declineNumber($value, $strings)
    {
      
      if($value > 100) {
        $value = $value % 100;
      }
      
      $firstDigit = $value % 10;
      $secondDigit = floor($value / 10);
      
      if ($secondDigit != 1) {
        if ($firstDigit == 1) {
          return $strings[0];
        } else if ($firstDigit > 1 && $firstDigit < 5) {
          return $strings[1];
        } else {
          return $strings[2];
        }
      } else {
        return $strings[2];
      }

    }
    
    public function getObjectIdByFieldName($objectName, $fieldName, $value)
    {
      $sql = "SELECT `id` AS `identifier` FROM `" . $objectName . "` WHERE `" . $fieldName . "` = '" . $this->dataLink->formatInput($value, BM_VT_STRING) . "';";
      $result = $this->dataLink->getValue($sql);
      return ($result != null) ? $result : 0;
    }
    
    public function getObjectIdBySynonym($objectName, $synonym)
    {
      $sql = "SELECT `" . $objectName . "Id` AS `identifier` FROM `" . $objectName . "_synonym` WHERE `synonym` = '" . $this->dataLink->formatInput($synonym, BM_VT_STRING) . "';";
      $result = $this->dataLink->getValue($sql);
      return ($result != null) ? $result : 0;
    }
    
    public function isEmailCorrect($email)
    {
      $emailParts = explode('@', $email);
      
      if (count($emailParts) == 2)
      {
        $hostname = $emailParts[1];
        if (getmxrr($hostname, $mxhosts) && filter_var($email, FILTER_VALIDATE_EMAIL))
        {
          return true;
        }
      }
      
      return false;
    }    
    
    public function formatNumber($number)
    {                                                                                                   
      if ($number >= 10000) 
      {
        $number = number_format($number, 0, ',', '~');  
        $number = str_replace('~', '&thinsp;', $number);
        return $number;
      }
      else
      {
        return $number;
      }                                            
    }
	}
	
?>