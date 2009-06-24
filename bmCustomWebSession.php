<?php
  class bmCustomWebSession extends bmCustomSession
  {
		public function __construct($application, $parameters = array())
		{
      $load = array_key_exists('load', $parameters);
      $identifier = array_key_exists('identifier', $parameters) ? $parameters['identifier'] : '';
      
      if ($load && $identifier == '')
      {
				if(($identifier = $application->cgi->getGPC('sessionId', '', BM_VT_STRING))=='')
					$parameters['load'] = false;
      }      

			
			$parameters['identifier'] = $identifier;			
			
			parent::__construct($application, $parameters);
			
			$application->cgi->addCookie('sessionId', $this->identifier, false, '/', '', time() + 900);
			
		}
  }
?>
