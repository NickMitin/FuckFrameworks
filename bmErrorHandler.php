<?php
	
	class bmErrorHandler extends bmFFObject
	{
		protected $stack = array();
		protected $errors = array();
		
		public function __construct($application, $parameters = array())
		{
			parent::__construct($application, $parameters);
			require_once(projectRoot . '/lib/error.php');
			require_once(projectRoot . '/lib/error_message_ru.php');
		}
		
		public function add($errorNumber)
		{
      if (count($this->stack) > 20)
      {
        array_shift($this->stack);
      }
			$this->stack[] = $errorNumber;
		}
		
		public function getLast()
		{
			$output = E_SUCCESS;
			if (count($this->stack) > 0)
			{
				$output = $this->stack[count($this->stack) - 1];
			}
			return $output;
		}
		
		public function getMessage($errorCode = null)
		{
			if ($errorCode === null)
			{
				$errorCode = $this->getLast();
			}      
			return $this->errors[$errorCode];
		}
		
		public function stackToString()
		{
			$output = '';
			foreach ($this->stack as $error)
			{
				if ($error !== E_WEBORAMA_SUCCESS)
				{
					$output .= $error . ' - ' . $this->errors[$error] . "\n";
				}
			}
			return $output;
		}
		
	}
	
?>
