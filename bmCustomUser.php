<?php
	
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