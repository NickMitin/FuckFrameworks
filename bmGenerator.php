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

  final class bmGenerator extends bmFFObject 
  {
    
    private $routes = array();
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      $this->routes = $application->cacheLink->get(C_CACHE_PREFIX . 'sitePages');
      if ($this->routes == false)
      {
        require_once(projectRoot . '/conf/generator.conf');
        $application->cacheLink->set(C_CACHE_PREFIX . 'sitePages', $this->routes);
      }
    }
    
    public function generate($path)
    {
      $result = '';
      $status = 200;
      $routes = $this->routes;
      foreach ($routes as $route => $routeData)
      {
        if (preg_match($route, $path, $matches))
        {
          
          require_once(documentRoot . $routeData['route']);
          $parameters = array();
          if (count($matches) > 1)
          {
            $i = 1;
            foreach ($routeData['parameters'] as $name => $type)
            {
              $parameters[$name] = $this->application->validateValue($matches[$i], $type);
              $i++;
            }
          }
          $entity = new $routeData['class']($this->application, $parameters);
          if ($entity instanceof bmCustomRemoteProcedure)
          {
            $result = $entity->execute();
            if ($result == '')
            {
              $result = 'OK';
            }
          }
          else
          {
            $result = $entity->generate();
          }
          break;
        }
      }
      if ($result == '')
      {
        #HTTP/1.1 200 OK
        header('HTTP/1.1 404 Not Found', true, 404);
        $result = '<h1>404</h1>';
        $status = 404;
      }
    
      return $result;
    }
    
  }
  
?>
