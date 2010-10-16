<?php
/*------------------------------------------------------------------------------
Plugin Name: Custom Content Type Manager
Description: Allows WordPress 3.x users to create, extend, and manage custom content types (a.k.a. post types) like a true CMS. You can define and standardize custom fields for any content type, including checkboxes, textareas, dropdowns, WYSIWYG editor fields, and media fields (allowing you to add an image, video, or audio clip).
Author: Everett Griffiths
Version: 0.7.9
Author URI: http://www.fireproofsocks.com/
Plugin URI: http://tipsfor.us/plugins/custom-content-type-manager/

About:
This plugin is similar to the "Custom-Post Type UI" plugin written by Brad Williams:
http://wordpress.org/extend/plugins/custom-post-type-ui/
but this plugin stores data differently in the database and allows for different input types 
(e.g. checkboxes and dropdowns).

See also:
http://kovshenin.com/archives/extending-custom-post-types-in-wordpress-3-0/
http://axcoto.com/blog/article/307

TO-DO items:
Permalinks in Custom Post Types:
http://xplus3.net/2010/05/20/wp3-custom-post-type-permalinks/

Attachments in Custom Post Types:
http://xplus3.net/2010/08/08/archives-for-custom-post-types-in-wordpress/

Taxonomies:
http://net.tutsplus.com/tutorials/wordpress/introducing-wordpress-3-custom-taxonomies/

Bummer. Trying to use the Media Upload Modal window is tricky:
http://core.trac.wordpress.org/ticket/11705

Editing Attachments
http://xplus3.net/2008/11/17/custom-thumbnails-wordpress-plugin/

Error messaging could fail if the user is using pre WP 2.0.11. Unlikely, but possible.
Not really worth fielding that case though...

TO-DO:
1. Allow users to add additional custom fields beyond the standardized fields.
2. Allow "list" fields -- e.g. you define a custom field that's a media type, if 
you check a box specifying that it's a list, it would allow you to add multiple 
instances of that field to your post.  That's a LOT trickier than what I'm doing 
now, but I think my architecture is sensible enough to support it.
3. Enable the taxonomy filtering in the media browser... I could also do an 
author filter and any other way you might want to sort image results.
4. Oh yeah... pagination.  I built a spot for that, but haven't plugged it in 
yet.
5. Additional tests
6. Complete internationalization / localization

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