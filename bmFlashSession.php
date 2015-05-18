<?php

/**
 * Created by PhpStorm.
 * User: vir-mir
 * Date: 08.08.14
 * Time: 15:31
 */
trait bmFlashSession
{

	public function setFlash($name, $value)
	{
		$this->application->cgi->addCookie('flash_session_' . $name, serialize($value), true, '/', '', time() + 10);
	}

	public function getFlash($name)
	{
		$result = $this->application->cgi->getGPC('flash_session_' . $name, null);
		$result = $result ? unserialize($result) : null;
		$this->application->cgi->deleteCookie('flash_session_' . $name);

		return $result;
	}

} 