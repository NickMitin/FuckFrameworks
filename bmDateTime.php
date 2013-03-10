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
    
    public function format($format, $locale = 'en_EN')
    {
      if ($locale == 'ru_RU')
      {
        switch ($format)
        {
          case 'j F Y?':
            return $this->toGenericRussianDateFormat();
          break;
          case 'j F Y':
            return $this->toGenericRussianDateFormat(true);
          break;
          case 'j? G I':
            return $this->toGenericRussianTimeFormat();
          break;
          case 'j F Y? at H:i':
            return $this->toGenericRussianDateTimeFormat();
          break;
          default:
            throw new Exception('Такой формат пока не поддерживается');
          break;
        }       
      }
      else
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
    }
    
    public function formatHumanOld()
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
          $minute = date('i', $date);
          /*$hourString = 'ночью';
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
          }*/
          $hourString = ' в ' . $hour . ':' . $minute;
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
    
    public function formatHumanSho()
    {
      
      $result = '';
      $date = $this->dateTime->format('U');
      $yearMonths = $yearMonths = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
      $days = array('Cегодня', 'Вчера');
      $today = time();
      
      $todayInfo = getdate($today);
      $dateInfo = getdate($date);
      $this->fixSunday($todayInfo);
      $this->fixSunday($dateInfo);
      
      $dayOffset = $todayInfo['yday'] - $dateInfo['yday'];
      $yearOffset = $todayInfo['year'] - $dateInfo['year'];
       $hour = date("H", $date);
       $minute = date('i', $date);
       $hourString = ' в ' . $hour . ':' . $minute;
       $hourString = '';
      if ($today < $date) {
        
        $result = 'В будущем';
        
      } else {
        if ($dayOffset < 2) {
          $result = $days[$dayOffset] . ' ' . $hourString;
        } else {
        	$year = $yearOffset > 0 ? ' ' . $dateInfo['year'] : '';
          $result = $dateInfo['mday'] . $year . ' ' . $yearMonths[$dateInfo['mon']] . $hourString;
        }
      }
      return $result;
      
    }
    
    private function formatHuman()
    {
      
      $result = '';
      $date = $this->dateTime->format('U');      
      $yearMonths = array(1 => 'января', 'февраля', 'марта', 'апреля', 'мая', 'июня', 'июля', 'августа', 'сентября', 'октября', 'ноября', 'декабря');
      //$weekDays = array(1 => 'в понедельник', 'во вторник', 'в среду', 'в четверг', 'в пятницу', 'в субботу', 'в воскресенье');
      //$weekDays = array(1 => 'в понедельник', 'во вторник', 'в среду', 'в четверг', 'в пятницу', 'в субботу', 'в воскресенье');
      $today = time();
      
      $todayInfo = getdate($today);
      $dateInfo = getdate($date);
      $this->fixSunday($todayInfo);
      $this->fixSunday($dateInfo);
      
      $dayOffset = $todayInfo['yday'] - $dateInfo['yday'];
      $monthOffset = $todayInfo['mon'] - $dateInfo['mon'];
      $yearOffset = $todayInfo['year'] - $dateInfo['year'];
      
      $dateInfo['minutes'] = str_pad($dateInfo['minutes'], 2, '0', STR_PAD_LEFT);
      $now = new DateTime();
      $difference = $now->diff($this->dateTime, true);
      
      $offset = $today - $date;
      if ($offset < 60)
      {
        if ($offset <= 2)
        {
          $result = '2 секунды назад';
        }
        else
        {
          $result = $offset . ' ' . $this->declineNumber($offset, array('секунду', 'секунды', 'секунд')) . ' назад';
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
          $result = $offset . ' ' . $this->declineNumber($offset, array('минуту', 'минуты', 'минут')) . ' назад';
        }
      }
      elseif ($offset < 86400)
      {
        
        if ($dateInfo['mday'] > $todayInfo['mday'])
        {
          $result = 'вчера в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          //$result = 'в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
          $count = floor($offset / 3600);
          $result = $count . ' ' . $this->declineNumber($count, array('час', 'часа', 'часов')) . ' назад';
        }
      }
      elseif ($offset < 604800)
      {
        $offset += $today % 86400;
        //if ($dateInfo['wday'] >= $todayInfo['wday'])
        if ($offset < 172800)
        {
          $result = 'вчера в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          //$result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
          $count = floor($offset / 86400);
          $result = $count . ' ' . $this->declineNumber($count, array('день', 'дня', 'дней')) . ' назад';
        }  
        //$count = floor($offset / 86400);
        //$result = $count . ' ' . $this->declineNumber($count, array('день', 'дня', 'дней')) . ' назад';
      }                                                                        
      else
      {
        if ($yearOffset == 0)
        {
          $result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']]; // . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
        }
        else
        {
          $result = $dateInfo['mday'] . ' ' . $yearMonths[$dateInfo['mon']]; // . ' ' . $dateInfo['year'] . ' в ' . $dateInfo['hours'] . ':' . $dateInfo['minutes'];
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
    
    private function toGenericRussianDateFormat($yearIsMandatory = false)
    {
      $monthsArray = array(1 => 'января',
                           2 => 'февраля', 
                           3 => 'марта', 
                           4 => 'апреля', 
                           5 => 'мая', 
                           6 => 'июня', 
                           7 => 'июля', 
                           8 => 'августа', 
                           9 => 'сентября', 
                           10 => 'октября', 
                           11 => 'ноября', 
                           12 => 'декабря');
                           
      if ($yearIsMandatory)
      {
        $year = ' ' . $this->format('Y');
      }
      else
      {
        $year = date('Y') == $this->format('Y') ? '' : ' ' . $this->format('Y');
      }
      
      $month = $monthsArray[$this->format('n')];
      $day = $this->format('j');
      
      return $day . ' ' . $month . $year;
    }
    
    private function toGenericRussianTimeFormat()
    {
      $daysCount = floor(intval($this->format('U')) / 86400);
      $hoursCount = floor(intval($this->format('U') - $daysCount * 86400) / 3600);
      $minutesCount = floor(intval($this->format('U') - $daysCount * 86400 - $hoursCount * 3600) / 60);
      //$secondsCount = intval((int) $this->format('s'));
      
      $days = $daysCount == 0 ? '' : $daysCount . ' ' . $this->declineNumber($daysCount, array('день', 'дня', 'дней'));
      $hours = $hoursCount == 0 ? '' : $hoursCount . ' ' . $this->declineNumber($hoursCount, array('час', 'часа', 'часов'));
      $minutes = $minutesCount == 0 ? '' : $minutesCount . ' ' . $this->declineNumber($minutesCount, array('минута', 'минуты', 'минут'));
      //$seconds = $secondsCount == 0 ? '' : $secondsCount . ' ' . $this->declineNumber($secondsCount, array('секунда', 'секунды', 'секунд'));
      
      return $days . ' ' . $hours . ' ' . $minutes; // . ' ' . $seconds;
    }
    
    private function toGenericRussianDateTimeFormat($yearIsMandatory = false)
    {
      $date = $this->toGenericRussianDateFormat($yearIsMandatory);
      $date .= ' в '  . $this->format('H:i');
      
      return $date;
    }

  }
?>