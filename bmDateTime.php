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
  
  class bmDateTime
  {
    
    private $value;
    private $dateTime;
    
    public function __construct($time)
    {
      if(is_int($time))
      {
        $time = date(DATE_RFC822, $time);
      }
      $this->dateTime = new DateTime($time);
    }
    
    public function __sleep()
    {
      $this->value = $this->dateTime->format('Y-m-d H:i:s');
      return array('value');
    }
    
    public function __wakeup()
    {
      $this->dateTime = new DateTime($this->value);   
    }
    
    public function __toString()
    {       
      return $this->dateTime->format('Y-m-d H:i:s');
    }
    
    public function getValue()
    {
      return $this->dateTime;
    }
    
    public function format($format)
    {
      if ($format == 'human')
      {
        return $this->formatHuman();
      }
      else
      {
        return $this->dateTime->format($format);
      }
    }
    
    private function formatHumanOld()
    {
      
      $result = '';
      $date = $this->dateTime->format('U');      
      $months = array('в этом месяце', 'в прошлом месяце');
      $yearMonths = array(1 => 'в январе', 'в феврале', 'в марте', 'в апреле', 'в мае', 'в июне', 'в июле', 'в августе', 'в сентябре', 'в октябре', 'в ноябре', 'в декабре');
      $days = array('сегодня', 'вчера', 'позавчера');
      $weekDays = array(1 => 'в понедельник', 'во вторник', 'в среду', 'в четверг', 'в пятницу', 'в субботу', 'в воскресенье');
      $today = time();
      
      $todayInfo = getdate($today);
      $dateInfo = getdate($date);
      $this->fixSunday($todayInfo);
      $this->fixSunday($dateInfo);
      
      $dayOffset = $todayInfo['yday'] - $dateInfo['yday'];
      $monthOffset = $todayInfo['mon'] - $dateInfo['mon'];
      $yearOffset = $todayInfo['year'] - $dateInfo['year'];
      
      if ($today < $date) {
        
        $result = 'В будущем';
        
      } elseif ($yearOffset == 0) {
        if ($dayOffset < 3) {

          
          $hour = date("H", $date);
          $hourString = 'ночью';
          if(($hour >= 6) && ($hour < 11))
          {
            $hourString = 'утром';
          }
          if(($hour >= 11) && ($hour < 17))
          {
            $hourString = 'днем';
          }  
          if(($hour >= 17) && ($hour < 23))
          {
            $hourString = 'вечером';
          }
          $result = $days[$dayOffset] . ' ' . $hourString;
          
        } elseif ($dayOffset < $todayInfo['wday'] + 7) {
        
          if ($dayOffset < $todayInfo['wday']) {
            $result = $weekDays[$dateInfo['wday']];
          } else {
            $result = 'на прошлой неделе';
          }
          
        } elseif ($monthOffset < 2) {
          $result = $months[$monthOffset];
        } else {
          $result = $yearMonths[$dateInfo['mon']];
        }
      } elseif ($yearOffset == 1) {
        $result = 'в прошлом году';
      } else {
        $result = $yearOffset . ' ' . $this->declineNumber($yearOffset, array('год', 'года', 'лет')) . ' назад';
      }
      return $result;
      
    }
    
    private function formatHuman()
    {
      
      $result = '';
      $date = $this->dateTime->format('U');      
      $yearMonths = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
      $weekDays = array(1 => 'в понедельник', 'во вторник', 'в среду', 'в четверг', 'в пятницу', 'в субботу', 'в воскресенье');
      $today = time();
      
      $todayInfo = getdate($today);
      $dateInfo = getdate($date);
      $this->fixSunday($todayInfo);
      $this->fixSunday($dateInfo);
      
      $dayOffset = $todayInfo['yday'] - $dateInfo['yday'];
      $monthOffset = $todayInfo['mon'] - $dateInfo['mon'];
      $yearOffset = $todayInfo['year'] - $dateInfo['year'];
      
      $offset = $today - $date;
      if ($offset < 60)
      {
        if ($offset <= 2)
        {
          $result = '2 секунды назад';
        }
        else
        {
          $result = $offset . ' ' . $this->declineNumber($offset, array('секунду', 'секунды', 'секунд'));
        }
      }
      elseif ($offset < 3600)
      {
        $offset = floor($offset / 60);
        if ($offset == 1)
        {
          $result = 'минуту назад';
        }
        else
        {
          $result = $offset . ' ' . $this->declineNumber($offset, array('минуту', 'минуты', 'минут'));
        }
      }
      elseif ($offset < 86400)
      {
        if ($dateInfo['mday'] >= $today['mday'])
        {
          $result = 'вчера в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          $result = 'в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
      }
      elseif ($offset < 604800)
      {
        if ($dateInfo['wday'] >= $today['wday'])
        {
          $result = $weekDays[$dateInfo['wday']] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          $result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
      }
      else
      {
        if ($yearOffset == 0)
        {
          $result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          $result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']] . ' ' . $dateInfo['year'] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
      }
      
      return $result;
      
    }
    
    private function declineNumber($value, $strings)
    {
      
      if($value > 100) {
        $value = $value % 100;
      }
      
      $firstDigit = $value % 10;
      $secondDigit = floor($value / 10);
      
      if ($secondDigit != 1) {
        if ($firstDigit == 1) {
          return $strings[0];
        } else if ($firstDigit > 1 && $firstDigit < 5) {
          return $strings[1];
        } else {
          return $strings[2];
        }
      } else {
        return $strings[2];
      }

    }
    
    private function fixSunday(&$dateInfo)
    {
      if ($dateInfo['wday'] == 0) {
        $dateInfo['wday'] = 7;
      }
    }

  }
?>