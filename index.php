<?php
/*
Plugin Name: Custom Content Type Manager
Plugin URI: http://www.tipsfor.us/
Description: Allows WordPress 3.x users to create, extend, and manage custom content types (a.k.a. post types) like a true CMS. You can define and standardize custom fields for any content type, including checkboxes, textareas, and dropdowns. This has been geared for production use: it has been tested and is free from PHP notices and errors that plague so many other plugins.
Author: Everett Griffiths
Version: 0.6
Author URI: http://www.tipsfor.us/

This plugin is similar to the "Custom-Post Type UI" plugin written by Brad Williams:
http://wordpress.org/extend/plugins/custom-post-type-ui/
but this plugin stores data differently in the database and allows for different input types 
(e.g. checkboxes and dropdowns).

See also:
http://kovshenin.com/archives/extending-custom-post-types-in-wordpress-3-0/
http://axcoto.com/blog/article/307

Permalinks in Custom Post Types:
http://xplus3.net/2010/05/20/wp3-custom-post-type-permalinks/

Attachments in Custom Post Types:
http://xplus3.net/2010/08/08/archives-for-custom-post-types-in-wordpress/

Bummer:
http://core.trac.wordpress.org/ticket/11705

Editing Attachments
http://xplus3.net/2008/11/17/custom-thumbnails-wordpress-plugin/
*/

include_once('includes/CustomPostTypeManager.php');
include_once('includes/FormGenerator.php');
include_once('includes/StandardizedCustomFields.php');

// Register any custom post-types
add_action( 'init', 'CustomPostTypeManager::register_custom_post_types', 0 );

// create custom plugin settings menu
add_action('admin_menu', 'CustomPostTypeManager::create_admin_menu');


// Standardize Fields
add_action( 'do_meta_boxes', 'StandardizedCustomFields::remove_default_custom_fields', 10, 3 );
add_action( 'admin_menu', 'StandardizedCustomFields::create_meta_box' );
add_action( 'save_post', 'StandardizedCustomFields::save_custom_fields', 1, 2 );





/*------------------------------------------------------------------------------
Array
(
    [type] => From Computer
    [type_url] => From URL
    [gallery] => Gallery
    [library] => Media Library
)
------------------------------------------------------------------------------*/



function simplify_media_tabs($tabs) 
{
//	print_r($tabs); exit;
	unset($tabs['type_url']);
	unset($tabs['gallery']);
	return $tabs;
}
add_filter('media_upload_tabs', 'simplify_media_tabs');


function xyzzy($x)
{
	print '---------------------------------------------------------';
	print_r($x); 
}


// This one controls the widget 
//add_filter('admin_post_thumbnail_html', 'xyzzy');



/*EOF*/