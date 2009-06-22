<?php
  class bmDictionary extends bmFFObject
  {
    
    private $initialized = false;
    private $tableName = '';
    private $content = array();
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      if ($this->$tableName != '')
      {
        $this->initialize();
      }
    }
    
    public function initialize()
    {
      if (!$this->initialized && $this->tableName != '')
      {
        $this->content = $this->application->cacheLink->get('dictionary_' . $this->tableName);
        if ($this->content == false)
        {
          $sql = "SELECT * FROM `" . $this->tableName . "` WHERE 1;";
          $this->application->dataLink($sql);
        }
      }
    }
    
  }
?>
