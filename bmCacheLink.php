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
   * Класс, инкапсулирующий работу с кешем
   * В случае, если:
   * - в системе нет поддерживаемого кешера 
   * - приложение находится в режиме отладки (определяется через $application->debug)
   * то все обращения к функциям класса будут завершаться неудачей.
   */
  class bmCacheLink extends bmFFObject {
        
    private $className = 'default';
    private $cacherObject = null;
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
      $fileName = projectRoot . '/conf/cache_' . $this->className . '.conf';
      if (!file_exists($fileName))
      {
        $fileName = projectRoot . '/conf/cache_default.conf';  
      }
      require($fileName);
      $className = $this->className;
      $this->cacherObject = new $className($this->application, array('prefix' => $this->prefix));
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
      return $this->cacherObject->get($key);
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
    public function set($key, $value, $expire = 0, $force = false)
    { 

      return $this->cacherObject->set($key, $value, $expire, $force);
    }

    /**
    * Функция удаляет значение с указанным ключем из кеша
    * 
    * @param mixed $key ключ
    * @return bool результат выполнения функции
    */
    public function delete($key)
    {
      return $this->cacherObject->delete($key);
    }
    
    public function deleteByPrefix($key)
    {
      return $this->cacherObject->deleteByPrefix($key);
    }
  }
?>
