<?php
/*------------------------------------------------------------------------------
Make sure we're cleared to launch.  Test the following

When you add a dropdown custom field, does the JS fire to let you create options?
When you deselect the dropdown as the field type, do the options clear?
------------------------------------------------------------------------------*/
class CCTMtests
{
	public static $errors = array(); // Any errors thrown.
	
	// INPUT: minimum req'd version of MySQL, e.g. 5.0.41
	public static function mysql_version_gt($ver)
	{
		global $wpdb;
		
		$exit_msg = CustomContentTypeManager::name . __( " requires MySQL $ver or newer. 
			Talk to your system administrator about upgrading");
		$result = $wpdb->get_results( 'SELECT VERSION() as ver' );

		if ( version_compare( $result[0]->ver, $ver, '<') ) 
		{
			self::$errors[] = $exit_msg;
		}
	}

	/*------------------------------------------------------------------------------
	SUMMARY: This relies on the output of the get_plugins() function and the 
		get_option('active_plugins') contents.
		
	INPUT:
		$required_plugins should be an associative array with the names of the plugins
		 and the required versions, e.g.
		 array( 'My Great Plugin' => '0.9', 'Some Other Plugin' => '1.0.1' )
		 
	OUTPUT: null if no errors. There are 2 errors that can be generated: one if the 
	plugin's version is too old, and another if it is missing altogether.
	------------------------------------------------------------------------------*/
	public static function wp_required_plugins($required_plugins)
	{
		require_once(ABSPATH.'/wp-admin/includes/admin.php');
		$all_plugins = get_plugins();
		$active_plugins = get_option('active_plugins');
		
		// Re-index the $all_plugins array for easier testing. 
		// We want to index it off of the name; it's not guaranteed to be unique, so this 
		// test could throw some illigitimate errors if 2 plugins shared the same name.
		$all_plugins_reindexed = array();
		foreach ( $all_plugins as $path => $data )
		{
			$new_index = $data['Name'];
			$all_plugins_reindexed[$new_index] = $data;
		}
		
		foreach ( $required_plugins as $name => $version )
		{
			if ( isset($all_plugins_reindexed[$name]) )
			{
				if ( !empty($all_plugins_reindexed[$name]['Version']) )
				{
					if (version_compare($all_plugins_reindexed[$name]['Version'],$version,'<'))
					{
						self::$errors[] = CustomContentTypeManager::name . __(" requires version $version of the $name plugin.");			
					}
				}
			}
			else
			{
				self::$errors[] = CustomContentTypeManager::name . __(" requires version $version of the $name plugin. $name is not installed.");			
			}
		}
	}

	//------------------------------------------------------------------------------
	private static function _test()
	{
		$error_flag = FALSE;
		
		if ( $all_plugins[$plugin_path]['Name'] == $name )
		{
			if (version_compare($all_plugins[$plugin_path]['Version'],$version,'<'))
			{
				self::$errors[] = 'Plugin version too old.';
			}
		
		}
	}
	
	//------------------------------------------------------------------------------
	public static function wp_version_gt($ver)
	{
		global $wp_version;
	
		$exit_msg = CustomContentTypeManager::name . __(" requires WordPress $ver or newer. 
			<a href='http://codex.wordpress.org/Upgrading_WordPress'>Please update!</a>");
		
		if (version_compare($wp_version,$ver,'<'))
		{
			self::$errors[] = $exit_msg;
		}

	}

	//------------------------------------------------------------------------------
	public static function php_version_gt($ver)
	{
		$exit_msg = CustomContentTypeManager::name . __(" requires PHP $ver or newer. 
			Talk to your system administrator about upgrading");
		
		if ( version_compare( phpversion(), $ver, '<') ) 
		{
			self::$errors[] = $exit_msg;
		}
	}
	
	
	
	/*------------------------------------------------------------------------------
	PHP might have been compiled without some module that you require. Pass this 
	function an array of $required_extensions and it will throw return a message 
	about any missing modules.
	INPUT: 
		$required_extensions = array('pcre', 'posix', 'mysqli', 'mcrypt');
	OUTPUT: null, or an error message.
	------------------------------------------------------------------------------*/
	public static function php_extensions($required_extensions)
	{
		
		$loaded_extensions = get_loaded_extensions();

		foreach ( $required_extensions as $req )
		{
			if ( !in_array($req, $loaded ) )
			{
				self::$errors[] = CustomContentTypeManager::name . __(" requires the $req PHP extension.
			Talk to your system administrator about reconfiguring PHP.");
			}
		}
	
	}
}
/*EOF*/