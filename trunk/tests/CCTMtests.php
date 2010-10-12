<?php
/*------------------------------------------------------------------------------
Make sure we're cleared to launch
------------------------------------------------------------------------------*/
class CCTMtests
{
	public static $errors = array(); // Any errors thrown.
	
	//------------------------------------------------------------------------------
	public static function wp_version_gt($ver)
	{
		global $wp_version;
	
		$exit_msg="Custom Content Type Manager requires WordPress $ver or newer. 
			<a href='http://codex.wordpress.org/Upgrading_WordPress'>Please update!</a>";
		
		if (version_compare($wp_version,$ver,'<'))
		{
			self::$errors[] = $exit_msg;
		}

	}

	//------------------------------------------------------------------------------
	public static function php_version_gt($ver)
	{
		$exit_msg="Custom Content Type Manager requires PHP $ver or newer. 
			Talk to your system administrator about upgrading";
		
		if ( version_compare( phpversion(), $ver, '<') ) 
		{
			self::$errors[] = $exit_msg;
		}
	}
}
/*EOF*/