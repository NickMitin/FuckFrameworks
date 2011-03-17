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
  
  define('BM_RP_TYPE_CUSTOM', 1);
  define('BM_RP_TYPE_JSON', 2);
  define('BM_RP_TYPE_JSONP', 3);
  define('BM_RP_TYPE_XML', 4);
  define('BM_RP_TYPE_RAW', 5);

  
  abstract class bmCustomRemoteProcedure extends bmFFObject
  {
    
    protected $returnTo = '';
    protected $output = '';
    protected $type = BM_RP_TYPE_CUSTOM;
    public $forceEmpty = false;
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      $this->returnTo = array_key_exists('returnTo', $parameters) ? $parameters['returnTo'] : $this->application->cgi->getReferer('returnTo', '');

    }
    
    public function execute() 
    {
      if ($this->returnTo != '' && $this->type == BM_RP_TYPE_CUSTOM)
      {
        header('location: ' . $this->returnTo, true);
      }
      else
      {
        switch ($this->type)
        {
          case BM_RP_TYPE_JSON:
            return json_encode($this->output);
          break;
          case BM_RP_TYPE_RAW:
            return $this->output;
          break;
        } 
      }
    }   
  }
?>