<?php
/*------------------------------------------------------------------------------
This is run only when this plugin is uninstalled. All cleanup code goes here.
Only a single option was used in the wp_options table -- that's where I 
stashed the serialized array that stored all the settings and such. 

WARNING: uninstalling a plugin fails when developing locally via MAMP.
I think it's a WordPress bug (version 3.0.1).
------------------------------------------------------------------------------*/

if ( defined('WP_UNINSTALL_PLUGIN'))
{
	include_once('includes/CCTM.php');
	delete_option( CCTM::db_key );
	global $wp_rewrite;
	$wp_rewrite->flush_rules();
}
/*EOF*/