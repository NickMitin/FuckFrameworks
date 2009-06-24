<?php
/**
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
	
	define('BM_VT_ANY', 0);
	define('BM_VT_STRING', 1);
	define('BM_VT_INTEGER', 2);
	define('BM_VT_FLOAT', 3);
	define('BM_VT_DATETIME', 4);
	define('BM_VT_OBJECT', 5);
	
	abstract class bmApplication extends bmFFObject
	{
		public $templatecache = array();
		public $doctype = '<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">';
		public $action = '';
		public $dataLink = null;
		public $user = null;		
		public $session = null;
		public $debug = false;
		public $debugUserId = null;
		
		public function __construct($application, $parameters = null)
		{
			parent::__construct($application, $parameters);
			$this->action = $this->cgi->getGPC('action', '');
			
			$this->dataLink = new bmMySQLLink($this);
			register_shutdown_function(array($this, 'save'));
		}
		
		function getReferer($defaultValue)
		{
			return array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : $defaultValue; 
		}
		
		public function login($email, $password)
		{
			$result = false;
			if (($this->session->userId != C_DEFAULT_USER_ID) && ($this->session->userId != 0))
			{
				$this->user->identifier = $this->session->userId;
				$this->user->lastVisit = $this->session->createTime;
				$this->timeOnline = time() - $this->session->createTime;        
				$this->user->store();
			}
			
			$dataLink = $this->dataLink;
			$sql = "SELECT 
								`id` AS `id`
							FROM
								`user`
							WHERE
								`email` = '" . $dataLink->formatInput($email) . "' AND 
								`password` = '" . md5($dataLink->formatInput($password)) . "'
							LIMIT 1;";
			$user = $dataLink->getObject($sql);
			if ($user)
			{
				$result = true;
				$this->session->userId = $user->id;
				$this->session->createTime = time();
				$this->session->save();
				
				$this->user->identifier = $user->id;
				$this->user->load();
				$sql = "INSERT IGNORE INTO 
								`link_user_session`
								SET 
									`userId` = '" . $dataLink->formatInput($this->session->userId) . "',
									`sessionHash` = '" . $dataLink->formatInput($this->session->identifier) . "',
									`ipAddress` = '" . $dataLink->formatInput($this->session->ipAddress) . "';";
									
				$dataLink->query($sql);
			}
		 
			return $result;
		}
		
		public function logout()
		{
			$result = false;
			if ($this->session->userId != C_DEFAULT_USER_ID)
			{
				$result = true;
				
				$this->user->lastVisit = $this->session->createTime;
				$this->user->timeOnline = time() - $this->session->createTime;
				$this->user->store();
				
				$dataLink = $this->dataLink;
				$sql = "DELETE FROM 
									`link_user_session`
								WHERE 
									`userId` = '" . $dataLink->formatInput($this->session->userId) . "';"; 
				$dataLink->query($sql);
				
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
			$className = 'bm' . ucfirst($propertyName);
			if (class_exists($className))
      {
				if (!$this->propertyExists($propertyName)) {
					$this->addProperty($propertyName, new $className($this));
				}
			}
			return parent::__get($propertyName);
		}
		
		public function save()
		{
 
		}

		public function decodeUrl($string)
		{
			$encoding = mb_detect_encoding($string, 'UTF-8, CP1251');
			if ($encoding != 'UTF-8')
			{
				$string = mb_convert_encoding($string, 'UTF-8', $encoding);
			}
			return $string;
		}

		public function getTemplate($templateName, $escape = true, $read = false, $path = documentRoot)
		{
			$template = $this->debug ?  false : $this->cacheLink->get(templatePrefix . $templateName);
			
			if ($template === false || $read !== false)
			{
				$template = trim(file_get_contents($path . '/templates/' . $templateName . '.html'));

				$this->cacheLink->set(templatePrefix . $templateName, $template);
				$this->updateTemplateStack($templateName);
			}
			if ($escape)
			{
				$template = addcslashes(trim($template), '"');
			}
			return $template;
		}
		
		public function getStaticCache($cacheName, $generator, $TTL = 0)
		{
			$cachePath = contentRoot . 'cache/' . $cacheName . '.html';
			$result = false;
			if (!file_exists($cachePath) || ($TTL > 0 && filemtime($cachePath) < time() - $TTL) || $TTL < 0)
			{
				
				$result = call_user_func($generator, $cacheName);
				
				$this->uploader->saveToFile('cache', $cacheName, $result);
			}
			else
			{
				$result = file_get_contents($cachePath);
			}
			return $result;
		}
		
		public function getHTMLCache($cacheName) 
		{
			$result = $this->debug ? false : $this->cacheLink->get($cacheName);
			return $result;
		}
		
		public function setHTMLCache($cacheName, $content) 
		{
			$this->cacheLink->set($cacheName, $content, BM_CACHE_MIDDLE_TTL);
		}
		
		
		public function removeStaticCache($cacheName)
		{
			$cachePath = contentRoot . 'cache/' . $cacheName . '.html';
			if (file_exists($cachePath))
			{
				unlink($cachePath);
			}
		}
		
		public function getClientTemplate($templateName)
		{

			$this->template = false;
			if ($this->template === false)
			{
				$this->template = $this->getTemplate($templateName, false);
				$this->template = preg_replace('/[{]?\$([a-zA-Z]+)->([a-zA-Z]+)?[}]?/S', '%\\2%', $this->template);
				$this->template = preg_replace('/[{]?\$([a-zA-Z]+)[}]?/S', '%\\1%', $this->template);
				$this->template = preg_replace('/\s*?\n\s*/', '', $this->template);
			}
			return $this->template;
		}
		
		private function updateTemplateStack($templateName)
		{
			$templateStack = $this->cacheLink->get('templateStack');
			if ($templateStack === false)
			{
				$templateStack = array();
			}
			$templateStack[$templateName] = $templateName;
			$this->cacheLink->set('templateStack', $templateStack);
		}
		
		public function updateTemplates()
		{
			$templateStack = $this->cacheLink->get('templateStack');
			if ($templateStack === false)
			{
				$templateStack = array();
			}
			
			foreach ($templateStack as $key => $templateName)
			{
				$template = trim(file_get_contents(homePath . 'templates/' . $templateName . '.html'));
				$this->cacheLink->set(templatePrefix . $templateName, $template);
			}
		}
		
		public function createObject($parameters)
		{
			$result = new stdClass();
			
			foreach($parameters as $parameterName => $parameterValue)
			{
				$result->$parameterName = $parameterValue;
			}
			return $result;
		}

	}
	
?>