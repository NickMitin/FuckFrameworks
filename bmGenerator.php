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
    
    private function ffTypeToString($type)
    {
      $result = 'BM_VT_ERROR';
      switch ($type)
      {
        case BM_VT_INTEGER: 
          $result = 'BM_VT_INTEGER';
        break;
        case BM_VT_FLOAT: 
          $result = 'BM_VT_FLOAT';
        break;
        case BM_VT_DATETIME: 
          $result = 'BM_VT_DATETIME';
        break;
        case BM_VT_STRING: 
          $result = 'BM_VT_STRING';
        break;
        case BM_VT_ANY: 
          $result = 'BM_VT_ANY';
        break;
      }
      return $result;
    }
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      $this->routes = $application->cacheLink->get('router');
      
      if ($this->routes == false)
      {
        require_once(projectRoot . '/conf/generator.conf');
        $application->cacheLink->set('router', $this->routes);
      }
    }
    
    public function addRoute($uri, $handlerFile, $handlerClassName, $parameters = array())
    {
      $route = array();
      $route['route'] = $handlerFile;
      $route['class'] = $handlerClassName;
      if (count($parameters) > 0)
      {
        $route['parameters'] = $parameters;
      }
      $this->routes[$uri] = $route;
    }
    
    public function setRoute($uri, $oldURI, $handlerFile, $handlerClassName)
    {
      if (array_key_exists($uri, $this->routes))
      {
        $parameters = array(); 
        if ($uri == $oldURI)
        {
          $this->routes[$uri]['route'] = $handlerFile;
          $this->routes[$uri]['class'] = $handlerClassName;
          return true;
        }
        else
        {
          if (array_key_exists('parameters', $this->routes[$uri]))
          {
            $parameters = $this->routes[$uri]['parameters'];
          }
          unset($this->routes[$uri]);
        }
      }
      $route = array();
      $route['route'] = $handlerFile;
      $route['class'] = $handlerClassName;
      $route['parameters'] = $parameters;
      $this->routes[$uri] = $route;
    }
    
    public function deleteRoute($uri)
    {
      if (array_key_exists($uri, $this->routes))
      {
        unset($this->routes[$uri]);
      }
      return false;
    }
    
    public function setRouteParameter($uri, $name, $type)
    {
      if (array_key_exists($uri, $this->routes))
      {
        if (array_key_exists('parameters', $this->routes[$uri]))
        {
          $this->routes[$uri]['parameters'][$name] = $type;
          return true;
        }
      }
      return false;
    }
    
    public function deleteRouteParameter($uri, $name)
    {
      if (array_key_exists($uri, $this->routes))
      {
        if (array_key_exists('parameters', $this->routes[$uri]))
        {
          if (array_key_exists($name, $this->routes[$uri]['parameters']))
          {
            unset($this->routes[$uri]['parameters'][$name]);
            return true;
          }
        }
      }
      return false;
    }
    
    public function serialize()
    {
      $routes = array();  
      foreach ($this->routes as $uri => $route)
      {
        $routeString = "  '" . $uri . "' => array\n  (\n    'route' => '" . $route['route'] . "',\n    'class' => '" . $route['class'] . "'";
        if (array_key_exists('parameters', $route))
        {
          $parameters = array();
          foreach ($route['parameters'] as $name => $type)
          {
            $parameters[] = "      '" . $name . "' => " . $this->ffTypeToString($type);
          }
          $routeString .= ",\n    'parameters' => array\n    (\n" . implode(",\n", $parameters) . "\n    )";
          
        }
        $routeString .= "\n  )";
        $routes[] = $routeString;
      }
      $routes = "<?php\n\n\$this->routes = array\n(\n" . implode(",\n", $routes) . "\n);";
      file_put_contents(projectRoot . '/conf/generator.conf', $routes);
      $application->cacheLink->set('router', $this->routes);
    }
    
    public function generate($path)
    {
      $pathParts = explode('?', $path);
      
      if (count($pathParts) > 1)
      {
        $path = $pathParts[0];
        if (trim($pathParts[1]) != '')
        {
          $getParameters = explode('&', $pathParts[1]);
          foreach ($getParameters as $getParameter)
          {
            if (mb_strpos($getParameter, '=') !== false)
            {
              list($key, $value) = explode('=', $getParameter);
              $_GET[urldecode($key)] = $value;
            }
          }
        }
      }

      $result = '';
      $status = 200;
      $routes = $this->routes;
      $forceEmpty = false;
      foreach ($routes as $route => $routeData)
      {
        if (preg_match($route, $path, $matches))
        {          
          require_once(documentRoot . $routeData['route']);
          $parameters = array();
          $matchCount = count($matches) - 1;
          if (array_key_exists('parameters', $routeData))
          {
            if ($matchCount > 0)
            {
              $routeParameters = array_slice($routeData['parameters'], 0, $matchCount);

              $i = 1;
              foreach ($routeParameters as $name => $type)
              {
                $parameters[$name] = $this->application->validateValue($matches[$i], $type);              
                $i++;
              }
            }
            if(count($routeParameters = array_slice($routeData['parameters'], $matchCount)) > 0)
            {
              foreach ($routeParameters as $name => $type)
              {
                $parameters[$name] = $this->application->cgi->getGPC($name, '', $type);  
              }
            }
            
          }

          $entity = new $routeData['class']($this->application, $parameters);
          if ($entity instanceof bmCustomRemoteProcedure)
          {
            $result = $entity->execute();
            $forceEmpty = $entity->forceEmpty;
            if ($result == '' && !$forceEmpty)
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

      if ($result == '' && !$forceEmpty)
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