<?php
	/**
	* Класс, реализующий сессию с чтением/сохранением идентификатора из cookies
	*/
  class bmCustomWebSession extends bmCustomSession
  {
  	/**
  	* Конструктор класса. Для загрузки сессии требуется в $parameters передать параметр 'load'=true
  	* 
  	* @param bmApplication $application экземпляр текущего приложения
  	* @param array $parameters параметры, используемые для инициализации сессии (для загрузки сесии необходимо передать 'load'=>true)
  	* @return bmCustomWebSession
  	*/
		public function __construct($application, $parameters = array())
		{
      $load = array_key_exists('load', $parameters);
      $identifier = array_key_exists('identifier', $parameters) ? $parameters['identifier'] : '';
      
      if ($identifier == '' && (($identifier = $application->cgi->getGPC(C_SESSION_COOKIE_NAME, '', BM_VT_STRING)) == ''))
			{
				$parameters['load'] = false;
			}
			
			
			
			$parameters['identifier'] = $identifier;			
		
			parent::__construct($application, $parameters);
			
			$application->cgi->addCookie(C_SESSION_COOKIE_NAME, $this->identifier, false, '/', '', time() + 900);
		}
  }
?>
