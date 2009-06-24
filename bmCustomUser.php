<?php
/**
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
		
	abstract class bmCustomUser extends bmDataObject
	{  

		public function __construct($application, $parameters)
		{                                                
      
      $this->objectName = 'user';
      
      $this->map = array_merge($this->map, array
      (
        'identifier' => array(
          'fieldName' => 'id',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => C_DEFAULT_USER_ID
        ),
        'passwordHash' => array(
          'fieldName' => 'password',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'email' => array(
          'fieldName' => 'email',
          'dataType' => BM_VT_STRING,
          'defaultValue' => 'guest@bricks'
        ),
        'homePage' => array(
          'fieldName' => 'homePage',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'avatar' => array(
          'fieldName' => 'avatar',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'name' => array(
          'fieldName' => 'name',
          'dataType' => BM_VT_STRING,
          'defaultValue' => 'Гость'
        ),
        'firstName' => array(
          'fieldName' => 'firstname',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'lastName' => array(
          'fieldName' => 'lastname',
          'dataType' => BM_VT_STRING,
          'defaultValue' => ''
        ),
        'birthday' => array(
          'fieldName' => 'birthday',
          'dataType' => BM_VT_STRING,
          'defaultValue' => '0000-00-00'
        ),
        'sex' => array(
          'fieldName' => 'sex',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'offset' => array(
          'fieldName' => 'offset',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'type' => array(
          'fieldName' => 'type',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'timeOnline' => array(
          'fieldName' => 'timeOnline',
          'dataType' => BM_VT_INTEGER,
          'defaultValue' => 0
        ),
        'joinDate' => array(
          'fieldName' => 'joinDate',
          'dataType' => BM_VT_STRING,
          'defaultValue' => '0000-00-00 00:00:00'
        ),
        'lastActivity' => array(
          'fieldName' => 'lastActivity',
          'dataType' => BM_VT_STRING,
          'defaultValue' => '0000-00-00 00:00:00'
        ),
        'lastVisit' => array(
          'fieldName' => 'lastVisit',
          'dataType' => BM_VT_STRING,
          'defaultValue' => '0000-00-00 00:00:00'
        )
      ));
      
      parent::__construct($application, $parameters);
		}
  
	}  
?>