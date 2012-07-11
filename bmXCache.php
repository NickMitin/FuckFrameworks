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
  class bmXCache extends bmFFObject {
    
    private $cacherExists = false;
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
      $this->cacherExists = function_exists('xcache_isset');  
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
      if ($key != null)
      {
        $key = $this->prefix . $key;
        if (!$this->cacherExists)
        {
          return false;
        }
        if (xcache_isset($key))
        {
          return unserialize(xcache_get($key));
        }
        return false;
      }
      else
      {
        return false;
      }
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
      if ($key != null)
      {
        if ($this->cacherExists)
        {
          $key = $this->prefix . $key;
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
        else
        {
          return false;
        }
      }
      else
      {
        return false;
      }
    }

    /**
    * Функция удаляет значение с указанным ключем из кеша
    * 
    * @param mixed $key ключ
    * @return bool результат выполнения функции
    */
    public function delete($key)
    {
      if ($key != null)
      {
        if ($this->cacherExists)
        {
          $key = $this->prefix . $key;
          $result = false; 
          if (xcache_isset($key))
          {
            $result = xcache_unset($key);
          }
          return $result;
        }
        else
        {
          return false;
        }
      }
      else
      {
        return false;
      }
    }
    
    public function deleteByPrefix($key)
    {
      if ($key != null)
      {
        $result = xcache_unset_by_prefix($key);
      }
      else
      {
        return false;
      }
    }
  }
  

  
?>
