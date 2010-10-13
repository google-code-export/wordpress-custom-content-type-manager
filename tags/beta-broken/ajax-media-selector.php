<?php
/*------------------------------------------------------------------------------
This file is an independent controller, used to query the WordPress database
and provide an ID from wp_posts identifying a specific attachment post.

TODO: pagination of results

INCOMING URL PARAMETERS:

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
require_once( realpath('../../../').'/wp-admin/includes/post.php');
$this_dir = dirname(__FILE__);
include_once($this_dir.'/includes/constants.php');

/*
if ( !current_user_can('edit_posts') )
{
	wp_die(__('You do not have permission to edit posts.'));
}
*/

// Read supplied get args. Filter them!!!
$args = array(
	'post_mime_type' => 'all'
);
$valid_post_mime_types = array(
	'image' => '',
	'video' => '',
	'audio' => '',
	'all'	=> ''
);
$fieldname = '';

//------------------------------------------------------------------------------
if ( isset($_GET['post_mime_type']) && !empty($_GET['post_mime_type']) )
{
	if ( !in_array( $_GET['post_mime_type'],  array_keys($valid_post_mime_types) ) )
	{
		wp_die(__('Invalid post_mime_type.')); 
	}
	$args['post_mime_type'] = $_GET['post_mime_type'];
}
if ( isset($_GET['fieldname']) && !empty($_GET['fieldname']) )
{
	if ( preg_match('/[^a-z_\-]/i', $_GET['post_mime_type']) )
	{
		wp_die(__('Invalid field_name.'));   // Only a-z, _, - is allowed.
	}
	$fieldname = $_GET['fieldname'];
}
// Search term
if ( isset($_GET['s']) && !empty($_GET['s']) )
{
	$search_term = $_GET['s'];
}
// TO-DO: pagination
if ( isset($_GET['page']))
{
	$page = (int) $_GET['page'];
}
// TO-DO: monthly archives
if ( isset($_GET['m']))
{
	$m = (int) $_GET['m'];
}

/*------------------------------------------------------------------------------

------------------------------------------------------------------------------*/
function get_post_mime_type_options($filter='all')
{
	global $wpdb;
	
	$avail_post_mime_types = array();
	if ($filter=='all')
	{
		$avail_post_mime_types = get_available_post_mime_types('attachment');
	}
	else
	{
		$avail_post_mime_types = array($filter);
	}

	$avail_post_mime_types_cnt = count($avail_post_mime_types);
	$media_type_option_tpl = '<li><span onclick="javascript:get_search_results(\'%s\');">%s</span> 
	%s </li>';
	$separator = '|';
	$media_type_list_items = sprintf($media_type_option_tpl,'all',__('All Types'),$separator);
	
	$media_type_option_tpl = '<li><span onclick="javascript:get_search_results(\'%s\');">%s <span class="count">(<span id="image-counter">%s</span>)</span> 
	%s </li>';
	
	$i = 1;
	// Format the list items for menu...
	foreach ( $avail_post_mime_types as $mt )
	{
		$mt_for_js = preg_replace('#/.*$#', '', $mt);
		//print $mt_for_js; exit;
		if ( $i == $avail_post_mime_types_cnt)
		{
			$separator = ''; // Special for last one.
		}

		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} 
			WHERE post_type = 'attachment'
			AND post_mime_type = %s  GROUP BY post_status";
		$raw_cnt = $wpdb->get_results( $wpdb->prepare( $query, $mt ), ARRAY_A );

		$cnt = $raw_cnt[0]['num_posts'];

		$media_type_list_items .= sprintf($media_type_option_tpl
			, $mt_for_js
			, __(ucfirst($mt_for_js))
			, $cnt
			, $separator);
		$i++;
	}

	$date_options = '<option value="0">Show all dates</option>
				<option value="201010">October 2010</option>';
	return $media_type_list_items;
}



?>
<script type="text/javascript">	
	function send_back_to_wp(x)
	{
		jQuery('#dicky').val(x);
		tb_remove();
		return false;
	}
</script>


<ul class="subsubsub">
	<?php print get_post_mime_type_options($args['post_mime_type']); ?>
</ul>