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
	
  abstract class bmHTMLPage extends bmPage
  {
    
    public $title = '';
    public $docType = '<?xml version="1.0" encoding="utf-8" ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
';
    
    private $scripts = array();
    private $CSS = array();
    private $meta = array();
    private $RSSLinks = array();
    
    protected $content = '';
    protected $metaData = '';
    protected $clientTemplates = '';
    protected $pageTemplate = '';
    
    public function generate() {
      
      $this->metaData = implode("\n", $this->meta) . "\n" . implode("\n", $this->scripts) . "\n" . implode("\n", $this->CSS) . "\n" . implode("\n", $this->RSSLinks) . "\n";
      
      return $this->docType;
      
    }
    
    public function addHTMLMetaDatum($dataType, $source, $path)
    {
      if (!array_key_exists($source, $this->$dataType)) {
        $this->{$dataType}[$source] = $path;
      }
    }
    
    public function getClientTemplates($templateNames)
    {
      if (!is_array($templateNames))
      {
        $templateNames = array($templateNames);
      }
      
      $templates = '';
      $templateSet = $this->application->getTemplate('global/div_templateSet');
      $templateClient = $this->application->getTemplate('global/div_template');
      foreach ($templateNames as $key => $templateName)
      {
        $currentTemplate = $this->application->getClientTemplate($templateName);
        $templateName = substr($templateName, strrpos($templateName, '/') + 1);
        eval('$templates .= "' . $templateClient . '";');
      }
      eval('$this->clientTemplates = "' . $templateSet . '";');
    }

    public function addScript($source)
    {
      $this->addHTMLMetaDatum('scripts', $source, '<script type="text/javascript" src="' . $this->application->contentProvider->getStaticServer() . '/scripts/' . $source . '.js"></script>');
    }
    
    public function addScripts($scripts)
    {
      if (!is_array($scripts))
      {
        $scripts = array($scripts);
      }
      
      foreach ($scripts as $source)
      {
        $this->addScript($source);
      }
    }
    
    public function addCSS($source)
    {
      $this->addHTMLMetaDatum('CSS', $source, '<link rel="stylesheet" type="text/css" href="' . $this->application->contentProvider->getStaticServer() . '/styles/' . $source . '.css" />');
    }
    
    public function addCSSs($CSSs)
    {
      if (!is_array($CSSs))
      {
        $CSSs = array($CSSs);
      }
      
      foreach ($CSSs as $source)
      {
        $this->addCSS($source);
      }
    }
    
    public function addMeta($name, $content)
    {
      $this->addHTMLMetaDatum('meta', $name, '<meta http-equiv="' . $name . '" content="' . $content . '" />');
    }
    
    public function addMetas($meta)
    {
      foreach ($meta as $name => $content)
      {
        $this->addMeta($name, $content);
      }
    }
  
    public function addRSSLink($source, $title)
    {
      $this->addHTMLMetaDatum('RSSLinks', $source, '<link href="' . $source .'" title="' . $title . '" type="application/rss+xml" rel="alternate"/>');
    }
    
    public function addRSSLinks($links)
    {
      if (!is_array($links))
      {
        $links = array($links);
      }
      
      foreach ($links as $source => $title)
      {
        $this->addRSSLink($source, $title);
      }
    }
  }
?>