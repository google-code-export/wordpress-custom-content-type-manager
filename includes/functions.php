<?php
/*------------------------------------------------------------------------------
These are functions in the main namespace, primarilyy reserved for use in 
theme files.
------------------------------------------------------------------------------*/


/*------------------------------------------------------------------------------
SYNOPSIS: Used inside theme files, e.g. single.php or single-my_post_type.php
where you need to print out the value of a specific custom field.

This prints the 1st instance of the meta_key identified by $fieldname 
associated with the current post. See get_post_meta() for more details.

INPUT: 
	$fieldname (str) the name of the custom field as defined inside the 
		Manage Custom Fields area for a particular content type.
OUTPUT:
	The contents of that custom field for the current post.
------------------------------------------------------------------------------*/
function get_custom_field($fieldname)
{
	// the_ID() function won't work because it *prints* its output
	$post_id = get_the_ID();
	return get_post_meta($post_id, $fieldname, true);
}


/*------------------------------------------------------------------------------
SYNOPSIS: Used inside theme files, e.g. single.php or single-my_post_type.php
where you need to print out the value of a specific custom field.

This prints the 1st instance of the meta_key identified by $fieldname 
associated with the current post. See get_post_meta() for more details.

INPUT: 
	$fieldname (str) the name of the custom field as defined inside the 
		Manage Custom Fields area for a particular content type.
OUTPUT:
	The contents of that custom field for the current post.
------------------------------------------------------------------------------*/
function print_custom_field($fieldname)
{
	print get_custom_field($fieldname);
}


/*------------------------------------------------------------------------------
Private (?) function that will scour the custom field definitions for any fields
of the type specified.  This is useful e.g. if you want to return all images 
attached to a post, or (FUTURE???) if the field is defined as a list.

Must be used when there is an active post.

A $def looks something like this:
 Array
(
    [label] => Author
    [name] => author
    [description] => This is who wrote the book
    [type] => text
    [sort_param] => 
)
------------------------------------------------------------------------------*/
function get_all_fields_of_type($type)
{
	global $post;
//	print_r($post); exit;	
	$values = array();

#	return get_post_meta($post_id, $fieldname, true);
	
	$data = get_option( CustomContentTypeManager::db_key );
	
	$post_type = $post->post_type;
	if ( !isset($data[$post_type]['custom_fields']) )
	{
		return "No custom fields defined for $fieldname field.";
	}
	
	foreach ( $data[$post_type]['custom_fields'] as $def )
	{
		if ($def['type'] == $type )
		{
			$values[] = get_custom_field($def['name']);
		}		
	}
	
	print_r($values);

}

/*------------------------------------------------------------------------------
This will return all posts that are tagged with slug $slug (i.e. term) in the 
taxonomy $taxonomy. 

Using query_posts() inside of the loop is not possible because WP manipulates that function
somehow, causing infinite loops. This function uses its own query, so it is safe
for the loop.

SAMPLE QUERY:
	SELECT * 
	FROM wp_terms 
	JOIN wp_term_taxonomy ON wp_terms.term_id = wp_term_taxonomy.term_id
	JOIN wp_term_relationships ON wp_term_relationships.term_taxonomy_id = wp_term_taxonomy.term_taxonomy_id
	JOIN wp_posts ON wp_posts.ID = wp_term_relationships.object_id
	WHERE wp_terms.slug = 'bear' 
	AND wp_term_taxonomy.taxonomy = 'post_tag';

USAGE: 

	$tagged_posts = get_posts_by_taxonomy_term('post_tag','popular');

	foreach ($tagged_posts as $p )
	{
		print $p->post_title;
	}

OR Using a custom taxonomy:
	get_posts_by_taxonomy_term('genre','horror');

	
INPUT:
	$taxonomy (str). Built in taxonomies include 'post_type','category', and 'link_category'
	$slug (str)
	$limit (int) optionallyi limit the posts returned to the given number. By 
		default, all posts will be returned.
OUTPUT: array of post objects or empty array.
------------------------------------------------------------------------------*/
function get_posts_by_taxonomy_term($taxonomy, $slug, $limit = FALSE)
{
	global $wpdb; 
	
	if ( empty($taxonomy) || empty($slug) )
	{
		return array();	
	}

		
	$limit_sql = '';
	if ($limit)
	{
		$limit_sql = " LIMIT " . (int) $limit;
	}
	
	$query = "SELECT * 
		FROM {$wpdb->terms} 
		JOIN {$wpdb->term_taxonomy} ON {$wpdb->terms}.term_id = {$wpdb->term_taxonomy}.term_id
		JOIN {$wpdb->term_relationships} ON {$wpdb->term_relationships}.term_taxonomy_id = {$wpdb->term_taxonomy}.term_taxonomy_id
		JOIN {$wpdb->posts} ON {$wpdb->posts}.ID = {$wpdb->term_relationships}.object_id
		WHERE 
			{$wpdb->term_taxonomy}.taxonomy = %s
			AND 
			{$wpdb->terms}.slug = %s " . $limit_sql;
//	print $query; exit;
		$results = $wpdb->get_results( $wpdb->prepare( $query, $taxonomy, $slug ) );
	
	
//	$results = query_posts( array( $taxonomy => $slug, 'posts_per_page' => $limit ) );
	return $results;
}
/*EOF*/