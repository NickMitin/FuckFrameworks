<?php
  class bmCustomWebSession extends bmCustomSession
  {
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
