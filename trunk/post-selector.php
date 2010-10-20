<?php
/*------------------------------------------------------------------------------
This file is an independent controller, used to query the WordPress database
and provide an ID from wp_posts identifying a specific post. It can be called
as an iFrame or via an AJAX request, and it spits out different output
during each -- see the PostSelector class for details.

INCOMING URL PARAMETERS:
	See comments inside of includes/PostSelector.php
	
	fieldname = (req) id of field receiving the wp_posts.ID 

	s = (opt) search term
	m = (opt) month+year 
	post_mime_type = (opt) image | video | audio | all. Default: all
	page (opt) integer defining which page of results we're displaying. Default: 0


OUTPUT:
The value of the fieldname identified by 'fieldname' will be updated, e.g.

	<input type="hidden" id="myMediaField" value="123" />
	
A div with the id of fieldname + '_preview' will get injected with an img tag
representing a thumbnail of the selected media item, e.g. 

	<div id="myMediaField_preview"><img src="..." /></div>
------------------------------------------------------------------------------*/

// To tie into WP, we come in through the backdoor, by including the config.
require_once( realpath('../../../').'/wp-config.php' );
require_once( realpath('../../../').'/wp-admin/includes/post.php'); // TO-DO: what if the wp-admin dir changes?
$this_dir = dirname(__FILE__);
include_once($this_dir.'/includes/constants.php');
include_once($this_dir.'/includes/PostSelector.php');
include_once($this_dir.'/includes/Pagination.php');
include_once($this_dir.'/includes/Pagination.conf.php');

if ( !current_user_can('edit_posts') )
{
	wp_die(__('You do not have permission to edit posts.'));
}

$MS = new PostSelector();

/*EOF*/