<?php
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