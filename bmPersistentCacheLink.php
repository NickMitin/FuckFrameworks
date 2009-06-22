<?php
  class bmPersistentCacheLink extends bmFFObject
  {
    private $cacher = null;
    
    public function __construct($application, $parameters = array())
    {
      $this->cacher = new Memcache();
      $this->cacher->connect('localhost', 21201);
      parent::__construct($application, $parameters);
    }
    
    public function __destruct()
    {
      $this->cacher->close();
      $this->cacher = null;
    }
    
    public function get($key)
    {
      return $this->cacher->get($key);
    }

    public function set($key, $value, $expire = 0)
    {
      $this->cacher->set($key, $value, 0, 0);
    }

    public function delete($key)
    {
      $this->cacher->delete($key);
    }
    
  }
?>
