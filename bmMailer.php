<?php                              
  
  class bmMailer extends bmFFObject
  { 
    public function send($subject, $messages)
    {               
      if (count($messages) > 0)
      {
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $header = "Content-type: text/plain; charset=utf-8";
        
        foreach ($messages as $email => $message)
        {
          if ($this->validate($email))
          {
            if (mail($email, $subject, $message, $header))
            {
              return true;
            }
            else
            {
              return false;
            }
          }
        }
      }
    }
       
    private function validate($address)
    {
      return (filter_var($address, FILTER_VALIDATE_EMAIL));    
    }
  }

?>