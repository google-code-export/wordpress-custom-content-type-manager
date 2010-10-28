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

See also 	
http://codex.wordpress.org/Function_Reference/get_post_custom_values
------------------------------------------------------------------------------*/
function get_custom_field($fieldname)
{
	// the_ID() function won't work because it *prints* its output
	$post_id = get_the_ID();
	return get_post_meta($post_id, $fieldname, true);
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
	
	return $values;

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
function get_posts_by_taxonomy_term($taxonomy, $slug, $limit = false)
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

/*------------------------------------------------------------------------------
Retrieves a complete post object, including all meta fields.
Ah... get_post_custom() will treat each custom field as an array, because in WP
you can tie multiple rows of data to the same fieldname.

At the end of this, I want a post object that can work like this:

print $post->post_title;
print $post->my_custom_field; // no $post->my_custom_fields[0];

and if the custom field *is* a list of items, then attach it as such.
------------------------------------------------------------------------------*/
function get_post_complete($id)
{
	$complete_post = get_post($id, OBJECT);
	if ( empty($complete_post) )
	{
		return array();
	}
	$custom_fields = get_post_custom($id);
	if (empty($custom_fields))
	{
		return $complete_post;
	}
	foreach ( $custom_fields as $fieldname => $value )
	{
		if ( count($value) == 1 )
		{
			$complete_post->$fieldname = $value[0];
		}
		else
		{
			$complete_post->$fieldname = $value[0];		
		}
	}
	
	return $complete_post;	
}


/*------------------------------------------------------------------------------
Returns an array of post "complete" objects (including all custom fields)
where the custom fieldname = $fieldname and the value of that field is $value.

USAGE:
	One example:
	$posts = get_posts_sharing_custom_field_value('genre', 'comedy');
	
	foreach ($posts as $p)
	{
		print $p->post_title;
	}

This is a hefty, db-intensive function... (bummer).
------------------------------------------------------------------------------*/
function get_posts_sharing_custom_field_value($fieldname, $value)
{
	global $wpdb;
	$query = "SELECT DISTINCT {$wpdb->posts}.ID 
		FROM {$wpdb->posts} JOIN {$wpdb->postmeta} ON {$wpdb->posts}.ID = {$wpdb->postmeta}.post_id  
		WHERE 
		{$wpdb->posts}.post_status = 'publish'
		AND {$wpdb->postmeta}.meta_key=%s AND {$wpdb->postmeta}.meta_value=%s";
	$results = $wpdb->get_results( $wpdb->prepare( $query, $fieldname, $value ), OBJECT );
	
	$completes = array();
	foreach ( $results as $p )
	{
		$completes[] = get_post_complete($p->ID);
	}
	return $completes;
}


/*------------------------------------------------------------------------------
A relation field stores a post ID, so given a fieldname, this returns the complete
post object for that 
------------------------------------------------------------------------------*/
function get_relation($fieldname)
{
	return get_post_complete( get_custom_field($fieldname) );
}

/*------------------------------------------------------------------------------
Given a specific custom field name ($fieldname), return an array of all unique
values contained in this field. This does not account for random custom fields
not defined as a "standardized" custom field. Likewise, it does not care which 
post types are making use of a particular custom field name so long as the
posts are published.

This filters out empty values ('' or null). 

USAGE:
	$array = get_unique_values_this_custom_field('favorite_cartoon');
	print_r($array);
		Array ( 'Family Guy', 'South Park' );
------------------------------------------------------------------------------*/
function get_unique_values_this_custom_field($fieldname)
{
	global $wpdb;
	$query = "SELECT DISTINCT {$wpdb->postmeta}.meta_value 
		FROM {$wpdb->postmeta} JOIN {$wpdb->posts} ON {$wpdb->postmeta}.post_id = {$wpdb->posts}.ID
		WHERE {$wpdb->postmeta}.meta_key=%s 
		AND {$wpdb->postmeta}.meta_value !=''
		AND {$wpdb->posts}.post_status = 'publish'";
	$results = $wpdb->get_results( $wpdb->prepare($query, $fieldname), ARRAY_N );	
	// Repackage
	$uniques = array();
	foreach ($results as $r )
	{
		$uniques[] = $r[0];
	}
	
	return $uniques;
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


/*EOF*/