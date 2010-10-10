<?php
/*------------------------------------------------------------------------------
This file is an independent controller, used to query the WordPress database
and provide search results for Ajax requests.
Reference this file with URL parameters for:

	s = search term
	m = month+year 
	post_mime_type = image | video | all

INPUT: 

------------------------------------------------------------------------------*/
if (!defined('WP_PLUGIN_URL')) 
{
	require_once( realpath('../../../').'/wp-config.php' );
}


// No point in executing a query if there's no query string
if ( empty($_GET['s']))
{
	exit;
}

// Get query vars

// Get posts
$postslist = get_posts('post_type=attachment&numberposts=10&order=ASC&orderby=title');
foreach ($postslist as $post) : 
	$id = $post->ID;
	// keyword (thumbnail, medium, large or full)
	print wp_get_attachment_image( $id, 'thumb' ) . '<br/>';
	print $post->post_title . "<br/>";
endforeach; 

// print output



// If there are no results... 
/*
if (! count($WP_Query_object->posts) ){
	print file_get_contents('tpls/no_results.tpl');	
	exit;
}


// Otherwise, format the results
$container = array('content'=>''); // define the container's only placeholder
$single_tpl = file_get_contents('tpls/single_result.tpl');	
foreach($WP_Query_object->posts as $result)
{
	$result->permalink = get_permalink($result->ID);
	$container['content'] .= parse($single_tpl, $result);
}

// Wrap the results
$results_container_tpl = file_get_contents('tpls/results_container.tpl');
print parse($results_container_tpl, $container);
*/

/*------------------------------------------------------------------------------
SYNOPSIS: a simple parsing function for basic templating.
INPUT:
	$tpl (str): a string containing [+placeholders+]
	$hash (array): an associative array('key' => 'value');
OUTPUT
	string; placeholders corresponding to the keys of the hash will be replaced
	with the values and the string will be returned.
------------------------------------------------------------------------------*/
function parse($tpl, $hash) {

    foreach ($hash as $key => $value) {
        $tpl = str_replace('[+'.$key.'+]', $value, $tpl);
    }
    return $tpl;
}

/* EOF */