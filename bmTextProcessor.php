<?php

class bmTextProcessor extends bmCustomTextProcessor 
  {
    
    private $allowedTags = array();
    private $allowedAttributes = array();

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
      
      
      //return $this->typo($text);
      //return $this->post_typo($text);
      return $this->text;
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
    
        
    
    //private $linkRegExp = '/(https?|ftp):\/\/([\w\-]+(\.[\w\-]+)*(\.[a-z]{2,4})?)(\d{1,5})?(\/(\S*))?/i';
    //private $linkRegExp = '/(([^:\/#\s\.\?,!:;"«»()а-я]+):\/\/)?([^\/?#\s\?,!:;"«»()а-я]+\.[^\/?#\s\.а-я]{2,4})((\/[\S]*)?[^\s\.\?,!:;"«»()]+)?/i';
    public $linkRegExp = '/(([^:\/#\s\.\?,!:;"«»()а-я]+):\/\/)?(([^\/?#\s\?.,!:;"«»()а-я]+\.)+[a-z]{2,4})((\/[\S]*)?[^\s\.\?,!:;"«»()]+)?/i';
    
    private $imageLinkRegExp = '(([^:\/?#\sа-я]+):\/\/)?([^\/?#\sа-я]+\.[^\/?#\s\.а-я]{1,4})(\/[\S]*)(gif|jpg|jpeg|png|bmp)';
    
    private $youTubeRegExp = '/(http\:\/\/)?www\.youtube\.com\/watch\?v=(\S{11})(&\S+)?/i';
    private $youTubeRuRegExp = '/(http\:\/\/)?ru\.youtube\.com\/watch\?v=(\S{11})(&\S+)?/i';
    private $ruTubeRegExp = '/(http\:\/\/)?rutube\.ru\/tracks\/\d+.html\?v=([a-f0-9]{32})/i';
    private $vimeoRegExp = '/(http\:\/\/)?vimeo\.com\/(\d+)(\?\S+)?/i';
    private $smotriRegExp = '/(http\:\/\/)?smotri\.com\/video\/view\/\?id=v(\S{10})/i';
    
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
    
    

    public function getYouTubePageVideo($videoId)
    {
      return 'http://ru.youtube.com/watch?v=' . $videoId;  
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

    private function typo_tag_encode($match)
    {
      return '<'.base64_encode($match[1]).'>';
    }

    private function typo_tag_decode($match)
    {
      return '<'.base64_decode($match[1]).'>';
    }

    private function typo_savetag_encode($match)
    {
      return '<%'.base64_encode('%'.$match[1]).'>';
    }
    
    private function typo_savetag_decode($match)
    {  $t=base64_decode($match[1]);
      if($t[0]!='%')return '<%'.$match[1].'>';
      return '<'.substr($t,1).'>';
    }


    private function typo_nbsp($match)
    {
      $match_t=trim(preg_replace('/<[^>]+>/u','',$match[0]));
      //if(substr($match_t,-1,1)=='.')return $match[0];
      $match_t=preg_replace('/[\s\()-]/u','',$match_t);

      $t=mb_strlen($match_t);
      if($t>0&&$t<4)$match[0]=$match[1].'&nbsp;';

      return $match[0];
    }

    private function typo($text,$settings='none')
    {

      if($text=='')return '';

      $config=array(
        'cleen_utf'=>true,
      );

      if($settings!='none')$config=$settings+$config;

      $spec_chars_normalaize=array(
      '&quot;'=>'"',
      '&#34;'=>'"',
      '&#034;'=>'"',

      '&#39;'=>"'",
      '&#039;'=>"'",

      '&#160;'=>'&nbsp;',
      '&#xA0;'=>'&nbsp;',
      chr(194).chr(160)=>'&nbsp;',

      '&mdash;'=>'&#151;',
      chr(226).chr(128).chr(148)=>'&#151;',

      '«'=>'&laquo;',
      '»'=>'&raquo;',
      '„'=>'&bdquo;',
      '”'=>'&rdquo;',
      '“'=>'&ldquo;',
      "‘"=>'&lsquo;',
      "’"=>'&rsquo;',
      );

      $spec_chars_good=array(
      '&quot;'=>'"',
      '&#34;'=>'"',
      '&#034;'=>'"',

      '&#39;'=>"'",
      '&#039;'=>"'",

      '&lsquo;'=>"‘",
      '&rsquo;'=>"’",

      '&ldquo;'=>'“',
      '&#147;'=>'“',
      '&#x93;'=>'“',

      '&rdquo;'=>'”',
      '&#148;'=>'”',
      '&#x94;'=>'”',

      '&bdquo;'=>'„',

      '&mdash;'=>chr(226).chr(128).chr(148),
      '&#151;'=>chr(226).chr(128).chr(148),


      '&laquo;'=>'«',
      '&#171;'=>'«',
      '&#xAB;'=>'«',

      '&raquo;'=>'»',
      '&#187;'=>'»',
      '&#xBB;'=>'»',

      //'&nbsp;'=>chr(194).chr(160),
      '&#160;'=>chr(194).chr(160),
      '&#xA0;'=>chr(194).chr(160),

      //'&#8209;'=>chr(226).chr(128).chr(145),
      //'-'=>chr(226).chr(128).chr(145),

      '&copy;'=>'©',
      '&#169;'=>'©',
      '&reg;'=>'®',
      '&#174;'=>'®',
      '&trade;'=>'™',
      '&#153;'=>'™',
      '&hellip;'=>'...',
      );



      $symbols = array(
      '(c)'=>'&#169;',
      '(r)'=>'&#174;',
      '(tm)'=>'&#153;',
      '(C)'=>'&#169;',
      '(R)'=>'&#174;',
      '(TM)'=>'&#153;',
      '...'=>'&hellip;'
      );

      //Сохраняем нужное
      $text=preg_replace_callback('/<((script|style|code|save)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $text);
      $text=preg_replace_callback('/<([^%][^>]*)>/us', 'typo_tag_encode', $text);
      $text=strtr($text,$symbols);
      $text=strtr($text,$spec_chars_normalaize);

      //Кавычки


      $text=preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', ' '.$text.' '); //russian
      $text=preg_replace('/([^\w])"([^"]*\d"[^"]+)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
      $text=preg_replace('/([^\w])"([^"]*[^\d])"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian
      $text=preg_replace('/([^\w])"([^"]*)"([^\w])/Usu', '\1&laquo;\2&raquo;\3', $text); //russian

      $text=preg_replace('/(&laquo;)\s+/Uus', '\1', $text);
      $text=preg_replace('/\s+(&raquo;)/Uus', '\1', $text);

      //$text=preg_replace('/&laquo;(.*)&laquo;(.*)&raquo;(.*)&raquo;/Usu', '&laquo;\1&bdquo;\2&ldquo;\3&raquo;', $text); //russian

      $text=preg_replace('/([^\w])\'([^\']*)\'([^\w])/Usu', '\1&lsquo;\2&rsquo;\3', $text);


      //Пробелы у пунктуации - иногда лучше отключать
      $text = preg_replace('/\s+([\.,;:\!\?])(\s+)/u','\1\2', $text);

      $text=trim($text);


      //Много тире
      $text = preg_replace('/\s*-{2,3}\s*/us','&nbsp;&#151; ', $text);


      //Длинное тире
      $text = preg_replace('/\s+-\s+/us','&nbsp;&#151; ', $text);
      if($text[0]=='-'&&$text[1]==' ')$text='&#151;'.substr($text,1);

      //Короткие слова
      $text=preg_replace('/\s+(\w{1,3}($|\.))/u', '&nbsp;\1', $text);

      //Удаляем лишние пробелы
      $text = preg_replace('/\s*&nbsp;\s*/u','&nbsp;', $text);
      $text = str_replace(' &#151;','&nbsp;&#151;', $text);
      $text = str_replace(' -','&nbsp;-', $text);

      //language part
      //back nbsp
      $text=preg_replace('/\s+(бы|ли|же)([\s\W])/u', '&nbsp;\1\2', $text);

      if($config['cleen_utf'])
      $text=strtr($text,$spec_chars_good);

      //nbsp
      $text=preg_replace_callback('/([^\s]+)[ \t]+/u', 'typo_nbsp', $text);

      if($config['cleen_utf'])
      //$text=str_replace('&nbsp;',chr(194).chr(160),$text);


      //------------------------------------------------------------------------------
      //Восстанавливаем нужное1
      $text=preg_replace_callback('/<([^%][^>]*)>/u', 'typo_tag_decode', $text);

      //вынос кавычек из ссылок
      $text=preg_replace('/<a([^>]+)>«([^<]+)»<\/a>/usi', '«<a\1>\2</a>»', $text);

      //Восстанавливаем нужное2
      $text=preg_replace_callback('/<%([^>]+)>/u', 'typo_savetag_decode', $text);

      return $text;
    }
    
    private function post_typo($text)
    {
      
      //Сохраняем нужное
      $text=preg_replace_callback('/<((script|style|code|save|nobr)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $text);
      $text=preg_replace_callback('/<([^%][^>]*)>/su', 'typo_tag_encode', $text);

      //непеносимый дефиз
      $text=preg_replace('/[A-zА-я0-9]+-[A-zА-я0-9]+/u', '<nobr class="typo">\0</nobr>', $text);
      $text=preg_replace_callback('/<((nobr)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $text);

      //вынос
      $text=preg_replace('/(\s|&nbsp;|'.chr(194).chr(160).')*(«|&laquo;)/su','<d2JyIGNsYXNzPSJ0eXBvIg==><span class="slaquo-s typo"> </span> <span class="hlaquo-s typo">\2</span>',$text);
      $text=preg_replace('/(\s|&nbsp;|'.chr(194).chr(160).')*(„|&bdquo;)/su','<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbdquo typo"> </span> <span class="hbdquo typo">\2</span>',$text);
      $text=preg_replace('/(\s|&nbsp;|'.chr(194).chr(160).')*\(/su','<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbrace typo"> </span> <span class="hbrace typo">(</span>',$text);

      if(substr($text,0,63)=='<d2JyIGNsYXNzPSJ0eXBvIg==><span class="slaquo-s typo"> </span> ')$text=substr($text,63);
      if(substr($text,0,61)=='<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbrace typo"> </span> ')$text=substr($text,61);
      if(substr($text,0,61)=='<d2JyIGNsYXNzPSJ0eXBvIg==><span class="sbdquo typo"> </span> ')$text=substr($text,61);

      $text=preg_replace_callback('/<((span)[^>]*>.+<\/\2)>/Uus', 'typo_savetag_encode', $text);

      //Восстанавливаем нужное
      $text=preg_replace_callback('/<([^%][^>]*)>/u', 'typo_tag_decode', $text);
      $text=preg_replace_callback('/<%([^>]+)>/u', 'typo_savetag_decode', $text);

      $text=preg_replace('/(<(p|br)[^>]*>(\s|&nbsp;|'.chr(194).chr(160).')*)<wbr class="typo"><span class="slaquo-s typo">(\s|&nbsp;)<\/span> /ius','\1',$text);
      $text=preg_replace('/(<(p|br)[^>]*>(\s|&nbsp;|'.chr(194).chr(160).')*)<wbr class="typo"><span class="sbrace typo">(\s|&nbsp;)<\/span> /ius','\1',$text);
      $text=preg_replace('/(<(p|br)[^>]*>(\s|&nbsp;|'.chr(194).chr(160).')*)<wbr class="typo"><span class="sbdquo typo">(\s|&nbsp;)<\/span> /ius','\1',$text);

      return $text;
    }


    private function unpost_typo($text)
    {
      $text=preg_replace('/<nobr class="typo">(.+)<\/nobr>/Uu','\1',$text);

      $text=str_replace('<span class="hbrace typo">(</span>', '(', $text);

      $text=str_replace('<span class="hlaquo-s typo">«</span>', '«', $text);
      $text=str_replace('<span class="hlaquo-s typo">&laquo;</span>', '&laquo;', $text);

      $text=str_replace('<span class="hbdquo typo">„</span>', '„', $text);
      $text=str_replace('<span class="hbdquo typo">&bdquo;</span>', '&bdquo;', $text);

      $text=str_replace('<wbr class="typo"><span class="slaquo-s typo"> </span> ', ' ', $text);
      $text=str_replace('<wbr class="typo"><span class="sbrace typo"> </span> ', ' ', $text);
      $text=str_replace('<wbr class="typo"><span class="sbdquo typo"> </span> ', ' ', $text);
      /**/
      return $text;
    }
  }
?>