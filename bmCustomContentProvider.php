<?php
  
  abstract class bmCustomContentProvider extends bmFFObject
  {
    protected $staticServers = null;
    protected $contentServers = null;
    protected $documentRoot = null;
    protected $contentRoot = null;
    protected $templatePrefixes = null;

    public function __construct($application, &$parameters = array())
    {
      parent::__construct($application, $parameters);
      require_once(projectRoot . 'conf/contentProvider.conf');
    }
    
    public function compileCSS($tudy = false)
    {
      require_once(serverRoot . 'conf/css.conf');
      
      $result = '';
      foreach ($toCompile as $css)
      {
        $result .= file_get_contents(documentRoot . 'css/' . $css . '.css') . "\n";
      }
      
      if ($tudy)
      {
        $result = $this->tudyCSS($result);
      }
      
      file_put_contents(documentRoot . 'css/global.css', $result);
    }
    
    public function getContentServer()
    {
      return $this->contentServers[0];
    }
    
    public function getStaticServer()
    {
      return $this->staticServers[0];
    }
        
    public function getDocumentRoot()
    {
      return $this->documentRoot[0];
    } 
       
    public function getContentRoot()
    {
      return $this->contentRoot[0];
    }
  }
  
?>
