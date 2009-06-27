<?php

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
