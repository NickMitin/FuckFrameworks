<?php
  
  abstract class bmCustomTextProcessor extends bmFFObject {
    
    public function declineNumber($value, $strings)
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
    
  }
  
?>
