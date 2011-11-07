<?php

class bmTextProcessor extends bmCustomTextProcessor 
  {
    
    private $allowedTags = array(); //array('html', 'body', 'div', 'a', 'b', 'strong', 'i', 'em', 'img', 'ul', 'ol', 'li', 'sub', 'sup', 'object', 'param', 'embed', 'br');
    private $allowedAttributes = array();// array('*'=>array('style', 'class'), 'img' => array('src', 'class', 'width', 'height'), 'a' => array('href'), 'object' => array('classid', 'width', 'height'), 'param' => array('name', 'value'), 'embed' => array('src', 'quality', 'allowfullscreen', 'wmode', 'width', 'height', 'type'));

    private $currentDotPosition = 0;
    private $position = 0;
    private $length = 0;    
    private $breakSymbol = array('.', '?', '!', ';');
    private $clearTextNode = false;       
    
    public function process($text, $allowedTags = null, $allowedAttributes = null)
    {
      if (is_null($allowedTags))
      {
        $allowedTags = array('a', 'img', 'b', 'strong', 'i', 'em', 'ul', 'ol', 'li', 's', 'strike', 'sub', 'sup', 'object', 'param', 'embed', 'br');
      }

      if (is_null($allowedAttributes))
      {
        $allowedAttributes = array('*'=>array('style', 'class'), 'img' => array('src', 'class', 'width', 'height'), 'a' => array('href'), 'object' => array('classid', 'width', 'height'), 'param' => array('name', 'value'), 'embed' => array('src', 'quality', 'allowfullscreen', 'wmode', 'width', 'height', 'type', 'flashvars'));
      }

      if (!array_key_exists('*', $allowedAttributes)) {
          $allowedAttributes['*']=array();
      }
      
      $this->allowedTags = $allowedTags;
      $this->allowedAttributes = $allowedAttributes;
      
      if (trim($text) != '')
      {        
        $text = preg_replace('/\r\n/', "\n", $text);
      
        $document = new DOMDocument('1.0', 'utf-8');
        
        $text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body><div>' . $text . '</div></body></html>';

        @$document->loadHTML($text);
        
        
        $this->cleanNode2($document->documentElement->childNodes->item(1)->childNodes->item(0));
        
        

        $text = $document->saveXML();
        
        $text = $this->processYouTubeLink($text);
        
        $text = preg_replace('/(?<!=")(https?|ftp):\/\/([\w\-]+(\.[\w\-]+)*(\.[a-z]{2,4})?)(\d{1,5})?(\/([^<>\s]*))?/', '<a href="\0">\0</a>', $text);
        $matches = null;
        preg_match('/<html><head><meta http-equiv="Content-Type" content="text\/html; charset=utf-8"\/><\/head><body><div>(.*)<\/div><\/body><\/html>/s', $text, $matches);
        if ($matches)
        {
          $text = $matches[1];
        }
      }

      $this->allowedTags = null;
      $this->allowedAttributes = null;
      
      
      
      return $text;
    }

    private function cleanNode2(DOMElement $node)
    {
      // проверка допустимых свойств
      $j = 0;
      $checkAttribute = array_key_exists($node->nodeName, $this->allowedAttributes);
      while ($j < $node->attributes->length)
      {
        $attribute = $node->attributes->item($j);
          if ( ((!$checkAttribute) || (!in_array($attribute->name, $this->allowedAttributes[$node->nodeName]))) && !in_array($attribute->name, $this->allowedAttributes['*']) )
        {
          $node->removeAttribute($attribute->name);
          $j--;            
        }

        if ( ($attribute->name == 'href') || ($attribute->name == 'src') )
        {
          // что-то мне регуляярка эта не нравится 
          if (preg_match('/^\s*javascript\s*:/', $attribute->value))
          {            
            $node->removeAttribute($attribute->name);
            $j--;
          }
        }
        $j++;
      }
      
      // проверка допустимых нод
      $i = 0;
      while ($i < $node->childNodes->length)
      {        
        $child = $node->childNodes->item($i);
        
        if ($child instanceof DOMElement)
        {
          if (in_array($child->nodeName, $this->allowedTags))
          {
            $this->cleanNode2($child);
          }
          else
          {
            var_dump($child->nodeName);
            //копируем содержимое запрещённой ноды
            foreach ($child->childNodes as $childChild)
            {
              $node->insertBefore($childChild->cloneNode(true), $child);
            }
            // и удаляем её
            $node->removeChild($child);
            $i--;
            $i--;
          }
        }
        elseif ($child instanceof DOMText) 
        {
          // проверяем текстовые ноды на наличие '\n', заменяем '\n' на '<br>'
          $text = $child->nodeValue;
          $pieces = preg_split('/\n/', $text);
          if (count($pieces) > 1)
          {
            for ($j = 0; $j < count($pieces); $j++)
            {
              $pieceTextNode = $node->ownerDocument->createTextNode($pieces[$j]);
              $node->insertBefore($pieceTextNode, $child);
              $i++;
              if (($j+1) < count($pieces))
              {
                $node->insertBefore($node->ownerDocument->createElement('br'), $child);
                $i++;
                /*$node->insertBefore($node->ownerDocument->createElement('br'), $child);
                $i++;*/
              }
            }
            $node->removeChild($child);
            $i--;
          }
        }
        $i++;
      }
    }   
                        
    public function simpleProcess($text)
    {
      $allowedTags = array('a');
      $allowedAttributes = array('a' => array('href'));
      $text = $this->process($text, $allowedTags, $allowedAttributes);
      return $text;
    }
    
    public function createShortText($text, $position = 200, $encoding = 'utf-8', $interval = null)
    {
      $sourceText = $text;
      if (mb_strlen($text, $encoding) > $position)
      {
        $textLen = mb_strlen($text, $encoding);
        $i = 0;
        $rightSymbol = ( ($position + $i) < $textLen )  ?  mb_substr($text, $position + $i, 1, $encoding) : '';
        $leftSymbol = ( ($position - $i) >=0 )  ?  mb_substr($text, $position - $i, 1, $encoding) : '';
        
        while ( ($rightSymbol != '.') && ($leftSymbol != '.') && ( ( ($position + $i) < $textLen) || ( ($position - $i) >=0 ) ) )
        {
          $i++;
          $rightSymbol = ( ($position + $i) < $textLen )  ?  mb_substr($text, $position + $i, 1, $encoding) : '';
          $leftSymbol = ( ($position - $i) >=0 )  ?  mb_substr($text, $position - $i, 1, $encoding) : '';             
        }
        
        if ($rightSymbol == '.')
        {
          $text = mb_substr($text, 0, $position + 1 + $i, $encoding);
        }

        if ($leftSymbol == '.')
        {
          $text = mb_substr($text, 0, $position + 1 - $i, $encoding);
        }
      }
      
      if (!is_null($interval))
      {
        if ( ! ( ( mb_strlen($text, $encoding) >= ($position - $interval) ) && ( mb_strlen($text, $encoding) <= ($position + $interval) ) ) )
        {
          $text = $sourceText;
          $textLen = mb_strlen($text, $encoding);
          $i = 0;
          
          $leftSymbol = ( ($position - $i) >=0 )  ?  mb_substr($text, $position - $i, 1, $encoding) : '';
          
          while ( ($leftSymbol != ' ') && ( ($position - $i) >=0 )  )
          {
            $i++;
            $leftSymbol = ( ($position - $i) >=0 )  ?  mb_substr($text, $position - $i, 1, $encoding) : '';             
          }
          
          $text = mb_substr($text, 0, $position + 1 - $i, $encoding);
          
          $text = trim($text);
          
          $lastSymbol = mb_substr($text, mb_strlen($text, $encoding) - 1, 1, $encoding);
          if (in_array($lastSymbol, array(',', ':', ';')))
          {
            $text = mb_substr($text, 0, mb_strlen($text, $encoding) - 1, $encoding);            
          }          
          $text = $text . '...';
        }
      }
      
      $text = trim($text);
      
      return $text;
    }
        
    public function removeLinkFromText($text)
    {
      $text = preg_replace($this->linkRegExp, '', $text);
      return $text;
    }
    
    // фунция принимает строку текста и пытается найти в ней все ссылки на youtube
    // в случае успеха возвращает массив идентификаторов видео в контекте youtube   
    public function getClipsFromText($text)
    {
      $result = array();
      
      $match = array();
      if (preg_match_all($this->youTubeRegExp, $text, $match) !== false)
      { 
        for($index = 0; $index < count($match[2]); ++$index)
        {
          $result[] = $match[2][$index];        
        }
      }
      
      return $result;        
    }
    
    // функция принимает строку текста, удаляет из него все ссылки на клипы
    // и возвращает результирующий текст
    private function removeClipsFromText($text)
    {
      $result = $text;
      
      $regExp = mb_substr($this->youTubeRegExp, 1, mb_strlen($this->youTubeRegExp));
      
      $regExp = '/:?\s*' . $regExp;
      
      $result = preg_replace($regExp, '.', $result);
            
      return $result;        
    }
    
    public function createAnons2($text, $length = 200)
    {
      if (trim($text) != '')
      {
        $text = strip_tags($text);
        $text = $this->removeLinkFromText($text);
        $text = preg_replace('/\n/', ' ', $text);
        $text = preg_replace('/\s{2,}/', ' ', $text);
        $text = $this->createShortText($text, $length);
      }
      return $text;
    }
    
    public function createAnons($text, $length = 200, $interval = null)
    {
      if (trim($text) != '')
      {
        $text = preg_replace('/\n/', ' ', $text);
        $text = preg_replace('/(<br\s?\/>)+/', ' ', $text);
        
        $text = $this->removeClipsFromText($text);
                  
        //var_dump($text);
        $text = $this->simpleProcess($text);
        
        //var_dump($text);
        $text = $this->processLink($text);
        
        
        //var_dump($text);
        //$text = $this->simpleProcess($text);
        //var_dump($text);
        $text = $this->insertSongs($text);
        
        //var_dump($text);
        $text = $this->anonsInsertAlbums($text);
        
        //var_dump($text);
        $text = $this->anonsInsertConcerts($text);
        
        //var_dump($text);        
        $text = strip_tags($text);
        //var_dump(htmlspecialchars($text));
        //var_dump($text);
        $text = $this->removeLinkFromText($text);
        //var_dump($text);
        //$text = html_entity_decode($text, ENT_COMPAT, 'UTF-8');
        
        $text = trim($text);
        
        //$text = $this->replaceLinkWithHash($text);
              
        $text = $this->createShortText($text, $length, 'utf-8', $interval);

      }      
      return $text;
    }
    
    public function processDescription($text)
    {
      if (trim($text) != '')
      {
        
        $allowedTags = array('a', 'i', 'em');
        $allowedAttributes = array('a' => array('href'));
        
        $text = $this->process($text, $allowedTags, $allowedAttributes);

        $text = preg_replace('/(<br\/>\s*){3,}/', '<br/><br/>', $text);
        
        $text = $this->processLink($text);

        $text = $this->insertSongs($text);
        
        $text = trim($text);
        
        $text = $this->replaceLinkWithHash($text); 

      }
            
      return $text;
    }
    
    private function cutTextNode(DOMElement $node)
    {
      // проверка допустимых нод
      $i = 0;
      while ($i < $node->childNodes->length)
      {        
        $child = $node->childNodes->item($i);
        
        if ($child instanceof DOMElement)
        {
          $this->cutTextNode($child);
          if (($child->nodeName == 'a') && ($child->nodeValue == ''))
          {
            $node->removeChild($child);
            $i--;
          }
        }
        elseif ($child instanceof DOMText) 
        {
          if ($this->clearTextNode)
          {
            $child->nodeValue = '';
          }
          else
          {            
            $nodeText = $child->nodeValue;
            if ( (mb_strlen($nodeText) + $this->position) < $this->length )
            {
              $j = mb_strlen($nodeText) - 1; 
              $dotFound = false;
              while ( ($j > 0) and (!$dotFound) )
              {
                $currentSymbol = mb_substr($nodeText, $j, 1);
                if (in_array($currentSymbol, $this->breakSymbol))
                {
                  $this->currentDotPosition = $this->position + $j + 1;
                  $dotFound = true;
                }
                $j--;
              }
              $this->position += mb_strlen($nodeText);
            }
            else
            {
              $j = 0;
              $dotFound = false;
              while ( ($this->position + $j < $this->length) || (!$dotFound) )
              {
                $currentSymbol = mb_substr($nodeText, $j, 1);
                if ( in_array($currentSymbol, $this->breakSymbol) )
                {
                  if ($this->position + $j >= $this->length)
                  {
                    $dotFound = true;
                    $this->clearTextNode = true;
                    $child->nodeValue = mb_substr($nodeText, 0, $j + 1);
                  }
                  else
                  {
                    $this->currentDotPosition = $this->position + $j;
                  }
                }
                $j++;
              }
              $this->position = $this->position + $j;
            }
          }
        }
        $i++;
      }
    }     
    
    public function substr($string, $start, $length = 0)
    {
      if ($length == 0 || $length > ($stringLen = mb_strlen($string)))
      {
        return mb_substr($string, $start);
      }
      else
      {
        if (($endpos = mb_strrpos(mb_substr($string, $start, $length), ' ')) !== false)
        {
          return mb_substr($string, $start, $endpos - $start);
        }
        else if (($endpos = mb_strpos($string, ' ', $start + $length)) !== false)
        {
          return mb_substr($string, $start, $endpos - $start);
        }
        else
        {
          return mb_substr($string, $start, $length);
        }
      }
    }
    
    /**
    * @desc функция для вызова в array_walk($array, array($testProcessor, 'trim'))
    */
    public function trim(&$value)
    {
      $value = trim($value);
      return $value;
    }
        
    /**
    * @desc функция для вызова в array_walk($array, array($testProcessor, 'toLowerCase'))
    */
    public function toLowerCase(&$value)
    {
      $value = mb_convert_case(trim($value), MB_CASE_LOWER);
      return $value;
    } 
    
    public function parseArtistName($artistName)
    {
      $artistName = preg_split('/[\s]?feat[\.]?|vs[\.]?|ft[\.]?|&|и|,[\s]?/uiS', $artistName, -1, PREG_SPLIT_NO_EMPTY);
      array_walk($artistName, array($this, 'trim'));
      return $artistName;
    }
    
    public function addLineFoldingToText($text)
    {
      $text = explode("\n", $text);      
      $text = "<p>" . implode("</p><p>", $text) . "</p>"; 
      return $text;  
    }

    public function getPublicationHTMLText($text)
    {
      
      $text = $this->process($text);
      
      $text = preg_replace('/(<br\/>\s*){3,}/', '<br/><br/>', $text);        
     
      
      $text = $this->processLink($text);
                        
      $text = $this->insertAlbums($text);
      
      $text = $this->insertSongs($text);
            
      $text = $this->insertConcerts($text);
      
      $text = $this->insertImages($text); 
      
      $text = $this->replaceLinkWithHash($text); 
      
      return $text;
    }
    
    public function getPublicationTextForRSS($text)
    { 
       
      $text = $this->process($text);
      
      $text = preg_replace('/(<br\/>\s*){3,}/', '<br/><br/>', $text);
      
      $text = $this->processLink($text);
      
      $text = $this->removeAlbumsWidget($text);
      $text = $this->removeConcertsWidget($text);

      $text = $this->insertSongs($text);
      
      $text = $this->insertImages($text); 
      
      //$text = $this->replaceLinkWithHash($text); 
      
      return $text;
    }
    
    public function removeAlbumsWidget($text)
    {
      $reg = '/<a\shref="' . $this->albumLinkRegExp . '"\/>(\s*<br\/>)*/xi';
      $text = preg_replace($reg, '', $text);      
      $reg = '/<a\shref="' . $this->albumLinkRegExp2 . '"\/>(\s*<br\/>)*/xi';
      $text = preg_replace($reg, '', $text);      
      return $text;
    }

    public function removeConcertsWidget($text)
    {
      $reg = '/<a\shref="' . $this->concertLinkRegExp . '"\/>(\s*<br\/>)*/xi';
      $text = preg_replace($reg, '', $text);
      $reg = '/<a\shref="' . $this->concertLinkRegExp2 . '"\/>(\s*<br\/>)*/xi';
      $text = preg_replace($reg, '', $text);
      return $text;
    }    
    
    //private $linkRegExp = '/(https?|ftp):\/\/([\w\-]+(\.[\w\-]+)*(\.[a-z]{2,4})?)(\d{1,5})?(\/(\S*))?/i';
    //private $linkRegExp = '/(([^:\/#\s\.\?,!:;"«»()а-я]+):\/\/)?([^\/?#\s\?,!:;"«»()а-я]+\.[^\/?#\s\.а-я]{2,4})((\/[\S]*)?[^\s\.\?,!:;"«»()]+)?/i';
    public $linkRegExp = '/(([^:\/#\s\.\?,!:;"«»()а-я]+):\/\/)?(([^\/?#\s\?.,!:;"«»()а-я]+\.)+[a-z]{2,4})((\/[\S]*)?[^\s\.\?,!:;"«»()]+)?/i';
  
    private $internalLinkRegExp = '/weborama\.ru\S/i';
    
    
    public $albumLinkRegExp = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*music\/\d*\/(\d+)\/?';
    //http://www.weborama.ru/#/modules/album/view.php?id=85826
    public $albumLinkRegExp2 = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*modules\/album\/view\.php\?id=(\d+)';
    //http://www.weborama.ru/#/music/Franz_Ferdinand/Franz_Ferdinand/ 
    public $albumLinkRegExpText = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*music\/([^\/]+\/[^\/]+)\/?';

    //http://www.weborama.ru/#/modules/audio/song/view.php?id=64d6dd74b72bcd0b9e2249cda7c61f75
    public $songLinkRegExp2 = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*modules\/audio\/song\/view\.php\?id=([a-f0-9]{32})';
    //http://www.weborama.ru/#/music/123/5563/64d6dd74b72bcd0b9e2249cda7c61f75
    public $songLinkRegExp = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*music\/\d+\/\d+\/([a-f0-9]{32})\/?';
    //http://www.weborama.ru/#/music/Franz_Ferdinand/Franz_Ferdinand/Take_me_out/
    public $songLinkRegExpText = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*music\/([^\/]+\/[^\/]+\/[^\/]+)\/?';
    
    public $concertLinkRegExp = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*concerts\/(\d+)\/?';
    public $concertLinkRegExp2 = '(http:\/\/)?[a-z0-9]{3,5}\.weborama\.ru\/\S*modules\/event\/view\.php\?id=(\d+)';
    
    public $weboramaImageLinkRegExp = '(http:\/\/)?static1\.weborama\.ru\/publication\/preview\/[a-f0-9]{2}\/([a-f0-9]{32})';
    
    private $imageLinkRegExp = '(([^:\/?#\sа-я]+):\/\/)?([^\/?#\sа-я]+\.[^\/?#\s\.а-я]{1,4})(\/[\S]*)(gif|jpg|jpeg|png|bmp)';
    
    private $youTubeRegExp = '/(http\:\/\/)?www\.youtube\.com\/watch\?v=(\S{11})(&\S+)?/i';
    private $youTubeRuRegExp = '/(http\:\/\/)?ru\.youtube\.com\/watch\?v=(\S{11})(&\S+)?/i';
    private $ruTubeRegExp = '/(http\:\/\/)?rutube\.ru\/tracks\/\d+.html\?v=([a-f0-9]{32})/i';
    private $vimeoRegExp = '/(http\:\/\/)?vimeo\.com\/(\d+)(\?\S+)?/i';
    private $smotriRegExp = '/(http\:\/\/)?smotri\.com\/video\/view\/\?id=v(\S{10})/i';
    
    //public $simpleLinkRegExp = '/((https?|ftp):\/\/)?(www\.)?[a-z0-9\-\.]+\.(ru|su|com|info|net)([\w\/\-]*)?/i';    

    private function insertYouTubeLink($match)
    {
      $text = '<div><object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/' . $match[2] . '&hl=ru&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="allowscriptaccess" value="always"></param><param name="wmode" value="opaque"></param><embed src="http://www.youtube.com/v/' . $match[2] . '&hl=ru&fs=1" type="application/x-shockwave-flash" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="425" height="344"></embed></object></div>';
      return $text;
    }

    private function insertYouTubeRuLink($match)
    {                      
      $text = '<div><object width="425" height="344"><param name="movie" value="http://www.youtube.com/v/' . $match[2] . '&hl=ru&fs=1"></param><param name="allowFullScreen" value="true"></param><param name="wmode" value="opaque"></param><embed src="http://www.youtube.com/v/' . $match[2] . '&hl=ru&fs=1" type="application/x-shockwave-flash" allowfullscreen="true" wmode="opaque" width="425" height="344"></embed></object></div>';
      return $text;
    }

    private function insertRuTubeLink($match)
    {
      $text = '<div><OBJECT width="470" height="353"><PARAM name="movie" value="http://video.rutube.ru/' . $match[2] . '"></PARAM><PARAM name="wmode" value="window"></PARAM><PARAM name="allowFullScreen" value="true"></PARAM><param name="wmode" value="opaque"></param><EMBED src="http://video.rutube.ru/' . $match[2] . '" type="application/x-shockwave-flash" wmode="window" wmode="opaque" width="470" height="353" allowFullScreen="true" ></EMBED></OBJECT></div>';
      return $text;
    }
    
    private function insertVimeoLink($match)
    {
      $text = '<div><object width="400" height="225">  <param name="allowfullscreen" value="true" />  <param name="allowscriptaccess" value="always" />  <param name="movie" value="http://vimeo.com/moogaloop.swf?clip_id=' . $match[2] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" /><param name="wmode" value="opaque" />  <embed src="http://vimeo.com/moogaloop.swf?clip_id=' . $match[2] . '&amp;server=vimeo.com&amp;show_title=1&amp;show_byline=1&amp;show_portrait=0&amp;color=&amp;fullscreen=1" type="application/x-shockwave-flash" allowfullscreen="true" allowscriptaccess="always" wmode="opaque" width="400" height="225"></embed></object></div>';
      return $text;
    }

    private function insertSmotriLink($match)
    {
      $text = '<div><object classid="clsid:d27cdb6e-ae6d-11cf-96b8-444553540000" width="400" height="330"><param name="movie" value="http://pics.smotri.com/scrubber_custom8.swf?file=v' . $match[2] . '&bufferTime=3&autoStart=false&str_lang=rus&xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color_lightaqua.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml" /><param name="allowScriptAccess" value="always" /><param name="wmode" value="opaque" /><param name="allowFullScreen" value="true" /><param name="bgcolor" value="#ffffff" /><embed src="http://pics.smotri.com/scrubber_custom8.swf?file=v' . $match[2] . '&bufferTime=3&autoStart=false&str_lang=rus&xmlsource=http%3A%2F%2Fpics.smotri.com%2Fcskins%2Fblue%2Fskin_color_lightaqua.xml&xmldatasource=http%3A%2F%2Fpics.smotri.com%2Fskin_ng.xml" quality="high" allowscriptaccess="always" allowfullscreen="true" wmode="opaque" width="400" height="330" type="application/x-shockwave-flash"></embed></object></div>';
      return $text;
    }

    
    public function getYouTubeFlashPlayer($videoId)
    {
      return $this->insertYouTubeLink(array(2 => $videoId));
    }
    
    private function processYouTubeLink($text)
    {
      return preg_replace_callback($this->youTubeRegExp, array($this, 'insertYouTubeLink'), $text);
    }

    private function processYouTubeRuLink($text)
    {
      return preg_replace_callback($this->youTubeRuRegExp, array($this, 'insertYouTubeRuLink'), $text);
    }

    private function processRuTubeLink($text)
    {
      return preg_replace_callback($this->ruTubeRegExp, array($this, 'insertRuTubeLink'), $text);
    }
    
    private function processVimeoLink($text)
    {
      return preg_replace_callback($this->vimeoRegExp, array($this, 'insertVimeoLink'), $text);
    }

    private function processSmotriLink($text)
    {
      return preg_replace_callback($this->smotriRegExp, array($this, 'insertSmotriLink'), $text);
    }
    
    
    public function processVideoLink($text)
    {
      $text = $this->processYouTubeLink($text);
      $text = $this->processYouTubeRuLink($text);      
      $text = $this->processRuTubeLink($text);
      $text = $this->processVimeoLink($text);
      $text = $this->processSmotriLink($text);
      return $text;
    }
        
    public function processLink($text)
    {
      
      if (trim($text) != '')
      {
        $document = new DOMDocument('1.0', 'utf-8');
        
        $text = '<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body><div>' . $text . '</div></body></html>';

        @$document->loadHTML($text);

        $this->searchNodeWithLink($document->documentElement->childNodes->item(1)->childNodes->item(0), $document);
        
        $this->replaceLink($document);

        $text = $document->saveXML();
      
        $matches = null;
        preg_match('/<html><head><meta http-equiv="Content-Type" content="text\/html; charset=utf-8"\/><\/head><body><div>(.*)<\/div><\/body><\/html>/s', $text, $matches);
        if ($matches)
        {
          $text = $matches[1];
        }
      }
      
      return $text;
    }
    
    private $textNodeToReplace = array();
    //сначала пробежимся по DOM, занесём в массив все текстовык ноды с ссылками, потом заменим эти ноды
    
    private function searchNodeWithLink(DOMElement $node, DOMDocument $document)
    {
      $childs = $node->childNodes;
      foreach ($childs as $child)
      {                  
        if ($child instanceof DOMElement)
        {
          if ($child->nodeName != 'a')
          {
            $this->searchNodeWithLink($child, $document);
          }
        }
        if ($child->nodeName == '#text')
        {  
          if (preg_match($this->linkRegExp, $child->nodeValue))
          {            
            $this->textNodeToReplace[] = $child;
          }
        }
      }
    }
    
    private function replaceLink($document)
    {
      foreach ($this->textNodeToReplace as $child)
      {   
        $parent = $child->parentNode;
        
        /*$i = 0;
        
        foreach ($parent->childNodes as $node)
        {
          if ($i > 0)
          {     
            if ($node->nodeName == 'br')
            {
              $parent->removeChild($node);
            }
            break;
          }
          if ($node === $child)
          {
            $i++;
          }
        }*/
        
        $child->nodeValue = preg_replace_callback($this->linkRegExp, array($this, 'insertLink'), $child->nodeValue);
        $child->nodeValue = $this->processVideoLink($child->nodeValue);
        
        $document2 = new DOMDocument('1.0', 'utf-8');
        @$document2->loadHTML('<html><head><meta http-equiv="Content-Type" content="text/html; charset=utf-8"/></head><body><div>' . $child->nodeValue . '</div></body></html>');
        $body = $document2->documentElement->childNodes->item(1)->childNodes->item(0);
        $parent = $child->parentNode;
        foreach ($body->childNodes as $newNode)
        {
          $parent->insertBefore($document->importNode($newNode, true), $child);
        }
        $parent->removeChild($child);
      }
      $this->textNodeToReplace = array();
    }    
    
    private function insertLink($match)
    {    
      $link = $match[0];
      
      if (preg_match($this->internalLinkRegExp, $link))
      {
        $text = '<a  href="' . $link . '">' . $link . '</a>';
      }
      else
      {   
        if ( mb_strpos($link, 'http://') !== false )
        {
          $text = '<a target="_top" href="' . $link . '">' . $link . '</a>';
        }
        else
        {
          $text = '<a target="_top" href="http://' . $link . '">' . $link . '</a>';
        }        
      }
      
      if (preg_match($this->youTubeRegExp, $link))
      {
        $text = $link;
      }      
      if (preg_match($this->youTubeRuRegExp, $link))
      {
        $text = $link;
      }      
      if (preg_match($this->ruTubeRegExp, $link))
      {
        $text = $link;
      }
      if (preg_match($this->vimeoRegExp, $link))
      {
        $text = $link;
      }      
      if (preg_match($this->smotriRegExp, $link))
      {
        $text = $link;
      }      

      if ( preg_match('/^' . $this->albumLinkRegExp . '$/i', $link) || preg_match('/^' . $this->albumLinkRegExp2 . '$/i', $link) || preg_match('/^' . $this->albumLinkRegExpText . '$/i', $link) )
      {
        $text = '<a  href="' . $link . '"></a>';
      }       

      if ( preg_match('/^' . $this->songLinkRegExp . '$/i', $link) || preg_match('/^' . $this->songLinkRegExp2 . '$/i', $link) || preg_match('/^' . $this->songLinkRegExpText . '$/i', $link))
      {
        $text = '<a  href="' . $link . '"></a>';
      }      
      
      if ( preg_match('/^' . $this->concertLinkRegExp . '$/i', $link) || preg_match('/^' . $this->concertLinkRegExp . '$/i', $link))
      {
        $text = '<a  href="' . $link . '"></a>';
      }      

      if ( preg_match('/^' . $this->weboramaImageLinkRegExp . '$/i', $link) || preg_match('/^' . $this->imageLinkRegExp . '$/i', $link) )
      {
        $text = '<a  href="' . $link . '"></a>';
      }      
      return $text;
    }

    


    public function getYouTubePageVideo($videoId)
    {
      return 'http://ru.youtube.com/watch?v=' . $videoId;  
    }
    
    public function removeAlbums($text)
    { 
      $reg = '/<a\shref="http:\/\/[a-z0-9]{3,5}\.weborama\.ru\/modules\/album\/view\.php\?id=(\d+)"><\/a>/xi';
      $text = preg_replace($reg, ' ', $text);
      return $text;
    }
    



    private function replaceLinkWithHash($text)
    {
      return preg_replace('/weborama.ru\/#\//', 'weborama.ru/', $text);
    }    
    
    private function insertSongs($text)
    {     
      $reg = '/<a\shref="' . $this->songLinkRegExp . '">(.+?)<\/a>/xi';
      $text = preg_replace_callback($reg, array($this, 'insertSong2'), $text);
      $reg = '/<a\shref="' . $this->songLinkRegExp2 . '">(.+?)<\/a>/xi';
      $text = preg_replace_callback($reg, array($this, 'insertSong2'), $text);
        
      $reg = '/<a\shref="' . $this->songLinkRegExp . '"\/>/xi';
      $text = preg_replace_callback($reg, array($this, 'insertSong'), $text);
      $reg = '/<a\shref="' . $this->songLinkRegExp2 . '"\/>/xi';
      $text = preg_replace_callback($reg, array($this, 'insertSong'), $text);
      
      /*  новые текcтовые урлы  */
      $reg = '/<a\shref="' . $this->songLinkRegExpText . '"\/>/xi';
      if (preg_match_all($reg, $text, $matches, PREG_SET_ORDER))
      {
        foreach ($matches as $match)
        {
          $parameters = $this->application->contentProvider->getRewriteData('song', urldecode($match[2]));
          if ($this->application->errorHandler->getLast() == E_WEBORAMA_SUCCESS)
          {
            $songText = $this->insertSong(array(2 => $parameters['id']));          
            $text = str_replace($match[0], $songText, $text);
          }
          else
          {
            //$songText = mb_substr($match[0], 0, mb_strrpos($match[0], '/')) . '>' . mb_substr($match[2], mb_strrpos($match[2], '/') + 1) . '</a>';
            $text = str_replace($match[0], urldecode(mb_substr($match[2], mb_strrpos($match[2], '/') + 1)), $text);
          }          
        }
      }
      
      return $text;
    }
    //-----------------------------------------------------------------------------------------------------

    private function insertAlbums($text)
    {
      $reg = '/(\s*<br\/>){0,2}<a\shref="' . $this->albumLinkRegExp . '"\/>(\s*<br\/>){0,2}/xi';
      $text = preg_replace_callback($reg, array($this, 'insertAlbum'), $text);
      
      $reg = '/(\s*<br\/>){0,2}<a\shref="' . $this->albumLinkRegExp2 . '"\/>(\s*<br\/>){0,2}/xi';
      $text = preg_replace_callback($reg, array($this, 'insertAlbum'), $text);
      
      /*  новые текcтовые урлы  */
      $reg = '/(\s*<br\/>){0,2}<a\shref="' . $this->albumLinkRegExpText . '"\/>(\s*<br\/>){0,2}/xi';
      if (preg_match_all($reg, $text, $matches, PREG_SET_ORDER))
      {       
        foreach ($matches as $match)
        {
          $parameters = $this->application->contentProvider->getRewriteData('album', urldecode($match[3]));
          if ($this->application->errorHandler->getLast() == E_WEBORAMA_SUCCESS)
          {
            $textAlbum = $this->insertAlbum(array(3 => $parameters['id']));          
            $text = str_replace($match[0], $textAlbum, $text);
          }
          else
          {
            $text = str_replace($match[0], urldecode(mb_substr($match[3], mb_strrpos($match[3], '/') + 1)), $text);
          }
        }
      }
      
      return $text;
    }    
    //-----------------------------------------------------------------------------------------------------

    private function anonsInsertAlbums($text)
    {
      //$reg = '/<a\shref="http:\/\/[a-z0-9]{3,5}\.weborama\.ru\/[\S]*modules\/album\/view\.php\?id=(\d+)"\/>/xi';
      $reg = '/<a\shref="' . $this->concertLinkRegExp . '"\/>/xi';
      $text = preg_replace_callback($reg, array($this, 'anonsInsertAlbum'), $text);
      //$reg = '/<a\shref="http:\/\/[a-z0-9]{3,5}\.weborama\.ru\/[\S]*music\/[\d]*\/([\d]*)\/"\/>/xi';
      $reg = '/<a\shref="' . $this->concertLinkRegExp2 . '"><\/a>/xi';
      $text = preg_replace_callback($reg, array($this, 'anonsInsertAlbum'), $text);
      
      return $text;
    }     
    //-----------------------------------------------------------------------------------------------------
    
    private function insertConcerts($text)
    {
      $reg = '/<a\shref="' . $this->concertLinkRegExp . '"\/>(\s*<br\/>)*/xi';
      $text = preg_replace_callback($reg, array($this, 'insertConcert'), $text);

      $reg = '/<a\shref="' . $this->concertLinkRegExp2 . '"><\/a>(\s*<br\/>)*/xi';
      $text = preg_replace_callback($reg, array($this, 'insertConcert'), $text);      
      
      return $text;
    }
    //-----------------------------------------------------------------------------------------------------
    private function anonsInsertConcerts($text)
    {
      //$reg = '/<a\shref="http:\/\/[a-z0-9]{3,5}\.weborama\.ru\/[\S]*modules\/event\/view\.php\?id=(\d+)"><\/a>/xi';
      $reg = '/<a\shref="' . $this->concertLinkRegExp . '"\/>/xi';
      $text = preg_replace_callback($reg, array($this, 'anonsInsertConcert'), $text);
            
      //$reg = '/<a\shref="http:\/\/[a-z0-9]{3,5}\.weborama\.ru\/[\S]*concerts\/(\d+)\/"\/>/xi';
      $reg = '/<a\shref="' . $this->concertLinkRegExp2 . '"><\/a>/xi';
      $text = preg_replace_callback($reg, array($this, 'anonsInsertConcert'), $text);
      
      return $text;
    }
    //-----------------------------------------------------------------------------------------------------
    
    private function insertSong($match)                      
    {                               
      $song = new bmSong($this->application, array('identifier' => $match[2], 'load' => true));
      $songNumber = '';
      $songPage = $song->info;//$this->application->contentProvider->getPageURIContent('audio', $song);
      $songName = $song->title;
       
      $songTemplate = '';
      eval('$songTemplate .= "' . $this->application->getTemplate('publication/view/songInText') . '";');
      return $songTemplate;                                                                
    }    

    private function insertSong2($match)
    {                       
      $song = new bmSong($this->application, array('identifier' => $match[2], 'load' => true));
      $songNumber = '';
      $songPage = $song->info;//$this->application->contentProvider->getPageURIContent('audio', $song);
      $songName = $match[3];

      $songTemplate = '';
      eval('$songTemplate .= "' . $this->application->getTemplate('publication/view/songInText') . '";');
      return $songTemplate;                                                                
    }    

    private function insertAlbum($match)
    {
      $albumTemplate = '';          
      
      //var_dump($match);
         
      $albumId = $match[3];
            
      //$album = new bmAlbum($this->application, array('identifier' => $albumId, 'load' => true));
      $album = new bmAlbum($this->application, array('identifier' => $albumId));
      if ($this->application->errorHandler->getLast() == E_WEBORAMA_SUCCESS)
      {
        $albumCover = $album->getCover(256);
        $albumArtist = $album->artist;                                                       
        $albumPage = $this->application->contentProvider->getPageURIContent('album', $album);
        $albumName = $album->title;
        $albumYear = $album->year->format('Y');
        if ($albumYear == '-0001')
        {
          $albumYear = '';
        }
        
        if ($album->mainArtist != null)
        {
          $albumArtist = htmlspecialchars($album->mainArtist->title);    
        }
        
        //if (count($album->artists) > 0)
  //      {
  //        $albumArtist = $album->artists[0]->title;
  //      }
                                     
        $songs = ''; 
        //$songPlayTemplate = $this->application->getTemplate('publication/view/songPlay');
        $songTemplate = $this->application->getTemplate('publication/view/song');  
        $songNoFileTemplate = $this->application->getTemplate('publication/view/songNoFile');  
        
        $songNumber = 1;
        $albumPostfix = rand(0, 90000);
        $left = 0;
        //$songsTitleLength = 0;
        $songsQuantity = count($album->songs);
        foreach ($album->songs as $item)
        {
          //$songsTitleLength += mb_strlen($song->title);
          $songBlockId = 'songList_'. $album->identifier . $albumPostfix . '_' . ($songNumber - 1);
          $playPopupSRC = C_PLAY_POPUP_SRC;
          if ($songNumber == 1)
          {
            $className = 'activePlay';
          }
          else
          {
            $className = '';
          }
          
          
          $songsIdentifiers = array();
          
          foreach ($album->songIds as $songIdentifier)
          {
            $songsIdentifiers[] = $songIdentifier->songId;
          }                                     
          
          $songsId = "'" . implode("', '", array_slice($songsIdentifiers, ($songNumber - 1))) . "'";
                    
          $songPage = $item->song->info;
          $songName = htmlspecialchars($item->song->title);   
          $offset = $songNumber - 1; 
          $limit = $songsQuantity - $offset; 
         
          /*if ($songNumber > 1)
          {
            eval('$songs .= "' . $songTemplate . '";');    
          }
          else
          {
            eval('$songs .= "' . $songPlayTemplate . '";');    
          }*/
          
          if ($item->song->hasFile == 1)
          {
            eval('$songs .= "' . $songTemplate . '";');
          }
          else
          {
            eval('$songs .= "' . $songNoFileTemplate . '";');
          }
          
          ++$songNumber;
          /*if($songNumber > 9)
          {
            $left = 30;
            if($songNumber >99)
            {
              $left = 35;
            }
          }*/  
        }
        
        /*if (count($album->songs) == 0)
        {
          $symbolsPerSong = 0;
        }
        else
        {
          $symbolsPerSong = $songsTitleLength / count($album->songs);
        }
        
        if ($symbolsPerSong > 17)
        {
          $albumTemplate = $this->application->getTemplate('publication/view/wideAlbum');
        }                
        else
        {
          $albumTemplate = $this->application->getTemplate('publication/view/album');
        }*/
        $albumTemplate = $this->application->getTemplate('publication/view/album');

        eval('$albumTemplate = "' . $albumTemplate . '";');
      }
      return $albumTemplate;
    }
    
    public function removeAlbumFromText(bmAlbum $album, $text)
    {
      $result = str_replace($album->info, '', $text);
      $result = str_replace('<a href="http://www.weborama.ru/modules/album/view.php?id="' . $album->identifier . '"></a>', '', $text);
      
      
      
      return $result;
    }
    
    private function anonsInsertAlbum($match)
    {
      $albumId = $match[1];
            
      $album = new bmAlbum($this->application, array('identifier' => $albumId, 'load' => true));
      
      $albumArtist = htmlspecialchars($album->artist);
      
      $albumName = htmlspecialchars($album->title);

      return $albumArtist  . ': ' . $albumName;
    }   
 

    private function insertConcert($match)
    {
      $concertBlock = '';
      $concert = new bmConcert($this->application, array('identifier' => $match[2], 'load' => true));
      $concertPoster = '';
      if(trim($concert->poster) !== '')
      {
        $concertPoster = $this->application->contentProvider->getContentLocation('concert', $concert->poster, 'w256');  
      }
      else
      {
        $concertPoster = $this->application->contentProvider->getDefaultEventPosterBig();
      }
      $concertPath = $concert->info; //$this->application->contentProvider->getPageURI('event', $concert->identifier);
      $concertName = htmlspecialchars($concert->name);

      $concertDetailsArray = array();

      $artisrts = $concert->artists;
      foreach ($artisrts as $artist)
      {
        //$concertDetailsArray[] = '<a href="' . $this->application->contentProvider->getPageURI('artist', $artist->identifier) . '">' . htmlspecialchars($artist->title) . '</a>';
        $concertDetailsArray[] = '<a href="' . $artist->info . '">' . htmlspecialchars($artist->title) . '</a>';
      }

      if (trim($concert->location) != '')
      {
        $concertDetailsArray[] = trim($concert->location);
      }

      $concertDetailsArray[] = $this->application->getStringDate($concert->date);
            
      $concertDetails = '';
      $concertDetails = implode(', ', $concertDetailsArray);
      $concertText = '';
      $concertText = $concert->text;

      $concertTextArray = explode(' ', $concertText, 51);
      if (count($concertTextArray) > 50)
      {
        $concertText = implode(' ', array_slice($concertTextArray, 0, 50)) . '...'; 
      }
                      
      if($concert->poster !== '')
      {                      
        eval('$concertBlock .= "' . $this->application->getTemplate('publication/view/concert') . '";');
      }
      else
      {
        eval('$concertBlock .= "' . $this->application->getTemplate('publication/view/concertDefaultPoster') . '";');
      }

      return $concertBlock;
    }
    
    private function anonsInsertConcert($match)
    {
    
      $concert = new bmConcert($this->application, array('identifier' => $match[1], 'load' => true));

      $concertName = htmlspecialchars($concert->name);
         
      $concertDetailsArray = array();

      $artisrts = $concert->artists;
      foreach ($artisrts as $artist)
      {
        $concertDetailsArray[] = htmlspecialchars($artist->title);
      }

      /*if (trim($concert->location) != '')
      {
        $concertDetailsArray[] = trim($concert->location);
      }*/

      //$concertDetailsArray[] = $this->application->getStringDate($concert->date);
            
      $concertDetails = '';
      $concertDetails = implode(', ', $concertDetailsArray);

      return $concertName . ' ' . $concertDetails;
    }
    
    public function insertImages($text)
    {
      $reg = '/(\s*<br\/>){0,2}<img\ssrc="(\S*)"\/>(\s*<br\/>){0,1}/ix';
      $text = preg_replace_callback($reg, array($this, 'insertImage'), $text); 

      $reg = '/(\s*<br\/>){0,2}<a\shref="(' . $this->weboramaImageLinkRegExp . ')"\/>(\s*<br\/>){0,1}/ix';
      $text = preg_replace_callback($reg, array($this, 'insertImage'), $text);

      $reg = '/(\s*<br\/>){0,2}<a\shref="(' . $this->imageLinkRegExp . ')"\/>(\s*<br\/>){0,1}/ix'; //(\s*<br\/>)*
      $text = preg_replace_callback($reg, array($this, 'insertImage'), $text);
      
      return $text;
    }
    
    public function insertImage($match)
    {
      $image = '';
      $imagePath = $match[2];      
      if ($imagePath == '/images/global/partners/afisha.png')
      {
        $image = '<img style="border: none;" src="/images/global/partners/afisha.png" />';
      }
      else
      {
        eval('$image = "' . $this->application->getTemplate('publication/view/image') . '";');
      }      
      return $image;  
    }

    /*-----------------------------------  search  --------------------------------------*/
    public function prepareSearchCache(&$string)
    {
      $string = mb_convert_case(trim(htmlspecialchars_decode($string)), MB_CASE_LOWER);
      $string = strip_tags($string);
      //$string = preg_replace('/(http:\/\/)?(w{3}\.)?([^\.]+\.[a-z]{2,4})[\/]?/iS', '', $string);
      $string = $this->pregReplaceInString('((http://|https://|ftp://|ftps://)?([^\.]+\.)?([^\.]+\.[a-z]{2,4})([^\s]+))', '', $string);
      $string = preg_replace('/№|#[\s]?([\d]+)/S', 'NUMBER \1', $string);
      //tring = preg_replace('/\x{401}|\x{451}/uS', 'е', $string);

      if (preg_match_all('/\$|@\%|#{2,}/S', $string, $letters, PREG_SET_ORDER))
      {
        foreach ($letters as $letter)
        {
          $string = $this->pregReplaceInString($letter[0], strlen($letter[0]) . $letter[0]{0}, $string);
        }
      }
      $string = preg_replace('/\$/S', 'BUKS', $string);
      $string = preg_replace('/@/S', 'ATA', $string);
      $string = preg_replace('/%/S', 'PERSENT', $string);
      $string = preg_replace('/#/S', 'DIEZ', $string);
      $string = preg_replace('~[\~`=@$%^*\(\)\|\{\}\[\]\'"<>]~S', '', $string);
      $string = preg_replace('~[\.,!?;:+_\-/]~S', ' ', $string);
      
      $string = $this->convertNumerater($string);      
      $string = preg_replace('~([\d]+)([\x{430}-\x{44F}a-z]+)~uS', '\1 \2', $string);
      $string = preg_replace('~([\x{430}-\x{44F}a-z]+)([\d]+)~uS', '\1 \2', $string);

      //$string = $this->pregReplaceInString('(track[\s]?[\d]+)', '', $string);
      //$string = $this->pregReplaceInString('(rmx|mix|remix|megamix|mixs|remixs|megamixs|микс)', ' REMIX ', $string);
      $string = $this->pregReplaceInString('(n)', ' AND ', $string);
      //$string = $this->pregReplaceInString('(i|im|the|to|as|a|an|of|on|at|for|cd[\s]?[\d]{1,})', ' ', $string);
      //$string = $this->pregReplaceInString('(feat|ft|vs[\.]?)', ' FEAT ', $string);
      //$string = $this->pregReplaceInString('([\x{430}-\x{44F}]{1,2})', ' ', $string);
      
      //$string = $this->pregReplaceInString('([\x{430}-\x{44F}a-z]{1,3})[-\/_& ]([\x{430}-\x{44F}a-z]{1,3})', '\1\2', $string);
      //$string = $this->pregReplaceInString('([\x{430}-\x{44F}a-z]{1,3})[-\/_&[\s]]', '\1\2', $string);
      //$string = $this->pregReplaceInString('[-\/_&[\s]]([\x{430}-\x{44F}a-z]{1,3})', '\1\2', $string);
      //$string = $this->pregReplaceInString('([\x{430}-\x{44F}a-z]{4,})[\s]?[-\/_&][\s]?([\x{430}-\x{44F}a-z]{4,})', '\1 \2', $string);

      //tring = preg_replace('/(.{1})\1{1,}/uS', '\1', $string);
      $string = preg_replace('/([\s]{2,})/', ' ', $string);
      $string = trim($string);

      $words = explode(' ', $string);
      foreach ($words as $word)
      {
        //if (($len = strlen($word)) < 3)
        if (($len = mb_strlen($word, 'utf-8')) < 3)
        {  
          //$string = str_replace($word, $word . str_repeat('A', 3 - $len), $string);
          $string = $this->pregReplaceInString($word, $word . str_repeat('Σ', 3 - $len), $string);
        }  
      }
      
      return $string;
    }  
    
    private function convertNumerater($string)
    {
      $string = preg_replace('/\b([\d]+)[\s]?(st|nd|rd|th)\b/ie', '"\1" . mb_convert_case("\2", MB_CASE_UPPER)', $string);
      // й ая ое гми
      if(preg_match_all('/([\d]+)[\s]?[-]?[\s]?[\x{439}\x{430}\x{44F}\x{43E}\x{435}\x{433\x{43C}\x{438}}][\s]+/iuS', $string, $match, PREG_SET_ORDER))
      {
        for($i = 0; $i < count($match); ++$i)
        {
          $postfix = '';
          switch($match[$i][1])
          {
            case 1: 
              $postfix = 'ST';
            break;
            case 2: 
              $postfix = 'ND';
            break;
            case 3: 
              $postfix = 'RD';
            break;
            default:
              $postfix = 'TH';
          }
          $string = str_replace($match[$i][0], $match[$i][1] . $postfix . ' ', $string);
        }
      }
      return $string;
    }
    
    private function pregReplaceInString($pattern, $replace, $string)
    {
      $pattern = addslashes($pattern);
      if(is_array($replace))
      {
        /*
        $string = preg_replace('/^' . $pattern . '$/iuS', array_key_exists(0, $replace) ? $replace[0] : $replace[count($replace) - 1], $string);
        $string = preg_replace('/^' . $pattern . '[\s]+/iuS', array_key_exists(1, $replace) ? $replace[1] : $replace[count($replace) - 1] . ' ', $string);
        $string = preg_replace('/[\s]+' . $pattern . '$/iuS', ' ' . array_key_exists(2, $replace) ? $replace[2] : $replace[count($replace) - 1], $string);
        $string = preg_replace('/[\s]+' . $pattern . '[\s]+/iuS', ' ' . array_key_exists(3, $replace) ? $replace[3] : $replace[count($replace) - 1] . ' ', $string);
        */
        $string = preg_replace('~^' . $pattern . '$~iuS', array_key_exists(0, $replace) ? $replace[0] : $replace[count($replace) - 1], $string);
        $string = preg_replace('~[\s]+' . $pattern . '[\s]+~iuS', ' ' . array_key_exists(3, $replace) ? $replace[3] : $replace[count($replace) - 1] . ' ', $string);
        $string = preg_replace('~^' . $pattern . '[\s]+~iuS', array_key_exists(1, $replace) ? $replace[1] : $replace[count($replace) - 1] . ' ', $string);
        $string = preg_replace('~[\s]+' . $pattern . '$~iuS', ' ' . array_key_exists(2, $replace) ? $replace[2] : $replace[count($replace) - 1], $string);
      }
      else
      {
        $string = preg_replace('~^' . $pattern . '[\s]+~iuS', $replace . ' ', $string);
          /*
        $string = preg_replace('/^' . $pattern . '$/iuS', $replace, $string);
       $string = preg_replace('/[\s]+' . $pattern . '$/iuS', ' ' . $replace, $string);
        $string = preg_replace('/[\s]+' . $pattern . '[\s]+/iuS', ' ' . $replace . ' ', $string);
        */
        $string = preg_replace('~^' . $pattern . '$~iuS', $replace, $string);
        $string = preg_replace('~[\s]+' . $pattern . '[\s]+~iuS', ' ' . $replace . ' ', $string);
        $string = preg_replace('~^' . $pattern . '[\s]+~iuS', $replace . ' ', $string);
        $string = preg_replace('~[\s]+' . $pattern . '$~iuS', ' ' . $replace, $string);
      }
      return $string;
    }
     
    public function selectWords($input, $words, $templateName = '')   
    {
      mb_regex_encoding('UTF-8');
      mb_internal_encoding('UTF-8');
      
      if ($templateName == '')
      {
        $template = '<strong>{$currentWord}</strong>';
      }
      else
      {
        $template = $this->application->getTemplate($templateName);
      }
      
      if (!is_array($words))
      {
        $words = str_replace(' ', '|', $words);
      }
      else
      {
        $words = implode('|', $words);      
      }
      $words = addcslashes($words, '/\'?');
      
      $counter = 0;
      $input = mb_convert_encoding($input, 'UTF-8');

      //if (preg_match_all('/\b(' . $words . ')\b/uiS', $input, $match, PREG_SET_ORDER))
      if (preg_match_all('/[^a-zа-я0-9]?(' . $words . ')[^a-zа-я0-9]?/uiS', $input, $match, PREG_SET_ORDER))
      {
        $uniqueWords = array();
        for ($i = 0; $i < count($match); ++$i)
        {
          if (!in_array($match[$i][1], $uniqueWords))
          {
            $uniqueWords[] = $match[$i][1];
            $currentWord = $match[$i][1];
            eval('$currentWord = "' . $template . '";');
            $input = str_replace($match[$i][1], $currentWord, $input);
          }
        }
      }
      return $input;
    }
    
    public function parseGenres($genresString, $load = false, $create = false)
    {
      $genres = mb_convert_case(trim(htmlspecialchars_decode($genresString)), MB_CASE_LOWER);
      //$genres = preg_replace("~[\s]?\r?\n[\s]?~", '  ', $genres);
      $genres = preg_replace("~[\s]-[\s]~", '-', $genres);
      $genres = preg_split('/([\s]*\r?[\n,\.;:\(\)]+[\s]*)/', $genres, -1, PREG_SPLIT_NO_EMPTY);
      $genres = array_unique($genres);

      $genres = $this->application->genreCache->getGenresBySynonym($genres, $load, $create);
      return $genres;
    }
    
    public function pack($string)
    {
      return unpack('I*', md5(mb_convert_case(trim($string), MB_CASE_LOWER), true));
    }
    
    public function unpack($pack)
    {
      $result = unpack('H*', pack('I', $pack[1]) . pack('I', $pack[2]) . pack('I', $pack[3]) . pack('I', $pack[4]));
      return $result[1];
    }
    
    public function hashToPack($hash)
    {
      $result = pack('H*', $hash);
      $result = unpack('I*', $result);
      return $result;
    }

    public function parseSongName($name)
    {
      $encoding = mb_detect_encoding($name);
      if (mb_convert_case($encoding, MB_CASE_LOWER) != 'utf-8')
      {
        $name = mb_convert_encoding($name, 'utf-8', $encoding);
      }
      
      $result = false;

      // trackNum artistName name
      if (preg_match('/([\d]+)[\s]?[\.-]+[\s]?(.*)[\s]?[-]+[\s]?(.*)\.mp3$/i', $name, $match))
      {
        $result = new stdClass();
        $result->trackNum = intval($match[1]);
        $result->artistName = trim(str_replace(array('_'), ' ', $match[2]));
        $result->name = trim(str_replace(array('_'), ' ', $match[3]));
      }
      // trackNum name 
      elseif (preg_match('/([\d]+)[\s]?[\.-]+[\s]?(.*)\.mp3$/i', $name, $match))
      {
        $result = new stdClass();
        $result->trackNum = intval($match[1]);
        $result->name = trim(str_replace(array('_'), ' ', $match[2]));
      }
      //artistName trackNum name
      elseif (preg_match('/(.*)[\s]?[\.-]+[\s]?([\d]+)?[\s]?(.*)\.mp3$/i', $name, $match))
      {
        $result = new stdClass();
        $result->trackNum = intval($match[2]);
        $result->artistName = trim(str_replace(array('_'), ' ', $match[1]));
        $result->name = trim(str_replace(array('_'), ' ', $match[3]));
      }
      
      return $result;
    }
    
    public function segmentateNumber($number)
    {
      $result = number_format($number, 0, ',', ' ');
      $result = str_replace(' ', '<span class="tsp">&nbsp;</span>', $result);
      return $result;
    }
    
    public function subString($string, $length, $encode = 'utf-8')
    {
      if ((mb_strlen($string) - $length) > 3)
      {
        $string = mb_substr($string, 0, $length, $encode);      
        $string = trim($string) . '...';
      }
      return $string;
    }    
  }
  ?>