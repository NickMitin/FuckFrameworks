<?php
/**
	* Copyright (c) 2009, tbms.ru
	* All rights reserved.
	* 
	* Redistribution and use in source and binary forms, with or without
	* modification, are permitted provided that the following conditions are met:
	* - Redistributions of source code must retain the above copyright
	*   notice, this list of conditions and the following disclaimer.
  * - Redistributions in binary form must reproduce the above copyright
  *   notice, this list of conditions and the following disclaimer in the
  *   documentation and/or other materials provided with the distribution.
  * - Neither the name of the tbms.ru nor the
  *   names of its contributors may be used to endorse or promote products
  *   derived from this software without specific prior written permission.

  * THIS SOFTWARE IS PROVIDED BY tbms.ru ''AS IS'' AND ANY
  * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
  * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
  * DISCLAIMED. IN NO EVENT SHALL tbms.ru BE LIABLE FOR ANY
  * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
  * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
  * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER CAUSED AND
  * ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT LIABILITY, OR TORT
  * (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY OUT OF THE USE OF THIS
  * SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH DAMAGE.
	* 
	*/
	
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
