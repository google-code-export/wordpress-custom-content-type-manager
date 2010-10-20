<?php
/*------------------------------------------------------------------------------
Plugin Name: Custom Content Type Manager
Description: Allows users to create custom content types (also known as post types) and standardize custom fields for each content type, including dropdowns, checkboxes, and images. This gives WordPress CMS functionality making it easier to use WP for eCommerce or content-driven sites.
Author: Everett Griffiths
Version: 0.8.0
Author URI: http://www.fireproofsocks.com/
Plugin URI: http://tipsfor.us/plugins/custom-content-type-manager/
------------------------------------------------------------------------------*/



/*------------------------------------------------------------------------------
CONFIGURATION: 

Define the names of functions and classes uses by this plugin so we can test 
for conflicts prior to loading the plugin and message the WP admins.

$function_names_used -- add any function names that this plugin declares in the 
	main namespace (e.g. utility functions or theme functions).

$class_names_used -- add any class names that are declared by this plugin.
------------------------------------------------------------------------------*/
$function_names_used = array('get_custom_field','print_custom_field');
$class_names_used = array('CustomPostTypeManager','FormGenerator'
	,'StandardizedCustomFields','CCTMtests','MediaSelector');
$constants_used = array('CUSTOM_CONTENT_TYPE_MGR_PATH','CUSTOM_CONTENT_TYPE_MGR_URL');

$error_items = '';

function custom_content_type_manager_cannot_load()
{
	global $error_items;
	print '<div id="custom-post-type-manager-warning" class="error fade"><p><strong>'
	.__('The Custom Post Type Manager plugin cannot load correctly!')
	.'</strong> '
	.__('Another plugin has declared conflicting class, function, or constant names:')
	."<ul style='margin-left:30px;'>$error_items</ul>"
	.'</p>'
	.'<p>'.__('You must deactivate the plugins that are using these conflicting names.').'</p>'
	.'</div>';
	
}

/*------------------------------------------------------------------------------
The following code tests whether or not this plugin can be safely loaded.
If there are no conflicts, the loader.php is included and the plugin is loaded,
otherwise, an error is displayed in the manager.
------------------------------------------------------------------------------*/
// Check for conflicting function names
foreach ($function_names_used as $f_name )
{
	if ( function_exists($f_name) )
	{
		$error_items .= '<li>'.__('Function: ') . $f_name .'</li>';
	}
}
// Check for conflicting Class names
foreach ($class_names_used as $cl_name )
{
	if ( class_exists($cl_name) )
	{
		$error_items .= '<li>'.__('Class: ') . $cl_name .'</li>';
	}
}
// Check for conflicting Constants
foreach ($constants_used as $c_name )
{
	if ( defined($c_name) )
	{
		$error_items .= '<li>'.__('Constant: ') . $c_name .'</li>';
	}
}

// Fire the error, or load the plugin.
if ($error_items)
{
	$error_items = '<ul>'.$error_items.'</ul>';
	add_action('admin_notices', 'custom_content_type_manager_cannot_load');
}
else
{
	// Load the plugin
	include_once('loader.php');
}

/*EOF*/