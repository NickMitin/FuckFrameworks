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
  * - Neither the name of the <organization> nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY tbms.ru ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL <copyright holder> BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	* 
	*/
	
  class bmCGI extends bmFFObject {
    
    public function addCookie($name, $value = '', $persistent = true, $path = '/', $domain = '', $expire = 0) {
      if ($expire == 0)
      {
        if ($persistent) {
          $expire = time() + 31536000;
        } else {
          $expire = 0;
        }
      }
      
      setcookie($name, $value, $expire, $path, $domain);
      $_COOKIE[$name] = $value;
    }
    
    public function deleteCookie($name, $domain = '', $path = '/') {
      setcookie($name, '', -100000, $path, $domain);
      if (array_key_exists($name, $_COOKIE))
      {
        unset($_COOKIE[$name]);
      }
    }

    public function getCookie($name, $defaultValue = '') {
      $value = array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : $defaultValue; 
    }
    
    public function getGPC($name, $defaultValue, $type = BM_VT_ANY) {
      $value = array_key_exists($name, $_GET) ? $_GET[$name] : (array_key_exists($name, $_POST) ? $_POST[$name] : (array_key_exists($name, $_COOKIE) ? $_COOKIE[$name] : $defaultValue)); 
      switch ($type) {
        case BM_VT_INTEGER:
          $value = intval($value);
        break;
        
        case BM_VT_FLOAT:
          $value = floatval($value);
        break;
        case BM_VT_STRING:
          $value = trim($value);
        break;
      }
      return $value;
    }

    public function getReferer($defaultValue = '') {
      return array_key_exists('HTTP_REFERER', $_SERVER) ? $_SERVER['HTTP_REFERER'] : $defaultValue; 
    }
    
    public function getIPAddress() {
      $result = false;
      if (array_key_exists('HTTP_X_FORWARDED_FOR', $_SERVER)) {
        $result = trim($_SERVER['HTTP_X_FORWARDED_FOR']);
      }
      if ($result == '' && array_key_exists('REMOTE_ADDR', $_SERVER)) {
        $result = $_SERVER['REMOTE_ADDR'];  
      }
      return $result;
    }
    
    public function getBrowser() {
      
      $result->name = 'none';
      $result->engine = 'none';
      $result->status = 'none'; 
      $result->url = 'none';
      
      if (array_key_exists('HTTP_USER_AGENT', $_SERVER)) {
        $userAgent = $_SERVER['HTTP_USER_AGENT'];
      } else {
        return $result;
      }
  
      $flag = false;
      
      if (strpos($userAgent, 'MSIE') !== false) {
        $flag = 'Internet Explorer';
        $result->engine = $flag;
      }
      
      if (strpos($userAgent, 'Konqueror') !== false) {
        $flag = 'Konqueror';
        $result->engine = 'KHTML';
      }
      
      if (strpos($userAgent, 'Firefox') !== false) {
        $flag = 'Firefox';
        $result->engine = 'Gecko';
      }
      
      if (strpos($userAgent, 'Opera') !== false) {
        $flag = 'Opera';
        $result->engine = $flag;
      }
      
      if (strpos($userAgent, 'Safari') !== false) {
        $flag = 'Safari';
        $result->engine = 'WebKit';
      }
      
      if (strpos($userAgent, 'SeaMonkey') !== false) {
        $flag = 'SeaMonkey';
        $result->engine = 'Gecko';
      }

      if (strpos($userAgent, 'SeaMonkey') !== false) {
        $flag = 'Chrome';
        $result->engine = 'WebKit';
      }
      
      $result->name = $flag; 
      $result->status = 'uptodate';
                 
      if ($flag) {
        
        switch ($flag) {
          case 'Internet Explorer':
            if (strpos($userAgent, 'MSIE 7') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.microsoft.com/windows/downloads/ie/getitnow.mspx';
            }
          break;
          case 'Konqueror':
            if (strpos($userAgent, 'Konqueror/3') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.konqueror.org/download/';
            }
          break;
          case 'Firefox':
            if (strpos($userAgent, 'Firefox/2') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.mozilla.com/';
            }
          break;
          case 'Opera':
            if (strpos($userAgent, 'Opera/9') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.opera.com/download/';
            }
          break;
          case 'Safari':
            if (strpos($userAgent, 'Safari/5') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.apple.com/macosx/features/safari/';
            }
          break;
          case 'SeaMonkey':
            if (strpos($userAgent, 'SeaMonkey/1') === false) {
              $result->status = 'outdated';
              $result->url = 'http://www.mozilla.ru/products/seamonkey/';
            }
          break;
        }
        
      }
      
      return $result;
      
    }
    
  }
  
?>
