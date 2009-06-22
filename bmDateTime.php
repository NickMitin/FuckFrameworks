<?php
  class bmDateTime
  {
    
    private $value;
    private $dateTime;
    
    public function __construct($time)
    {
      $this->dateTime = new DateTime($time);
    }
    
    public function __sleep()
    {
      $this->value = $this->dateTime->format('Y-m-d H:i:s');
      return array('value');
    }
    
    public function __wakeup()
    {
      $this->dateTime = new DateTime($this->value);   
    }
    
    public function __toString()
    {       
      return $this->dateTime->format('Y-m-d H:i:s');
    }
    
    public function getValue()
    {
      return $this->dateTime;
    }

  }
?>