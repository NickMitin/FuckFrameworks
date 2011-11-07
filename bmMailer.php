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
    
  class bmMailer extends bmFFObject
  { 
    
    private $queue = array();
    
    private $contexts = array();
    
    private function getMainContext()
    {
      foreach ($this->contexts as $key => $context)
      {
        if ($context->type == 'main')
        {
          return $key;
        }
      }
    }
    
    public function __construct($application, $parameters = array())
    {
      parent::__construct($application, $parameters);
      require_once(projectRoot . '/conf/mailer.conf');
    }
    
    public function addToQueue($subject, $to, $message, $context)
    {
      $mail = new bmMail($this->application, array('subject' => $subject, 'to' => $to, 'context' => $context, 'message' => $message));
    }
    
    public function send($subject, $messages, $context = 'default')
    {               
      
      
      if (!array_key_exists($context, $this->contexts))
      {
        return;
      }
      if (count($messages) > 0)
      {
        
        require_once('Mail.php');

        $context = $this->contexts[$context];
        $subject = '=?UTF-8?B?' . base64_encode($subject) . '?=';
        $smtp = null;
        
        if (class_exists('Mail', false))
        {
          $smtp = Mail::factory('smtp', array ('host' => $context['host'], 'port' => $context['port'], 'auth' => $context['authorization'], 'username' => $context['username'], 'password' => $context['password'], 'debug' => true));
        }

        foreach ($messages as $email => $message)
        {
          if ($this->validate($email))
          {
            $senderName = '';
            if ($smtp != null)
            {
              $senderName = $senderName == '' ? $context['senderName'] : $senderName;
              $sender = $context['senderEmail'];
              if ($senderName != '')
              {
                $sender = '=?UTF-8?B?' . base64_encode($senderName) . '?= <' . $sender . '>';
              }
              $headers = array ('From' => $sender, 'To' => $email, 'Subject' => $subject, 'Content-type' => 'text/html; charset=utf-8');
              $mail = $smtp->send($email, $headers, $message);
            } 
            elseif (mail($email, $subject, $message, 'Content-type: text/html; charset=utf-8'))
            {
            }
            else
            {
            }
          }
        }
        unset($smtp);
      }
    }
       
    private function validate($address)
    {
      return (filter_var($address, FILTER_VALIDATE_EMAIL));    
    }
    
    public function processQueue()
    {
      $context = $this->getMainContext();
      
      $sql = "SELECT `id` FROM `mail` WHERE `context` = '" . $context . "' LIMIT 1";
      $id = $this->application->dataLink->getValue($sql);
      if ($id == null)
      {
        $sql = 'SELECT `id` FROM `mail` WHERE 1 LIMIT 1';
        $id = $this->application->dataLink->getValue($sql);
      }
      if ($id > 0)
      {
        $mail = new bmMail($this->application, array('identifier' => $id));
        $this->send($mail->subject, array($mail->to => $mail->message), $mail->context);
        $mail->delete();
        unset($mail);
      }
    }
  }

?>