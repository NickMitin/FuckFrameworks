<?php
	/*
	* Copyright (c) 2009, "The Blind Mice Studio"
	* All rights reserved.
	* 
	* Redistribution and use in source and binary forms, with or without
	* modification, are permitted provided that the following conditions are met:
	* - Redistributions of source code must retain the above copyright
	*	 notice, this list of conditions and the following disclaimer.
	* - Redistributions in binary form must reproduce the above copyright
	*	 notice, this list of conditions and the following disclaimer in the
	*	 documentation and/or other materials provided with the distribution.
	* - Neither the name of the "The Blind Mice Studio" nor the
	*	 names of its contributors may be used to endorse or promote products
	*	 derived from this software without specific prior written permission.

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

	
	 define('BM_CACHE_SHORT_TTL', 600);
	 define('BM_CACHE_MIDDLE_TTL', 3600);
	 define('BM_CACHE_LONG_TTL', 86400);
	 define('BM_CACHE_LIFELONG_TTL', 0);

	 
	 /**
	 * Класс, инкапсулирующий работу с кешем
	 * В случае, если:
	 * - в системе нет поддерживаемого кешера 
	 * - приложение находится в режиме отладки (определяется через $application->debug)
	 * то все обращения к функциям класса будут завершаться неудачей.
	 */
	class bmRedis extends bmFFObject {
		
		private $cacher = null;
		protected $prefix = '';
		
		/**
		* Конструктор класса
		* 
		* @param bmApplication $application экземпляр текущего приложения
		* @param array $parameters параметры, необходимые для инициализации класса
		* @return bmCacheLink
		*/
		public function __construct($application, $parameters = array())
		{
			parent::__construct($application, $parameters);
			
			$redis = new Redis();
			$connectResult = $redis->pconnect('/tmp/px.sock');
			
			if ($connectResult)
			{
				$this->cacher = $redis;
			}
		}
		
		
		/**
		* Возвращает значение из кеша по ключу.
		* Возвращает false в одном из следующих случаев:
		* - не установлен поддерживаемый кешер
		* - приложение находится в режиме отладки
		* - данные с указанным ключом не обнаружены
		* 
		* @param mixed $key ключ
		* @return mixed значение, сохраненное в кеше или false в случае неудачи
		*/
		public function get($key)
		{
			$result = false;
			
			if (($key != null) && ($this->cacher != null))
			{
				$key = $this->prefix . $key;
				$getResult = $this->cacher->get($key);	// https://github.com/nicolasff/phpredis/#get
				if ($getResult !== false)
				{
					$result = unserialize($getResult);
				}

/*
				if (mb_strpos($key, 'post_sections_') !== false)
				{
					var_dump('------------------------------------ READING: ');
					var_dump('$key');					var_dump($key);
					var_dump('$getResult');		var_dump($getResult);
					var_dump('$result');			var_dump($result);
					
					var_dump('DEBUG_BACKTRACE: ');
					//var_dump(debug_backtrace());
					debug_print_backtrace();
				}
*/
				
			}
			
			return $result;
		}
		
		public function getKeys($keyPattern)
		{
			$result = false;
			
			if (($keyPattern != null) && ($this->cacher != null))
			{
				$keyPattern = $this->prefix . $keyPattern;
				
				$keyNames = $this->cacher->keys($keyPattern);	// https://github.com/nicolasff/phpredis/#keys-getkeys
				
				if (is_array($keyNames))
				{
					$result = array();
					$keyPrefixLength = mb_strlen($this->prefix);
					foreach ($keyNames as $keyName)
					{
						$result[] = mb_substr($keyName, $keyPrefixLength);
					}
				}
			}
			
			return $result;
		}

		/**
		* Сохраняет значение в кеш с указанным ключем и временем жизни
		* Функция завершится неудачей, если не установлен поддерживаемый кешер
		* 
		* @param mixed $key ключ
		* @param mixed $value значение
		* @param int $expire время жизни объекта в кеше в секундах. 0 для бесконечного времени жизни.
		* @return bool флаг успеха. true, если все ок. false, если не установлен поддерживаемый кешер.
		*/
		public function set($key, $value, $expire = 0)
		{
			$result = false;
			
			if (($key != null) && ($this->cacher != null))
			{
				$key = $this->prefix . $key;

				if ($expire > 0)
				{					
					$result = $this->cacher->setex($key, $expire, serialize($value));	// https://github.com/nicolasff/phpredis/#setex-psetex
				}
				else
				{
					$result = $this->cacher->set($key, serialize($value));
				}

/*				
				if (mb_strpos($key, 'post_sections_') !== false)
				{
					var_dump('------------------------------------ WRITING: ');
					var_dump('$key');							var_dump($key);
					var_dump('$value');						var_dump($value);
					var_dump('$serializedValue');	var_dump(serialize($value));
					var_dump('$result');					var_dump($result);
					
					var_dump('DEBUG_BACKTRACE: ');
					//var_dump(debug_backtrace());
					debug_print_backtrace();
				}
*/
			}
			return $result;
		}

		/**
		* Функция удаляет значение с указанным ключем из кеша
		* 
		* @param mixed $key ключ
		* @return bool результат выполнения функции
		*/
		public function delete($key)
		{
			$result = false;
			
			if (($key != null) && ($this->cacher != null))
			{
				$key = $this->prefix . $key;
				if ($this->cacher->exists($key))
				{
					$result = ($this->cacher->delete($key) > 0);	// https://github.com/nicolasff/phpredis/#del-delete
				}
			}
			
			return $result;
		}
		
		public function deleteKeys($keyNames)
		{
			$result = false;
			
			if (is_array($keyNames) && (count($keyNames) > 0) && ($this->cacher != null))
			{
				$fullKeyNames = array();
				foreach ($keyNames as $keyName)
				{
					$fullKeyNames[] = $this->prefix . $keyName;
				}
				unset($keyNames);
				
				$result = ($this->cacher->delete($fullKeyNames) == count($fullKeyNames));	// https://github.com/nicolasff/phpredis/#del-delete
			}
			
			return $result;
		}
		
		public function deleteByPrefix($key)
		{
			$result = true;
			
			if (($key != null) && ($this->cacher != null))
			{
				$allKeys = $this->cacher->keys($key . '*');
				if (count($allKeys) > 0)
				foreach ($allKeys as $cacheKey)
				{
					$this->cacher->delete($cacheKey);
				}
			}
		}
	}
?>
