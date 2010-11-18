<?php
/*------------------------------------------------------------------------------
This plugin standardizes the custom fields for specified content types, e.g.
post, page, and any other custom post-type you register via a plugin.
------------------------------------------------------------------------------*/
class StandardizedCustomFields 
{
	/*
	This prefix helps ensure unique keys in the $_POST array. It is used only to 
	identify the form elements; this prefix is *not* used as part of the meta_key
	when saving the field names to the database. If you want your fields to be 
	hidden from built-in WordPress functions, you can name them individually 
	using "_" as the first character.
	
	If you omit a prefix entirely, your custom field names must steer clear of
	the built-in post field names (e.g. 'content').
	*/
	public static $prefix = 'custom_content_'; 
	
	// Which types of content do we want to standardize?
	public static $content_types_array = array('post');
	
	//! Private Functions
	/*------------------------------------------------------------------------------
	This plugin is meant to be configured so it acts on a specified list of content
	types, e.g. post, page, or any custom content types that is registered.
	FUTURE: read this from the database.
	------------------------------------------------------------------------------*/
	private static function _get_active_content_types()
	{
		$data = get_option( CCTM::db_key );
		if ( !empty($data) && is_array($data) )
		{
			$known_post_types = array_keys($data);
			$active_post_types = array();
			foreach ($known_post_types as $pt)
			{
				if ( isset($data[$pt]['is_active']) && $data[$pt]['is_active'] == 1 )
				{
					$active_post_types[] = $pt;
				}
			}
			return $active_post_types;
		}
		else
		{
			return array();
		}
	}

	/*------------------------------------------------------------------------------
	Get custom fields for this content type.
	INPUT: $content_type (str) the name of the content type, e.g. post, page.
	OUTPUT: array of associative arrays where each associative array describes 
		a custom field to be used for the $content_type specified.
	FUTURE: read these arrays from the database.
	------------------------------------------------------------------------------*/
	private static function _get_custom_fields($content_type)
	{
		$data = get_option( CCTM::db_key );
		if (isset($data[$content_type]['custom_fields']))
		{
			return $data[$content_type]['custom_fields'];
		}
		else
		{
			return array();
		}
	}


	//! Public Functions	
	/*------------------------------------------------------------------------------
	* Create the new Custom Fields meta box
	------------------------------------------------------------------------------*/
	public static function create_meta_box() {
		$content_types_array = self::_get_active_content_types();
		foreach ( $content_types_array as $content_type ) {
			add_meta_box( 'custom-content-type-mgr-custom-fields'
				, __('Custom Fields', CCTM::txtdomain )
				, 'StandardizedCustomFields::print_custom_fields'
				, $content_type
				, 'normal'
				, 'high'
				, $content_type 
			);
		}
	}


	/*------------------------------------------------------------------------------
	SYNOPSIS: a simple parsing function for basic templating.
	INPUT:
		$tpl (str): a string containing [+placeholders+]
		$hash (array): an associative array('key' => 'value');
	OUTPUT
		string; placeholders corresponding to the keys of the hash will be replaced
		with the values and the string will be returned.
	------------------------------------------------------------------------------*/
	public static function parse($tpl, $hash) {
	
	    foreach ($hash as $key => $value) 
	    {
	        $tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	    }
	    return $tpl;
	}


	/*------------------------------------------------------------------------------
	Display the new Custom Fields meta box
	INPUT:
		$post (the post object is always passed to this callback function). 
		$callback_args will always have a copy of this object passed (I'm not sure why),
		but in $callback_args['args'] will be the 7th parameter from the add_meta_box() function.
		We are using this argument to pass the content_type.
	------------------------------------------------------------------------------*/
	public static function print_custom_fields($post, $callback_args='') 
	{
		//return;
		$content_type = $callback_args['args']; // the 7th arg from add_meta_box()
		$custom_fields = self::_get_custom_fields($content_type);
		$output = '';		
		
		// If no custom content fields are defined...
		if ( empty($custom_fields) )
		{
			global $post;
			$post_type = $post->post_type;
			$url = sprintf( '<a href="options-general.php?page='
				.CCTM::admin_menu_slug.'&'
				.CCTM::action_param.'=4&'
				.CCTM::post_type_param.'='.$post_type.'">%s</a>', __('Settings Page', CCTM::txtdomain ) );
			print '<p>';
			printf ( __('Custom fields can be added and configured using the %1$s %2$s', CCTM::txtdomain), CCTM::name, $url );
			print '</p>';
			return;
		}
		
		foreach ( $custom_fields as $def_i => &$field ) {

			$output_this_field = '';			
			$field['label'] = $field['label'] . ' ('.$field['name'].')'; // to display the name used in templates			
			$field['value'] = htmlspecialchars( get_post_meta( $post->ID, $field['name'], true ) );
			$field['name'] = self::$prefix . $field['name']; // this ensures unique keys in $_POST


		}
		$output = FormGenerator::generate($custom_fields);
 		// Print the form
 		print '<div class="form-wrap">';
	 	wp_nonce_field('update_custom_content_fields','custom_content_fields_nonce');
	 	print $output;
	 	print '</div>';
 
	}


	/*------------------------------------------------------------------------------
	Remove the default Custom Fields meta box. Only affects the content types that
	have been activated.
	INPUTS: sent from WordPress
	------------------------------------------------------------------------------*/
	public static function remove_default_custom_fields( $type, $context, $post ) 
	{
		$content_types_array = self::_get_active_content_types();
		foreach ( array( 'normal', 'advanced', 'side' ) as $context ) {
			foreach ( $content_types_array as $content_type )
			{
				remove_meta_box( 'postcustom', $content_type, $context );
			}
		}
	}
	
	/*------------------------------------------------------------------------------
	Save the new Custom Fields values
	INPUT:
		$post_id (int) id of the post these custom fields are associated with
		$post (obj) the post object
	------------------------------------------------------------------------------*/
	public static function save_custom_fields( $post_id, $post ) 
	{
		// The 2nd arg here is important because there are multiple nonces on the page
		if ( !empty($_POST) && check_admin_referer('update_custom_content_fields','custom_content_fields_nonce') )
		{			
			$custom_fields = self::_get_custom_fields($post->post_type);
			
			foreach ( $custom_fields as $field ) {
				if ( isset( $_POST[ self::$prefix . $field['name'] ] ) )
				{
					$value = trim($_POST[ self::$prefix . $field['name'] ]);
					// Auto-paragraphs for any WYSIWYG
					if ( $field['type'] == 'wysiwyg' ) 
					{
						$value = wpautop( $value );
					}
					update_post_meta( $post_id, $field[ 'name' ], $value );
				}
				// if not set, then it's an unchecked checkbox, so blank out the value.
				else 
				{
					update_post_meta( $post_id, $field[ 'name' ], '' );
				}
			}
			
		}
	}


} // End Class



/*EOF*/