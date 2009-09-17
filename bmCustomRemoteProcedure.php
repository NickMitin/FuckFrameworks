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
  * Класс, являющийся базовым для удаленных процедур
  */
  abstract class bmCustomRemoteProcedure extends bmFFObject
  {
    /**
    * Закрытое поле, определяющее куда будет направлен пользователь после выполнения процедуры
    * 
    * @var string
    */
    protected $returnTo = '';
    
    /**
    * Конструктор класса
    * Заполняет поле returnTo значением, переданным в $parameters или же параметром HTTP
    * 
    * @param bmApplication $application экземпляр текущего выполняющегося приложения
    * @param array $parameters массив параметров
    * @return bmCustomRemoteProcedure
    */
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      $this->returnTo = array_key_exists('returnTo', $parameters) ? $parameters['returnTo'] : $this->application->cgi->getReferer('returnTo', '');

    }
    
    /**
    * Переопределяемый в наследниках метод.
    * Наследник реализует в этом методе логику удаленной процедуры.
    * В текущей реализации выполняет перевод пользователя на адрес, указанный в returnTo
    * Наследующий метод должен в конце своей работы вызвать этот метод. 
    */
    public function execute() 
    {
      header('location: ' . $this->returnTo, true);
    }   
  }
?>
