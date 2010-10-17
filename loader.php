<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed');
/*------------------------------------------------------------------------------
The loader.php is called only when we've checked for any potential conflicts
with function names, class names, or constant names. With so many WP plugins
available and so many potential conflicts out there, I've attempted to 
avoid the resulting headaches as much as possible.
------------------------------------------------------------------------------*/


// Required Files
include_once('includes/constants.php');
include_once('includes/CustomContentTypeManager.php');
include_once('includes/FormGenerator.php');
include_once('includes/StandardizedCustomFields.php');
include_once('includes/functions.php');
include_once('tests/CCTMtests.php');

// Run Tests.
CCTMtests::wp_version_gt(CustomContentTypeManager::wp_req_ver);
CCTMtests::php_version_gt(CustomContentTypeManager::php_req_ver);
CCTMtests::mysql_version_gt(CustomContentTypeManager::mysql_req_ver);

// Get admin ready, show any 'hard' errors, if any.
add_action( 'admin_notices', 'CustomContentTypeManager::print_notices');


if ( empty(CCTMtests::$errors) )
{
	add_action( 'admin_init', 'CustomContentTypeManager::admin_init');	
	
	// Register any custom post-types
	add_action( 'init', 'CustomContentTypeManager::register_custom_post_types', 0 );
	
	// create custom plugin settings menu
	add_action('admin_menu', 'CustomContentTypeManager::create_admin_menu');
	add_filter('plugin_action_links', 'CustomContentTypeManager::add_plugin_settings_link', 10, 2 );
	
	
	// Standardize Fields
	add_action( 'do_meta_boxes', 'StandardizedCustomFields::remove_default_custom_fields', 10, 3 );
	add_action( 'admin_menu', 'StandardizedCustomFields::create_meta_box' );
	add_action( 'save_post', 'StandardizedCustomFields::save_custom_fields', 1, 2 );
}
/*
$error = new WP_Error();
print_r( $error );
exit;
*/

/*EOF*/