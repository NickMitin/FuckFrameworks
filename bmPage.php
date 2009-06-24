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
	
  abstract class bmPage extends bmFFObject
  {

    abstract public function generate();
    
    private function executeSSI($matches) {
      return $this->application->generator->generate($matches[1]);
    }
    
    public function render($return = true) 
    {
      $content = $this->generate();
      
      $path = '';
      
      if (array_key_exists('REQUEST_URI', $_SERVER))
      {
        $path = documentRoot . $_SERVER['REQUEST_URI'];
      }
      
      
      if (!$this->application->debug)
      {
        if (mb_substr($path, -1, 1) != '/')
        {
          $path .= '/';
        }
        $path .= 'index.html';
        if ($path != '' && $path != '/') 
        {
          //$this->application->saveToFile($path, $content);
        }
      }
      
      while (preg_match('/<!--#exec\s+cmd="sh\s+.+?generator\.sh\s+([^"]+)(\s+(\d+))?"-->/', $content)) {
        $content = preg_replace_callback('/<!--#exec\s+cmd="sh\s+.+?generator\.sh\s+([^"]+)(\s+(\d+))?"-->/', array($this, 'executeSSI'), $content);
      }
      if ($return) {
        return $content;
      } else {
        print $content;
      }
    }
    
  }
?>