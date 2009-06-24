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
  abstract class bmCustomSession extends bmDataObject implements IDataObject 
  {
    
    public function __construct($application, $parameters = array()) 
    {
      $this->map = array_merge($this->map, array
      (
        'identifier' => array
        (
          'fieldName' => 'id',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'ipAddress' => array
        (
          'fieldName' => 'ipAddress',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'userId' => array
        (
          'fieldName' => 'userId',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => C_DEFAULT_USER_ID
        ),
        'userAgent' => array
        (
          'fieldName' => 'userAgent',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'location' => array
        (
          'fieldName' => 'location',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'createTime' => array
        (
          'fieldName' => 'createTime',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'lastActivity' => array
        (
          'fieldName' => 'lastActivity',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'lastVisit' => array
        (
          'fieldName' => 'lastVisit',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        )
      ));
      
      
      $load = array_key_exists('load', $parameters);
      $identifier = array_key_exists('identifier', $parameters) ? $parameters['identifier'] : '';
      
      if ($load && $identifier == '')
      {
        $parameters['load'] = false;
      }      

      parent::__construct($application, $parameters);
      
      if ( ($load) && ($parameters['load']) )
      {          
        if ( ($this->application->errorHandler->getLast() != E_SUCCESS) )
        {        
          $sql = "SELECT 
                    `link_user_session`.`userId` AS `userId`,
                    `link_user_session`.`sessionHash` AS `sessionId`
                  FROM 
                    `link_user_session`
                  JOIN 
                    `user` ON (`link_user_session`.`userId` = `user`.`id`)
                  WHERE 
                    `link_user_session`.`sessionHash` = '" . $this->application->dataLink->formatInput($this->identifier) . "'";
          $oldSession = $this->application->dataLink->getObject($sql);        
          if ($oldSession)
          {
            $this->userId = intval($oldSession->userId);
            $this->ipAddress = $this->application->cgi->getIPAddress();
            $this->createTime = time();
          }
          else
          {
            $this->identifier = md5(uniqid(microtime(true), true));
            $this->userId = C_DEFAULT_USER_ID;
            $this->createTime = time();
          }
        }
      }
      else
      {
        $this->identifier = md5(uniqid(microtime(true), true));
        $this->userId = C_DEFAULT_USER_ID;
        $this->createTime = time();        
      }      
      
      $this->ipAddress = $this->application->cgi->getIPAddress();
      $this->userAgent = $_SERVER['HTTP_USER_AGENT'];
      $this->location = $_SERVER['REQUEST_URI'];
      $this->lastActivity = time();
      $this->application->user = new bmUser($this->application, array('identifier' => $this->properties['userId'], 'load' => true));
    }   
  }

?>
