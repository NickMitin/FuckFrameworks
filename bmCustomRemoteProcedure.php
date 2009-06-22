<?php
  abstract class bmCustomRemoteProcedure extends bmFFObject
  {
    
    protected $returnTo = '';
    
    public function __construct($application, $parameters = null)
    {
      parent::__construct($application, $parameters);
      if ($parameters != null)
      {
        $this->returnTo = array_key_exists('returnTo', $parameters) ? $parameters['returnTo'] : $this->application->getReferer('returnTo', '');
      }
      else
      {
        $this->returnTo = $this->application->cgi->getReferer('returnTo', '');
      }
    }
    
    public function execute() 
    {
      header('location: ' . $this->returnTo, true);
    }   
  }
?>
