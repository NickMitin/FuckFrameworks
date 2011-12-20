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

		
	class bmErrorHandler extends bmFFObject
	{
		protected $stack = array();
		protected $errors = array();
		
		public function __construct($application, $parameters = array())
		{
			parent::__construct($application, $parameters);
			require_once(projectRoot . '/locale/' . $application->locale . '/error_messages.php');
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
