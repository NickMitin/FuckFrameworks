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
	
   define('BM_CACHE_SHORT_TTL', 600);
   define('BM_CACHE_MIDDLE_TTL', 3600);
   define('BM_CACHE_LONG_TTL', 86400);

  class bmCacheLink extends bmFFObject {
    
    public function get($key)
    {
      $key = CACHE_PREFIX . $key;
      if ($this->application->debug)
      {
        return false;
      }
      if (xcache_isset($key))
      {
        return unserialize(xcache_get($key));
      }
      return false;
    }

    public function set($key, $value, $expire = 0)
    { 
      $key = CACHE_PREFIX . $key;
      $result = true;
      if (!xcache_isset('lock_' . $key))
      {
        xcache_set('lock_' . $key, true);
        if (is_object($value) && get_class($value) != 'stdClass')
        {
          print "\n Вероятно ты пытаешься положить в кэш какой-то объект, который наследуется от bmFFObject:\n";
          print "-> $key <-\n";
          var_dump($test);
          print "Этого делать нельзя, так как чтение такого кеша приведет к внутренней ошибке PHP и 500 ошибке сервера.\n";
          print "Поэтому я (скрипт) вынужден завершиться на 36 строке файла /lib/bmCacheLink.php\n";
          //НЕ УДАЛЯТЬ, ПО ЭТОМУ ВОПРОСУ К КОЛЕ.
          exit;
        }
        else if (is_array($value))
        {
          $test = current($value);
          if (is_object($test) && get_class($test) != 'stdClass')
          {
            print "\n Вероятно ты пытаешься положить в кэш массив каких-то объектов, которые наследуются от bmFFObject:\n";
            print "-> $key <-\n";
            var_dump($test);
            print "Этого делать нельзя, так как чтение такого кеша приведет к внутренней ошибке PHP и 500 ошибке сервера.\n";
            print "Поэтому я (скрипт) вынужден завершиться на 46 строке файла /lib/bmCacheLink.php\n";
            //НЕ УДАЛЯТЬ, ПО ЭТОМУ ВОПРОСУ К КОЛЕ.
            exit;
          }
        }
        $result = xcache_set($key, serialize($value), $expire);
        xcache_unset('lock_' . $key);
      }
      return $result;
    }

    public function delete($key)
    {
      $key = CACHE_PREFIX . $key;
      $result = false; 
      if (xcache_isset($key))
      {
        $result = xcache_unset($key);
      }
      return $result;
    }
    
  }
?>
