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
	
  define('STEMMER_VOWEL_RU', '/аеиоуыэюя/u');
  define('STEMMER_PERFECTIVEGROUND_RU', '/((ив|ивши|ившись|ыв|ывши|ывшись)|((?<=[ая])(в|вши|вшись)))$/u');
  define('STEMMER_REFLEXIVE_RU', '/(с[яь])$/u');
  define('STEMMER_ADJECTIVE_RU', '/(ее|ие|ые|ое|ими|ыми|ей|ий|ый|ой|ем|им|ым|ом|его|ого|ему|ому|их|ых|ую|юю|ая|яя|ою|ею)$/u');
  define('STEMMER_PARTICIPLE_RU', '/((ивш|ывш|ующ)|((?<=[ая])(ем|нн|вш|ющ|щ)))$/u');
  define('STEMMER_VERB_RU', '/((ила|ыла|ена|ейте|уйте|ите|или|ыли|ей|уй|ил|ыл|им|ым|ен|ило|ыло|ено|ят|ует|уют|ит|ыт|ены|ить|ыть|ишь|ую|ю)|((?<=[ая])(ла|на|ете|йте|ли|й|л|ем|н|ло|но|ет|ют|ны|ть|ешь|нно)))$/u');
  define('STEMMER_NOUN_RU', '/(а|ев|ов|ие|ье|е|иями|ями|ами|еи|ии|и|ией|ей|ой|ий|й|иям|ям|ием|ем|ам|ом|о|у|ах|иях|ях|ы|ь|ию|ью|ю|ия|ья|я)$/u');
  define('STEMMER_RVRE_RU', '/^(.*?[аеиоуыэюя])(.*)$/u');
  define('STEMMER_DERIVATIONAL_RU', '/[^аеиоуыэюя][аеиоуыэюя]+[^аеиоуыэюя]+[аеиоуыэюя].*(?<=о)сть?$/u');
  
  define('STEMMER_CONSONANT_EN', '(?:[bcdfghjklmnpqrstvwxz]|(?<=[aeiou])y|^y)');
  define('STEMMER_VOWEL_EN', '(?:[aeiou]|(?<![aeiou])y)');

  class bmStemmer extends bmFFObject  
  {

    private $stemCache = array();
    
    public function __construct($application, $parameters = null)
    {
      
      parent::__construct($application, $parameters);
      $this->stemCache = $this->application->cacheLink->get('stemCache');
      if ($this->stemCache === false)
      {
        $this->stemCache = array();
      }
      
    }
    
    public function __destruct()
    {
      $this->application->cacheLink->set('stemCache', $this->stemCache, BM_CACHE_LONG_TTL);
    }
  
    private function testReplace(&$source, $expression, $replacement)
    {
      $original = $source;

      $source = preg_replace($expression, $replacement, $source);

      return $original !== $source;

    }

    public function stemRussianWord($word)
    {
      
      $word = preg_replace('/[\d]+/Su', '', $word);
      $word = preg_replace('/.*(ё).*/u', 'e', $word);

      $stem = $word;

      if (preg_match(STEMMER_RVRE_RU, $word, $matches)) 
      {

        $start = $matches[1];
        $RV = $matches[2];

        if ($RV != '')
        {

          if (!$this->testReplace($RV, STEMMER_PERFECTIVEGROUND_RU, ''))
          {
            $this->testReplace($RV, STEMMER_REFLEXIVE_RU, '');

            if ($this->testReplace($RV, STEMMER_ADJECTIVE_RU, ''))
            {
              $this->testReplace($RV, STEMMER_PARTICIPLE_RU, '');
            }
            else 
            {
              if (!$this->testReplace($RV, STEMMER_VERB_RU, ''))
              {
                $this->testReplace($RV, STEMMER_NOUN_RU, '');
              }
            }
          }

          $this->testReplace($RV, '/и$/u', '');

          if ($this->matchRU($RV, STEMMER_DERIVATIONAL_RU))
          {
            $this->testReplace($RV, '/ость?$/u', '');
          }

          if (!$this->testReplace($RV, '/ь$/u', '')) 
          {
            $this->testReplace($RV, '/ейше?/u', '');
            $this->testReplace($RV, '/нн$/u', 'н');
          }



          $stem = $start . $RV;

          
        }
      }
      $this->stemCache[$word] = $stem;
      return $stem;

    }
 
    public function stemEnglishWord($word)
    {
      $word = preg_replace('/[\d]+/Su', '', $word);  
      if (mb_strlen($word) <= 2) {
        return $word;
      }

      if (mb_substr($word, -1) == 's') {        
        $word = preg_replace(array('/sses$/u', '/ies$/u', '/ss$/u', '/s$/u'), array('ss', 'i', 'ss', ''), $word);
      }

      if ((mb_substr($word, -2, 1) != 'e') || (!$this->replaceEN($word, 'eed', 'ee', 0))) 
      {
        if (preg_match('/' . STEMMER_VOWEL_EN . '+/u', mb_substr($word, 0, -3)) && $this->replaceEN($word, 'ing', '') or preg_match('/' . STEMMER_VOWEL_EN . '+/u', mb_substr($word, 0, -2)) && $this->replaceEN($word, 'ed', ''))
        {          
          if (!$this->replaceEN($word, 'at', 'ate') && !$this->replaceEN($word, 'bl', 'ble') && !$this->replaceEN($word, 'iz', 'ize')) 
          {
            if ($this->doubleConsonant($word) && (mb_substr($word, -2) != 'll') && (mb_substr($word, -2) != 'ss') && (mb_substr($word, -2) != 'zz'))
            {
              $word = mb_substr($word, 0, -1);

            } 
            else if ($this->matchEN($word) == 1 && $this->cvc($word)) 
            {
              $word .= 'e';
            }
          }
        }
      }

      if ((mb_substr($word, -1) == 'y') && preg_match('/' . STEMMER_VOWEL_EN . '+/u', mb_substr($word, 0, -1))) {
        $this->replaceEN($word, 'y', 'i');
      }

      switch (mb_substr($word, -2, 1)) {
        case 'a':
          $this->replaceEN($word, 'ational', 'ate', 0);
          $this->replaceEN($word, 'tional', 'tion', 0);
        break;
        case 'c':
          $this->replaceEN($word, 'enci', 'ence', 0);
          $this->replaceEN($word, 'anci', 'ance', 0);
        break;
        case 'e':
          $this->replaceEN($word, 'izer', 'ize', 0);
        break;
        case 'g':
          $this->replaceEN($word, 'logi', 'log', 0);
        break;
        case 'l':
          $this->replaceEN($word, 'entli', 'ent', 0);
          $this->replaceEN($word, 'ousli', 'ous', 0);
          $this->replaceEN($word, 'alli', 'al', 0);
          $this->replaceEN($word, 'bli', 'ble', 0);
          $this->replaceEN($word, 'eli', 'e', 0);
        break;
        case 'o':
          $this->replaceEN($word, 'ization', 'ize', 0);
          $this->replaceEN($word, 'ation', 'ate', 0);
          $this->replaceEN($word, 'ator', 'ate', 0);
        break;
        case 's':
          $this->replaceEN($word, 'iveness', 'ive', 0);
          $this->replaceEN($word, 'fulness', 'ful', 0);
          $this->replaceEN($word, 'ousness', 'ous', 0);
          $this->replaceEN($word, 'alism', 'al', 0);
        break;
        case 't':
          $this->replaceEN($word, 'biliti', 'ble', 0);
          $this->replaceEN($word, 'aliti', 'al', 0);
          $this->replaceEN($word, 'iviti', 'ive', 0);
        break;
      }
      
      switch (mb_substr($word, -2, 1))
      {
        case 'a':
          $this->replaceEN($word, 'ical', 'ic', 0);
        break;
        case 's':
          $this->replaceEN($word, 'ness', '', 0);
        break;
        case 't':
          $this->replaceEN($word, 'icate', 'ic', 0);
          $this->replaceEN($word, 'iciti', 'ic', 0);
        break;
        case 'u':
          $this->replaceEN($word, 'ful', '', 0);
        break;
        case 'v':
          $this->replaceEN($word, 'ative', '', 0);
        break;
        case 'z':
          $this->replaceEN($word, 'alize', 'al', 0);
        break;
      }
      
      switch (mb_substr($word, -2, 1)) {
        case 'a':
          $this->replaceEN($word, 'al', '', 1);
        break;
        case 'c':
          $this->replaceEN($word, 'ance', '', 1);
          $this->replaceEN($word, 'ence', '', 1);
        break;
        case 'e':
          $this->replaceEN($word, 'er', '', 1);
        break;
        case 'i':
          $this->replaceEN($word, 'ic', '', 1);
        break;
        case 'l':
          $this->replaceEN($word, 'able', '', 1);
          $this->replaceEN($word, 'ible', '', 1);
        break;

        case 'n':
          $this->replaceEN($word, 'ant', '', 1);
          $this->replaceEN($word, 'ement', '', 1);
          $this->replaceEN($word, 'ment', '', 1);
          $this->replaceEN($word, 'ent', '', 1);
        break;

        case 'o':
          if (mb_substr($word, -4) == 'tion' || mb_substr($word, -4) == 'sion') 
          {
            $this->replaceEN($word, 'ion', '', 1);
          }
          else
          {
            $this->replaceEN($word, 'ou', '', 1);
          }
        break;
        case 's':
          $this->replaceEN($word, 'ism', '', 1);
        break;
        case 't':
          $this->replaceEN($word, 'ate', '', 1);
          $this->replaceEN($word, 'iti', '', 1);
        break;
        case 'u':
          $this->replaceEN($word, 'ous', '', 1);
        break;
        case 'v':
          $this->replaceEN($word, 'ive', '', 1);
        break;
        case 'z':
          $this->replaceEN($word, 'ize', '', 1);
        break;
      }
      
      if (mb_substr($word, -1) == 'e') 
      {
        if ($this->matchEN(mb_substr($word, 0, -1)) > 1)
        {
          $this->replaceEN($word, 'e', '');
        } 
        else if ($this->matchEN(mb_substr($word, 0, -1)) == 1)
        {
          if (!$this->cvc(mb_substr($word, 0, -1))) 
          {
            $this->replaceEN($word, 'e', '');
          }
        }
      }
      
      if ($this->matchEN($word) > 1 AND $this->doubleConsonant($word) AND mb_substr($word, -1) == 'l') 
      {
        $word = mb_substr($word, 0, -1);
      }
      return $word;
    }
    
    public function stemWord($word) {
      
      $word = mb_strtolower($word);    
      
      if (array_key_exists($word, $this->stemCache))
      {
        return $this->stemCache[$word];
      }
      else if (preg_match('/\d/', $word))
      {
        return $word;
      }
      else if (preg_match('/[А-Яа-яЁё]/u', $word))
      {
        return $this->stemRussianWord($word);
      }
      else
      {
        return $this->stemEnglishWord($word);
      }
    
    }

    private function replaceEN(&$subject, $search, $replacement, $count = null)
    {
      $length = 0 - mb_strlen($search);

      if (mb_substr($subject, $length) == $search) {
        $substr = mb_substr($subject, 0, $length);
        if (is_null($count) || $this->matchEN($substr) > $count) {
          $subject = $substr . $replacement;
        }
        return true;
      }
      return false;
    }

    private function matchRU($subject, $expression)
    {
      return preg_match($expression, $subject);
    }

    private function matchEN($subject)
    {

      $subject = preg_replace('/^' . STEMMER_CONSONANT_EN . '+/u', '', $subject);
      $subject = preg_replace('/' . STEMMER_VOWEL_EN . '+$/u', '', $subject);
      
      preg_match_all('/(' . STEMMER_VOWEL_EN . '+' . STEMMER_CONSONANT_EN . '+)/u', $subject, $matches);

      return count($matches[1]);
    }

    private function doubleConsonant($subject)
    {
      return preg_match('/' . STEMMER_CONSONANT_EN . '{2}$/u', $subject, $matches) && ($matches[0]{0} == $matches[0]{1});
    }

    private function cvc($subject)
    {
      return preg_match('/(' . STEMMER_CONSONANT_EN . STEMMER_VOWEL_EN . STEMMER_CONSONANT_EN . ')$/', $subject, $matches) && (strlen($matches[1]) == 3) && ($matches[1]{2} != 'w') && ($matches[1]{2} != 'x') && ($matches[1]{2} != 'y');
    }

  }

?>

