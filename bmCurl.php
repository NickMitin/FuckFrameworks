<?php
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
    
    private function onCurlWrite($curl, $data) {
      $result = strlen($data);
      $this->buffer .= $data;
      return $result;
    }
    
    private function getHeader($key) {
      return array_key_exists($key, $_SERVER) ? $_SERVER[$key] : defined('DEFAULT_' . $key) ? constant('DEFAULT_' . $key) : '';
    }
    
    private function setHeader($key, $name, $value, $forse = true) {
      if (!array_key_exists($key, $this->headers) || $forse) {
        $this->headers[$key] = $name . ': ' . $value;
      }
    }
    
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
    
    public function get($url) {
      if (!$this->hasCurl) {
        return false;
      }
      return $this->execute($url, 'GET');
    }
    
    public function post($url, $data) {
      if (!$this->hasCurl) {
        return false;
      }
      return $this->execute($url, 'POST', $data);
    }
    
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
    
    public function __construct($application, $parameters = null) {
      
      if (!function_exists('curl_init'))
      {
        if (!($this->hasCurl = dl('curl.so'))) {
          //TODO ERROR;
        }
      }
      
    }
  }
?>
