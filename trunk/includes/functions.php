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


/*EOF*/