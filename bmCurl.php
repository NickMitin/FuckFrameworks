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

	
  define('CURL_EMULATE_NONE', 0); 
  define('CURL_EMULATE_FIREFOX', 1);
  
  define('CURL_RETURN', 0);
  define('CURL_PRINT', 1);
  define('CURL_FILE', 2);
  
  define('DEFAULT_HTTP_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
  define('DEFAULT_HTTP_ACCEPT', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
  define('DEFAULT_HTTP_ACCEPT_LANGUAGE', 'ru,en-us;q=0.7,en;q=0.3');
  //define('DEFAULT_HTTP_ACCEPT_ENCODING', 'gzip,deflate');
  define('DEFAULT_HTTP_ACCEPT_ENCODING', '');
  define('DEFAULT_HTTP_ACCEPT_CHARSET', 'windows-1251,utf-8;q=0.7,*;q=0.7');
  
  define('FIREFOX_HTTP_USER_AGENT', 'Mozilla/5.0 (Windows; U; Windows NT 5.1; ru; rv:1.9.0.3) Gecko/2008092417 Firefox/3.0.3');
  define('FIREFOX_HTTP_ACCEPT', 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8');
  define('FIREFOX_HTTP_ACCEPT_LANGUAGE', 'ru,en-us;q=0.7,en;q=0.3');
  define('FIREFOX_HTTP_ACCEPT_ENCODING', 'gzip,deflate');
  define('FIREFOX_HTTP_ACCEPT_CHARSET', 'windows-1251,utf-8;q=0.7,*;q=0.7');

  /**
  * Класс, инкапсулирующий работу с библиотекой cURL
  * Требует для своей работы соответствующее расширение php
  */
  class bmCurl extends bmFFObject {
    
    private $headers = array();
    private $hasCurl = true;
    private $curl = null;
    private $fileName = '';
    private $history = array();
    private $info = null;
    
    private $buffer = '';
    
    public $debug = false;
    public $emulate = CURL_EMULATE_NONE;
    public $keepAlive = 0;
    public $acceptCookies = true;
    public $outputMethod = CURL_RETURN;
    
    /**
    * Функция обратного вызова, использующаяся для приема данных
    * 
    * @param resource $curl идентификатор используемой сессии
    * @param mixed $data данные
    * @return int длина записанных данных в байтах
    */
    private function onCurlWrite($curl, $data) {
      $result = strlen($data);
      $this->buffer .= $data;
      return $result;
    }
    
    /**
    * Возвращает значение заголовка
    *
    * @todo ревью документации
    */
    private function getHeader($key) {
      return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : defined('DEFAULT_' . $key) ? constant('DEFAULT_' . $key) : '';
    }
    
    /**
    * Устанавливает значение заголовка
    *
    * @param string $key ключ (внутреннее имя заголовка)
    * @param string $name имя заголовка
    * @param string $value значение заголовка
    * @param bool $force необходимо ли переустановить значение, если оно уже сохранено
    * @todo ревью документации
    */
    private function setHeader($key, $name, $value, $forсe = true) {
      if (!array_key_exists($key, $this->headers) || $forсe) {
        $this->headers[$key] = $name . ': ' . $value;
      }
    }
    
    /**
    * Выполняет запрос по указанному URL
    * Функция может выполнить как GET, так и POST запросы. 
    * В случае, если выполняется POST запрос, в качестве данных запроса используется параметр $data.
    * В случае, если свойства $this->emulate установлена в CURL_EMULATE_FIREFOX, то функция передает соответствующие заголовки.
    * 
    * @param string $url адрес, по которому необходимо выполнить запрос
    * @param string $method етод запроса, может быть: "GET", "POST"
    * @param array|string $data данные, передаваемые в теле POST запросе
    * @return string результат выполнения запроса
    */
    public function execute($url, $method = 'GET', $data = null) {
      if (!$this->hasCurl) {
        return false;
      }
      $this->curl = curl_init();  
      
      switch ($method) {
        case 'GET':
          curl_setopt($this->curl, CURLOPT_HTTPGET, true);
        break;
        case 'POST':
          curl_setopt($this->curl, CURLOPT_POST, true);
          curl_setopt($this->curl, CURLOPT_POSTFIELDS, $data);
        break;
      }
      
      switch ($this->emulate) {
        case CURL_EMULATE_FIREFOX:
          $this->setHeader('HTTP_USER_AGENT', 'User-Agent', FIREFOX_HTTP_USER_AGENT);
          $this->setHeader('HTTP_ACCEPT', 'Accept', FIREFOX_HTTP_ACCEPT);
          $this->setHeader('HTTP_ACCEPT_LANGUAGE', 'Accept-Language', FIREFOX_HTTP_ACCEPT_LANGUAGE);
          $this->setHeader('HTTP_ACCEPT_ENCODING', 'Accept-Encoding', FIREFOX_HTTP_ACCEPT_ENCODING);
          $this->setHeader('HTTP_ACCEPT_CHARSET', 'Accept-Charset', FIREFOX_HTTP_ACCEPT_CHARSET);
        default:
          $this->setHeader('HTTP_USER_AGENT' , 'User-Agent', $this->getHeader('HTTP_USER_AGENT'));
          $this->setHeader('HTTP_ACCEPT', 'Accept', $this->getHeader('HTTP_ACCEPT'));
          $this->setHeader('HTTP_ACCEPT_LANGUAGE', 'Accept-Language', $this->getHeader('HTTP_ACCEPT_LANGUAGE'));
          $this->setHeader('HTTP_ACCEPT_ENCODING', 'Accept-Encoding', $this->getHeader('HTTP_ACCEPT_ENCODING'));
          $this->setHeader('HTTP_ACCEPT_CHARSET', 'Accept-Charset', $this->getHeader('HTTP_ACCEPT_CHARSET'));
        break;
      }
      if ($this->keepAlive > 0) {
        $this->setHeader('KEEP_ALIVE', 'Keep-Alive', $this->keepAlive);
        $this->setHeader('KEEP_ALIVE', 'Connection', 'keep-alive');
      } else {
        $this->setHeader('KEEP_ALIVE', 'Connection', 'close');
      }
      if ($this->acceptCookies) {
        curl_setopt($this->curl, CURLOPT_COOKIEFILE, '/tmp/curl_cookiefile');  
        curl_setopt($this->curl, CURLOPT_COOKIEJAR, '/tmp/curl_cookiefile');  
      }
      curl_setopt($this->curl, CURLOPT_URL, $url);
      curl_setopt($this->curl, CURLOPT_HTTPHEADER, $this->headers);
      curl_setopt($this->curl, CURLOPT_FOLLOWLOCATION, true);
      curl_setopt($this->curl, CURLOPT_AUTOREFERER, true);
      if (count($this->history) > 0) {
        curl_setopt($this->curl, CURLOPT_REFERER, end($history));
      }
      
      switch ($this->outputMethod) {
        case CURL_RETURN:
          curl_setopt($this->curl, CURLOPT_WRITEFUNCTION, array($this, 'onCurlWrite'));
          $this->buffer = ''; 
        break;
        case CURL_FILE:
          $this->buffer = fopen($this->fileName, 'w');
          curl_setopt($this->curl, CURLOPT_FILE, $this->buffer);
        break;
      }
      
      if ($this->debug) {
        curl_setopt($curl, CURLOPT_VERBOSE, 1);
        //curl_setopt($curl, CURLOPT_WRITEHEADER, $headFile);
        //curl_setopt($curl, CURLOPT_STDERR, $errFile);
      }
      curl_exec($this->curl);
      $this->info = curl_getinfo($this->curl);
      $error = curl_errno($this->curl);
   
      switch ($this->outputMethod) {
        case CURL_FILE:
          fclose($this->buffer);
          $this->buffer = $this->fileName;
        break;
      }
      curl_close($this->curl);
      $this->curl = null;   
      return $this->buffer;
    }
    
    /**
    * Псевдоним для функции {@link execute()}, выполняющий GET запрос
    *
    * @param string $url адрес, по которому выполняется запрос
    * @return string|bool результат выполнения запроса или false в случае неудачи
    */
    public function get($url) {
      if (!$this->hasCurl) {
        return false;
      }
      return $this->execute($url, 'GET');
    }
    
    /**
    * Псевдоним для функции {@link execute()}, выполняющий POST запрос
    *
    * @param string $url адрес, по которому выполняется запрос
    * @param arrray|string $data данные, передаваемые в теле POST запроса
    * @return string|bool результат выполнения запроса или false в случае неудачи
    */
    public function post($url, $data) {
      if (!$this->hasCurl) {
        return false;
      }
      return $this->execute($url, 'POST', $data);
    }
    
    /**
    * Выполняет проверку существования указанного адреса
    *
    * @param string $url адрес, существование которого проверяется
    * @return bool результат выполнения функции
    */
    public function fileExists($url) {
      if (!$this->hasCurl) {
        return false;
      }
      $this->execute($url, 'GET');
      $result = true;
      if (array_key_exists('http_code', $this->info)) {
        $result = $this->info['http_code'] != 404;
      }
      return $result;
    }
    
    /**
    * Производит загрузку с указанного адреса в файл
    *
    * @param string $url адрес с которого выполнется загрузка
    * @param string $fileName имя файла в локальной файловой системе, куда выполняется загрузка
    * @return string|bool результат выполнения запроса или false в случае неудачи
    */
    public function getFile($url, $fileName) {
      if (!$this->hasCurl) {
        return false;
      }
      $saveOutputMethod = $this->outputMethod;
      $this->outputMethod = CURL_FILE;
      $this->fileName = $fileName;
      $result = $this->execute($url, 'GET');
      $this->outputMethod = $saveOutputMethod;
      return $result;
      
    }
    
    /**
    * Конструктор класса. Выполняет проверку на поддержку библиотеки cURL
    *
    * @param bmApplication $application экземпляр текущего приложения
    * @param array $parameters параметры, необходимые для инициализации экземпляра класса
    */
    public function __construct($application, $parameters = array()) {
      
      if (!function_exists('curl_init'))
      {
        if (!($this->hasCurl = dl('curl.so'))) {
          //TODO ERROR;
        }
      }
      
    }
  }
?>
