<?php
/*------------------------------------------------------------------------------
This class handles the creation and management of custom post types.  It
requires the FormGenerator.php and the StandardizedCustomFields.php files
in order to function.
------------------------------------------------------------------------------*/
class CustomContentTypeManager
{	
	const name = 'Custom Content Type Manager';
	
	// Required versions.
	const wp_req_ver 	= '3.0.1';
	const php_req_ver 	= '5.2.6';
	const mysql_req_ver = '5.0.41';

	// Used to uniquely identify an option_name in the wp_options table 
	// ALL data describing the post types and their custom fields lives there.
	// DELETE FROM `wp_options` WHERE option_name='custom_content_types_mgr_data'; 
	// would clean out everything this plugin knows.
	const db_key 	= 'custom_content_types_mgr_data';
	
	// Used to uniquely identify this plugin's menu page
	const admin_menu_slug = 'custom_content_type_mgr';

	// These parameters identify where in the $_GET array we can find the values
	const action_param 			= 'a';
	const post_type_param 		= 'pt';

	public static $def_i = 0; // used to iterate over groups of field definitions.

	// Built-in types that can have custom fields, but cannot be deleted.
	public static $built_in_post_types = array('post','page');
	// Names that are off-limits for custom post types
	public static $reserved_post_types = array('post','page','attachment','revision'
		,'nav_menu','nav_menu_item');
		
	// Future-proofing: post-type names cannot begin with this:
	// See: http://codex.wordpress.org/Custom_Post_Types	
	public static $reserved_prefix = 'wp_';

	public static $Errors;	// used to store WP_Error object
	
	// Used when creating or editing Post Types
	public static $post_type_form_definition =	array(
		'post_type' => array(
			'name'			=> 'post_type', 
			'label'			=> 'Name *',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'Unique singular name to identify this post type in the database, e.g. "movie","book". This may show up in your URLs, e.g. ?movie=star-wars. This will also make a new theme file available, starting with prefix named "single-", e.g. <strong>single-movie.php</strong>. The name should be lowercase with only letters and underscores. This name cannot be changed!',	
			'type'			=> 'text',
			'sort_param'	=> 1,
		),
		'singular_label' => array(
			'name'			=> 'singular_label', 
			'label'			=> 'Singular Label',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'Human readable single instance of this content type, e.g. "Post"',	
			'type'			=> 'text',
			'sort_param'	=> 2,
		),
		'label' => array(
			'name'			=> 'label', 
			'label'			=> 'Menu Label (Plural)',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'Plural name used in the admin menu, e.g. "Posts"',	
			'type'			=> 'text',
			'sort_param'	=> 3,
		),		
		'description' => array(
			'name'			=> 'description', 
			'label'			=> 'Description',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> '',	
			'type'			=> 'textarea',
			'sort_param'	=> 4,
		),
		'show_ui' =>array(
			'name'			=> 'show_ui',
			'label'			=> 'Show Admin User Interface',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> 'Should this post type be visible on the back-end?',
			'type'			=> 'checkbox',
			'sort_param'	=> 5,
		),
		'capability_type' => array(
			'name'			=> 'capability_type',
			'label'			=> 'Capability Type',
			'value'			=> 'post',
			'extra'			=> '',
			'description'	=> ' The post type to use for checking read, edit, and delete capabilities.
Default: "post"',
			'type'			=> 'text',
			'sort_param'	=> 6,
		),
		'public' => array(
			'name'			=> 'public',
			'label'			=> 'Public',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> 'Should these posts be visible on the front-end?',
			'type'			=> 'checkbox',
			'sort_param'	=> 7,
		),

		'hierarchical' => array(
			'name'			=> 'hierarchical',
			'label'			=> 'Hierarchical',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'Allows parent to be specified (Page Attributes should be checked)',
			'type'			=> 'checkbox',
			'sort_param'	=> 8,
		),
		'supports_title' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_title',
			'label'			=> 'Title',
			'value'			=> 'title',
			'checked_value' => 'title',
			'extra'			=> '',
			'description'	=> 'Post Title',
			'type'			=> 'checkbox',
			'sort_param'	=> 20,
		),
		'supports_editor' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_editor',
			'label'			=> 'Content',
			'value'			=> 'editor',
			'checked_value' => 'editor',
			'extra'			=> '',
			'description'	=> 'Main content block.',
			'type'			=> 'checkbox',
			'sort_param'	=> 21,
		),
		'supports_author' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_author',
			'label'			=> 'Author',
			'value'			=> '',
			'checked_value' => 'author',
			'extra'			=> '',
			'description'	=> 'Track the author.',
			'type'			=> 'checkbox',
			'sort_param'	=> 22,
		),
		'supports_thumbnail' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_thumbnail',
			'label'			=> 'Thumbnail',
			'value'			=> '',
			'checked_value' => 'thumbnail',
			'extra'			=> '',
			'description'	=> 'featured image (current theme must also support post-thumbnails)',
			'type'			=> 'checkbox',
			'sort_param'	=> 23,
		),
		'supports_excerpt' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_excerpt',
			'label'			=> 'Excerpt',
			'value'			=> '',
			'checked_value' => 'excerpt',
			'extra'			=> '',
			'description'	=> 'Small summary field.',
			'type'			=> 'checkbox',
			'sort_param'	=> 24,
		),
		'supports_trackbacks' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_trackbacks',
			'label'			=> 'Trackbacks',
			'value'			=> '',
			'checked_value' => 'trackbacks',
			'extra'			=> '',
			'description'	=> '',
			'type'			=> 'checkbox',
			'sort_param'	=> 25,
		),
		'supports_custom-fields' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_custom-fields',
			'label'			=> 'Supports Custom Fields',
			'value'			=> '',
			'checked_value' => 'custom-fields',
			'extra'			=> '',
			'description'	=> '',
			'type'			=> 'checkbox',
			'sort_param'	=> 26,
		),
		'supports_comments' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_comments',
			'label'			=> 'Enable Comments',
			'value'			=> '',
			'checked_value' => 'comments',
			'extra'			=> '',
			'description'	=> '',
			'type'			=> 'checkbox',
			'sort_param'	=> 27,
		),
		'supports_revisions' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_revisions',
			'label'			=> 'Store Revisions',
			'value'			=> '',
			'checked_value' => 'revisions',
			'extra'			=> '',
			'description'	=> '',
			'type'			=> 'checkbox',
			'sort_param'	=> 28,
		),
		'supports_page-attributes' => array(
			'name'			=> 'supports[]',
			'id'			=> 'supports_page-attributes',
			'label'			=> 'Enable Page Attributes',
			'value'			=> '',
			'checked_value' => 'page-attributes',
			'extra'			=> '',
			'description'	=> '(template and menu order; hierarchical must be checked)',
			'type'			=> 'checkbox',
			'sort_param'	=> 29,
		),
		
		'menu_position' => array(
			'name'			=> 'menu_position',
			'label'			=> 'Menu Position',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'Determines where this post type should appear in the left hand admin menu.<br/>
							Default: null - defaults to below Comments.
							<ul style="margin-left:40px;">
							<li><strong>5</strong> - below Posts</li>
							<li><strong>10</strong> - below Media</li>
							<li><strong>20</strong> - below Pages</li>
							<li><strong>60</strong> - below first separator</li>
							<li><strong>100</strong> - below second separator</li>
							</ul>',
			'type'			=> 'text',
			'sort_param'	=> 30,
		),
		
		'menu_icon' => array(
			'name'			=> 'menu_icon',
			'label'			=> 'Menu Icon',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> 'The url to the icon to be used for this menu.',
			'type'			=> 'text',
			'sort_param'	=> 31,
		),
		'use_default_menu_icon' => array(
			'name'			=> 'use_default_menu_icon',
			'label'			=> 'Use Default Menu Icon',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> 'If checked, your post type will use the posts icon',
			'type'			=> 'checkbox',
			'sort_param'	=> 32,
		),

		'rewrite_slug' => array(
			'name'			=> 'rewrite_slug',
			'label'			=> 'Rewrite Slug',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> "Prepend posts with this slug - defaults to post type's name",
			'type'			=> 'text',
			'sort_param'	=> 35,
		),
		'rewrite_with_front' => array(
			'name'			=> 'rewrite_with_front',
			'label'			=> 'Rewrite with Permalink Front',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> "Allow permalinks to be prepended with front base - defaults to checked",
			'type'			=> 'checkbox',
			'sort_param'	=> 35,
		),
		'rewrite' => array(
			'name'			=> 'permalink_action',
			'label'			=> 'Permalink Action',
			'value'			=> 'Off',
			'options'		=> array('Off','/%postname%/'), // ,'Custom'),
			'extra'			=> '',
			'description'	=> "Use permalink rewrites for this post_type? Default: Off<br/>
						<ul style='margin-left:20px;'>
							<li><strong>Off</strong> - URLs for custom post_types will always look like: http://site.com/?post_type=book&p=39 even if the rest of the site is using a different permalink structure.</li>
							<li><strong>/%postname%/</strong> - You MUST use the custom permalink structure: '/%postname%/'. Other formats are <strong>not</strong> supported.  Your URLs will look like http://site.com/movie/star-wars/</li>
							<!--li><strong>Custom</strong> - Evaluate the contents of slug</li-->
						<ul>",
			'type'			=> 'dropdown',
			'sort_param'	=> 37,
		),		

		'query_var' => array(
			'name'			=> 'query_var',
			'label'			=> 'Query Variable',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> '(optional) Name of the query var to use for this post type.<br/>
				E.g. "movie" would make for URLs like http://site.com/?movie=star-wars<br/>
				If blank, the default structure is http://site.com/?post_type=movie&p=18',
			'type'			=> 'text',
			'sort_param'	=> 38,
		),


		'can_export' => array(
			'name'			=> 'can_export',
			'label'			=> 'Can Export',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> 'Can this post_type be exported.',
			'type'			=> 'checkbox',
			'sort_param'	=> 40,
		),		

		'show_in_nav_menus' => array(
			'name'			=> 'show_in_nav_menus',
			'label'			=> 'Show in Nav Menus',
			'value'			=> '1',
			'extra'			=> '',
			'description'	=> 'Whether post_type is available for selection in navigation menus.
Default: value of public argument',
			'type'			=> 'checkbox',
			'sort_param'	=> 40,
		),		
	);

	/*------------------------------------------------------------------------------
	This array acts as a template for all new field definitions.
	See the _page_manage_custom_fields() function for when and how this occurs.
	------------------------------------------------------------------------------*/
	public static $custom_field_def_template = array(
		'label' => array(
			'name'			=> "custom_fields[[+def_i+]][label]",
			'label'			=> 'Label',
			'value'			=> '',
			'extra'			=> '',			
			'description'	=> '',
			'type'			=> 'text',
			'sort_param'	=> 1,
		),
		'name' => array(
			'name'			=> "custom_fields[[+def_i+]][name]", 
			'label'			=> 'Name',
			'value'			=> '',
			'extra'			=> '',			
			'description'	=> 'The name identifies the <em>option_name</em> in the <em>wp_options</em> database table. You will use this name in your template functions to identify this field.  If the name begins with an underscore, it will be hidden from <em>the_meta()</em> function and from the default WordPress manager.',	
			'type'			=> 'text',
			'sort_param'	=> 2,
		),
		'description' => array(
			'name'			=> "custom_fields[[+def_i+]][description]",
			'label'			=> 'Description',
			'value'			=> '',
			'extra'			=> '',
			'description'	=> '',
			'type'			=> 'textarea',
			'sort_param'	=> 3,
		),
		'type' => array(
			'name'			=> "custom_fields[[+def_i+]][type]",
			'label'			=> 'Input Type',
			'value'			=> '',
			'extra'			=> ' onchange="javascript:addRemoveDropdown(this.parentNode.id,this.value, [+def_i+])"',
			'description'	=> '',
			'type'			=> 'dropdown',
			'options'		=> array('checkbox','dropdown','media','relation','text','textarea','wysiwyg'),
			'sort_param'	=> 4,
		),
		'sort_param' => array(
			'name'			=> "custom_fields[[+def_i+]][sort_param]",
			'label'			=> 'Sort Order',
			'value'			=> '',
			'extra'			=> ' size="2" maxlength="4"',
			'description'	=> 'Fields with smaller numbers will appear higher on the page.',
			'type'			=> 'text',
			'sort_param'	=> 5,
		)
	);
	
	//! Private Functions
	/*------------------------------------------------------------------------------
	Generate HTML portion of our manage custom fields form. This is in distinction
	to the JS portion of the form, which uses a slightly different format.

	self::$def_i is used to track the definition #.  All 5 output fields will use 
	this number to identify their place in the $_POST array.
	
	INPUT: $custom_field_defs (mixed) an array of hashes, each hash describing
	a custom field.
	
	Array
	(
	    [1] => Array
	        (
	            [label] => Rating
	            [name] => rating
	            [description] => MPAA rating
	            [type] => dropdown
	            [options] => Array
	                (
	                    [0] => G
	                    [1] => PG
	                    [2] => PG-13
	                )
	
	            [sort_param] => 
	        )
	
	)

	OUTPUT: basically, for each field definition in the input, you get a slightly 
	modified version of the self::$custom_field_def_template structure. 
	------------------------------------------------------------------------------*/
	private static function _get_html_field_defs($custom_field_defs)
	{
//		print_r($def); exit;
		
		$output = '';
		foreach ($custom_field_defs as $def)
		{
			FormGenerator::$before_elements = '
			<div id="generated_form_number_'.self::$def_i.'">';
			
			FormGenerator::$after_elements = '
				<span class="button custom_content_type_mgr_remove" onClick="javascript:removeDiv(this.parentNode.id);">'.__('Remove This Field').'</span>
				<hr/>
			</div>';
			
			$translated = self::_transform_data_structure_for_editing($def);
			$output .= FormGenerator::generate($translated);
			self::$def_i++;
		}
		
		return $output;
	}
	
	/*------------------------------------------------------------------------------
	Gets a field definition ready for use inside of a JS variable.  We have to over-
	ride some of the names so they will inherit values from Javascript variables.
	------------------------------------------------------------------------------*/
	private static function _get_javascript_field_defs()
	{
		$def = self::$custom_field_def_template;
		foreach ($def as $row_id => &$field)
		{
			//$name = $field['name'];
			$name = $row_id;
			// alter the Extra part of this for the listener on the dropdown
			if($name == 'type')
			{
				$field['extra'] = str_replace('[+def_i+]', 'def_i', $field['extra']);
			}
			$field['name'] = "custom_fields['+def_i+'][$name]";
		}
		
		FormGenerator::$before_elements = '<div id="generated_form_number_\'+def_i+\'">';
		FormGenerator::$after_elements = '
			<a class="button" href="#" onClick="javascript:removeDiv(this.parentNode.id);">'.__('Remove This Field').'</a>
			<hr/>
		</div>';
		
		$output = FormGenerator::generate($def);
		// Javascript chokes on newlines...
		return str_replace( array("\r\n", "\r", "\n", "\t"), ' ', $output);
	}
	
	
	/*------------------------------------------------------------------------------
	Designed to safely retrieve scalar elements out of a hash. Don't use this 
	if you have a more deeply nested object (e.g. an array of arrays).
	INPUT: 
		$hash : an associative array, e.g. array('animal' => 'Cat');
		$key : the key to search for in that array, e.g. 'animal'
		$default (optional) : value to return if the value is not set. Default=''
	OUTPUT: either safely escaped value from the hash or the default value
	------------------------------------------------------------------------------*/
	private static function _get_value($hash, $key, $default='')
	{
		if ( !isset($hash[$key]) )
		{
			return $default;
		}
		else
		{	// Warning: stripslashes was added to avoid some weird behavior
			return esc_html(stripslashes($hash[$key]));
		}
	}
		
	/*------------------------------------------------------------------------------
	SYNOPSIS: checks the custom content data array to see if $post_type exists.
		The $data array is structured something like this:

		$data = array(
			'movie' => array('name'=>'movie', ... ),
			'book' => array('name'=>'book', ... ),
			...
		);
	
	So we can just check the keys of the main array to see if the post type exists.
	
	Built-in post types 'page' and 'post' are considered valid by default.
	
	INPUT: 
		$post_type (string) the lowercase database slug identifying a post type.
		$search_built_ins (boolean) whether or not to search inside the 
			$built_in_post_types array. 
			
	OUTPUT: boolean TRUE | FALSE indicating whether this is a valid post-type
	------------------------------------------------------------------------------*/
	private static function _is_existing_post_type($post_type, $search_built_ins=TRUE)
	{
		$data = get_option( self::db_key );
		
		// If there is no existing data, check against the built-ins
		if ( empty($data) && $search_built_ins ) 
		{
			return in_array($post_type, self::$built_in_post_types);
		}
		// If there's no existing $data and we omit the built-ins...
		elseif ( empty($data) && !$search_built_ins )
		{
			return FALSE;
		}
		// Check to see if we've stored this $post_type before
		elseif ( array_key_exists($post_type, $data) )
		{
			return TRUE;
		}
		// Check the built-ins
		elseif ( $search_built_ins && in_array($post_type, self::$built_in_post_types) )
		{
			return TRUE;
		}
		else
		{
			return FALSE;
		}
	}
		
	/*------------------------------------------------------------------------------
	Activating a post type will cause it to show up in the WP menus and its custom 
	fields will be managed.
	------------------------------------------------------------------------------*/
	private static function _page_activate_post_type($post_type)
	{
		// Validate post type
		if (!self::_is_existing_post_type($post_type) )
		{
			self::_page_display_error();
			return;
		}

		// get current values from database
		$data = get_option( self::db_key, array() );
		$data[$post_type]['is_active'] = 1;
		update_option( self::db_key, $data );
		
		// Often, PHP scripts use the header() function to refresh a page, but
		// WP has already claimed those, so we use a JavaScript refresh instead.
		// Refreshing the page ensures that active post types are added to menus.	
		$msg = '
			<script type="text/JavaScript">
				window.location.replace("?page='.self::admin_menu_slug.'");
			</script>';
		self::_page_show_all_post_types($msg);
	}
	
	/*------------------------------------------------------------------------------
	Create a new post type
	------------------------------------------------------------------------------*/
	private static function _page_create_new_post_type()
	{
		// Variables for our template
		$style			= '<style>'
			. file_get_contents( self::get_basepath() .'/css/create_or_edit_post_type.css' ) 
			. '</style>';

		$page_header 	= __('Create Custom Content Type');
		$fields			= '';
		$action_name 	= 'custom_content_type_mgr_create_new_content_type';
		$nonce_name 	= 'custom_content_type_mgr_create_new_content_type_nonce';
		$submit 		= __('Create New Content Type');
		$msg 			= ''; 	
		
		$def = self::$post_type_form_definition;
		
		// Save data if it was properly submitted
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			$sanitized_vals = self::_sanitize_post_type_def($_POST);
			$error = self::_post_type_name_has_errors($sanitized_vals['post_type']);

			if ( empty($error) )
			{				
				self::_save_post_type_settings($sanitized_vals);
				$msg = '
				<div class="updated">
					<p>The content type <em>'.$sanitized_vals['post_type'].'</em> has been created</p>
				</div>';
				self::_page_show_all_post_types($msg);
				return;
			}
			else
			{
				// This is for repopulating the form
				foreach ( $def as $node_id => $d )
				{
					$d['value'] = self::_get_value($sanitized_vals, $d['name']);
				}
					
				$msg = "<div class='error'>$error</div>";
			}
		}
				
		$fields = FormGenerator::generate($def);	
		include('pages/basic_form.php');
	}
	
	
	/*------------------------------------------------------------------------------
	Deactivate a post type. This will remove custom post types from the WP menus;
	deactivation stops custom fields from being standardized in built-in and custom 
	post types
	------------------------------------------------------------------------------*/
	private static function _page_deactivate_post_type($post_type)
	{
		// Validate post type
		if (!self::_is_existing_post_type($post_type) )
		{
			self::_page_display_error();
			return;
		}
		// Variables for our template
		$style			= '';
		$page_header = __('Deactivate Content Type') . ' '. $post_type;
		$fields			= '';
		$action_name 	= 'custom_content_type_mgr_deactivate_content_type';
		$nonce_name 	= 'custom_content_type_mgr_deactivate_content_type_nonce';
		$submit 		= __('Deactivate');
				
		// If properly submitted, Proceed with deleting the post type
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			// get current values from database
			$data = get_option( self::db_key, array() );
			$data[$post_type]['is_active'] = 0;
			update_option( self::db_key, $data );
			
			// A JavaScript refresh ensures that inactive post types are removed from the menus.	
			$msg = '
			<script type="text/JavaScript">
				window.location.replace("?page='.self::admin_menu_slug.'");
			</script>';
			self::_page_show_all_post_types($msg);
			return;
		}
		
		$msg = '<div class="error"><p>You are about to deactivate the <em>'.$post_type.'</em> 
			post type. Deactivation does <em>not</em> delete anything.</p>
			<p>Custom fields will no longer be managed, so if their names begin with an underscore (_),
			they will be invisible to the default WordPress manager.</p>
			';		
		
		// If it's a custom post type, we include some additional info.
		if ( !in_array($post_type, self::$built_in_post_types) )
		{
			$msg .= '<p>After deactivation, '.$post_type.' posts will be
			unavailable to the outside world. <strong>'.$post_type.'</strong> will be
			removed from the administration menus and you will no longer be able to edit '.$post_type.' posts
			using the WordPress manager.</p>';
		}
		
		$post_cnt_obj = wp_count_posts($post_type);
		$msg .= '<p>This would affect <strong>'.$post_cnt_obj->publish.'</strong> published
				<em>"'.$post_type.'"</em> posts.</p>
				<p>Are you sure you want to do this?</p>
			</div>';		

		include('pages/basic_form.php');
	}
	
	/*------------------------------------------------------------------------------
	This is only a valid page for custom post types.
	------------------------------------------------------------------------------*/
	private static function _page_delete_post_type($post_type)
	{
		// We can't delete built-in post types
		if (!self::_is_existing_post_type($post_type, FALSE ) )
		{
			self::_page_display_error();
			return;
		}

		// Variables for our template
		$style			= '';
		$page_header = __('Delete Content Type: ') . ' '. $post_type;
		$fields			= '';
		$action_name = 'custom_content_type_mgr_delete_content_type';
		$nonce_name = 'custom_content_type_mgr_delete_content_type_nonce';
		$submit 		= __('Delete');
		
		// If properly submitted, Proceed with deleting the post type
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			// get current values from database
			$data = get_option( self::db_key, array() );
			unset($data[$post_type]); // <-- Delete this node of the data structure
			update_option( self::db_key, $data );
			$msg = '<div class="updated"><p>The post type <em>'.$post_type.'</em> has been <strong>deleted</strong></p></div>';
			self::_page_show_all_post_types($msg);
			return;
		}
		
		$msg = '<div class="error"><p>You are about to delete the <em>'.$post_type.'</em> 
			post type. This will remove all of its settings from the database, but this will <em>NOT</em>
			delete any rows from the <strong>wp_posts</strong> table. However, without a custom post
			type defined for those rows, they will essentially be invisible to WordPress.
			</p>
			<p>Are you sure this is what you want to do?</p>
			</div>';

		include('pages/basic_form.php');
		
	}
	
	/*------------------------------------------------------------------------------
	Returned on errors. Future: accept an argument identifying an error
	------------------------------------------------------------------------------*/
	private static function _page_display_error()
	{	
		$msg = '<p>'.__('Invalid post type.') 
			. '</p><a class="button" href="?page='
			.self::admin_menu_slug.'">'. __('Back'). '</a>';
		wp_die( $msg );
	}


	/*------------------------------------------------------------------------------
	Edit an existing post type. We store the existing value of $post_type in 2 places 
	on the form: one editable and one hidden so we can see if the user
	changed the value. Validation rules prohibit overwriting existing post types, but
	if the post_type name did not change, we DO allow the overwrite.
	------------------------------------------------------------------------------*/
	private static function _page_edit_post_type($post_type)
	{
		// We can't edit built-in post types
		if (!self::_is_existing_post_type($post_type, FALSE ) )
		{
			self::_page_display_error();
			return;
		}

		// Variables for our template
		$style			= '<style>'
			. file_get_contents( self::get_basepath() .'/css/create_or_edit_post_type_class.css' ) 
			. '</style>';
		$page_header 	= __('Edit Content Type: ') . $post_type;
		$fields			= '';
		$action_name = 'custom_content_type_mgr_edit_content_type';
		$nonce_name = 'custom_content_type_mgr_edit_content_type_nonce';
		$submit 		= __('Save');
		$msg 			= ''; 	// Any validation errors
	
		$def = self::$post_type_form_definition;
		
		// Save data if it was properly submitted
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			$sanitized_vals = self::_sanitize_post_type_def($_POST);
			$error = self::_post_type_name_has_errors($sanitized_vals['post_type'], $_POST['_post_type_prev']);

			if ( empty($error) )
			{				
				self::_save_post_type_settings($sanitized_vals);

				$msg = '
					<script type="text/JavaScript">
						window.location.replace("?page='.self::admin_menu_slug.'");
					</script>';
				$msg .='<div class="updated"><p>Settings for <em>'
					.$sanitized_vals['post_type']
					.'</em> have been <strong>updated</strong></p></div>';
				self::_page_show_all_post_types($msg);
				return;
			}
			else
			{
				// This is for repopulating the form
				$def = self::_populate_form_def_from_data($def, $sanitized_vals);
				$msg = "<div class='error'>$error</div>";
			}
		}

		// get current values from database
		$data = get_option( self::db_key, array() );
		
		// Populate the form $def with values from the database
		$def = self::_populate_form_def_from_data($def, $data[$post_type]);
		$fields = FormGenerator::generate($def);
		$fields .= "<input type='hidden' name='_post_type_prev' value='$post_type'/>";
		include('pages/basic_form.php');
	}
	
	/*------------------------------------------------------------------------------
	Manage custom fields for any post type, built-in or custom.
	------------------------------------------------------------------------------*/
	private static function _page_manage_custom_fields($post_type)
	{
		// Validate post type
		if (!self::_is_existing_post_type($post_type) )
		{
			self::_page_display_error();
			return;
		}

		$action_name = 'custom_content_type_mgr_manage_custom_fields';
		$nonce_name = 'custom_content_type_mgr_manage_custom_fields_nonce';
		$msg = ''; 			// Any validation errors
		$def_cnt = '';	// # of custom field definitions
		// The set of fields that makes up a custom field definition, but stripped of newlines
		// and with some modifications so it can be used inside a javascript variable
		$new_field_def_js = ''; 
		// Existing fields
		$fields = '';
		
		$data = get_option( self::db_key, array() );
		// Save data if it was properly submitted
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			$error_flag = FALSE;
			if (!isset($_POST['custom_fields']))
			{
				$data[$post_type]['custom_fields'] = array(); // all custom fields were deleted
			}
			else
			{
				$data[$post_type]['custom_fields'] = $_POST['custom_fields'];
				foreach ( $data[$post_type]['custom_fields'] as &$cf )
				{
					if ( preg_match('/[^a-z_]/i', $cf['name']))
					{
						$cf['name'] = preg_replace('/[^a-z_]/','',$cf['name']);
						$error_flag = TRUE;
					}
					if ( strlen($cf['name']) > 20 )
					{
						$cf['name'] = substr($cf['name'], 0 , 20);
						$error_flag = TRUE;
					}
				}
			}
			if ($error_flag)
			{
				$msg = '<div class="error">Field names must be URL friendly. A-Z and underscores only! And 20 characters or less. Please review the suggested changes below.</div>';
			}
			else
			{
				update_option( self::db_key, $data );
				$msg = '<div class="updated">Custom fields for <em>'
					.$post_type.'</em> have been <strong>updated</strong></p></div>';			
			}
		}	
	
		// We want to extract a $def for only THIS content_type's custom_fields
		$def = array();
		if ( isset($data[$post_type]['custom_fields']) )
		{
			$def = $data[$post_type]['custom_fields'];
		}
		// count # of custom field definitions --> replaces [+def_i+]
		$def_cnt = count($def);
		
		// We don't need the exact number of form elements, we just need an integer
		// that is sufficiently high so that the ids of Javascript-created elements
		// do not conflict with the ids of PHP-created elements.
		$element_cnt = count($def, COUNT_RECURSIVE);
		
		if (!$def_cnt)
		{
			$msg .= '<div class="updated">The <em>'.$post_type.'</em> post type does not have any custom fields yet. Click the button above to add a custom field.</div>';
		}

		$fields = self::_get_html_field_defs($def);
		//print_r($def); exit; 
		// Gets a form definition ready for use inside of a JS variable
		$new_field_def_js = self::_get_javascript_field_defs();
		
		include('pages/manage_custom_fields.php');
	}
	
	/*------------------------------------------------------------------------------
	List all post types (default page)
	------------------------------------------------------------------------------*/
	private static function _page_show_all_post_types($msg='')
	{	
		$data = get_option( self::db_key, array() );
		$customized_post_types =  array_keys($data);
		$displayable_types = array_merge(self::$built_in_post_types , $customized_post_types);
		$displayable_types = array_unique($displayable_types);
		
		$row_data = '';
		foreach ( $displayable_types as $post_type )
		{
			if ( isset($data[$post_type]['is_active']) && !empty($data[$post_type]['is_active']) )
			{
				$class = 'active';
				$is_active = TRUE;
			}
			else
			{
				$class = 'inactive';
				$is_active = FALSE;
			}

			// Built-in post types use a canned description.
			if ( in_array($post_type, self::$built_in_post_types) ) 
			{
				$description 	= __('Built-in post type');
			}
			// Whereas users define the description for custom post types
			else
			{
				$description 	= self::_get_value($data[$post_type],'description');
			}
			ob_start();
	    	    include('single_content_type_tr.php');
				$row_data .= ob_get_contents();
			ob_end_clean();
		}
		
		include('pages/default.php');
	}

	/*------------------------------------------------------------------------------
	Populate form definition from data (either from the database, or from the $_POST
	array.  The $pt_data should contain only information about one post_type; do not
	pass this function the entire contents of the get_option.
	
	This whole function is necessary because the form generator definition needs
	to know where to find values for its fields.  It's easy when your field names are 
	simple strings, but it's more difficult when you're passing unnamed arrays.
	
	INPUT: $def (mixed) form definition
		$pt_data (mixed) data describing a single post type
	OUTPUT: $def updated with values
	------------------------------------------------------------------------------*/
	private static function _populate_form_def_from_data($def, $pt_data)
	{
		foreach ($def as $node_id => $tmp)
		{
			if ( $node_id == 'supports_title' )
			{			
				if ( !empty($pt_data['supports']) && in_array('title', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'title';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}
			}
			elseif ( $node_id == 'supports_editor' )
			{			
				if ( !empty($pt_data['supports']) && in_array('editor', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'editor';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}
			}
			elseif ( $node_id == 'supports_author' )
			{			
				if ( !empty($pt_data['supports']) && in_array('author', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'author';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'supports_excerpt' )
			{			
				if ( !empty($pt_data['supports']) && in_array('excerpt', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'excerpt';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'supports_thumbnail' )
			{			
				if ( !empty($pt_data['supports']) && in_array('thumbnail', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'thumbnail';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'supports_trackbacks' )
			{			
				if ( !empty($pt_data['supports']) && in_array('trackbacks', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'trackbacks';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}		
			elseif ( $node_id == 'supports_custom-fields' )
			{			
				if ( !empty($pt_data['supports']) && in_array('custom-fields', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'custom-fields';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}			
			elseif ( $node_id == 'supports_comments' )
			{			
				if ( !empty($pt_data['supports']) && in_array('comments', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'comments';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'supports_revisions' )
			{			
				if ( !empty($pt_data['supports']) && in_array('revisions', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'revisions';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'supports_page-attributes' )
			{			
				if ( !empty($pt_data['supports']) && in_array('page-attributes', $pt_data['supports']) )
				{
					$def[$node_id]['value'] = 'page-attributes';
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'rewrite_slug' )
			{			
				if ( !empty($pt_data['rewrite']['slug']) )
				{
					$def[$node_id]['value'] = $pt_data['rewrite']['slug'];
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			elseif ( $node_id == 'rewrite_with_front' )
			{			
				if ( !empty($pt_data['rewrite']['with_front']) )
				{
					$def[$node_id]['value'] = $pt_data['rewrite']['with_front'];
				}
				else
				{
					$def[$node_id]['value'] = '';
				}				
			}
			else
			{
//				print "--------------------------------\nNODE ID: $node_id\n";
//				print "Fieldname: ". $def[$node_id]['name'] . "\n";
				$field_name = $def[$node_id]['name'];
				$def[$node_id]['value'] = self::_get_value($pt_data,$field_name);			
			}
		}
		
		return $def;
			
	}
	
	/*------------------------------------------------------------------------------
	SYNOPSIS: make sure that this is a valid post_type name.
	INPUT: 
		$post_type (str) name of the post type
		$previous_name (str) previous name of the post type (optional). This lets us
		see if the user changed the name of the post type.
	OUTPUT: null if there are no errors, otherwise return a string describing an error.
	------------------------------------------------------------------------------*/
	private static function _post_type_name_has_errors($post_type, $previous_name='')
	{
		$errors = null;
		if ( empty($post_type) )
		{
			return __('Name is required.');
		}
		// Is reserved name?
		elseif ( in_array($post_type, self::$reserved_post_types) )
		{
			return __('Please choose another name. The name you have chosen is reserved.');
		}
		// Reserved prefix 
		elseif ( preg_match('/^'.preg_quote(self::$reserved_prefix).'.*/', $post_type) )
		{
			return __('The post type name cannot begin with ') . self::$reserved_prefix;
		}
		// If this is a new post_type or if the $post_type name has been changed, 
		// ensure that it is not going to overwrite an existing post type name.
		else
		{
			$data = get_option( self::db_key, array() );
			if ( $post_type != $previous_name && in_array( $post_type, array_keys($data) ) )
			{
				return __('That name is already in use.');
			}
		}

		return; // no errors
	}
	
		
	/*------------------------------------------------------------------------------
	Every form element when creating a new post type must be filtered here.
	INPUT: unsanitized $_POST data.
	OUTPUT: filtered data.  Only white-listed values are passed thru to output.
	------------------------------------------------------------------------------*/	
	private static function _sanitize_post_type_def($raw)
	{
		$sanitized = array();
//print_r($raw); exit;
		// This will be empty if none of the "supports" items are checked.
		if (!empty($raw['supports']) )
		{
			$sanitized['supports'] = $raw['supports'];
		}
		unset($raw['supports']); // we manually set this later
/*
		if (isset($raw['rewrite']) && is_array($raw['rewrite']))
		{
			$sanitized['rewrite'] = $raw['rewrite'];
		}
		unset($raw['rewrite']);
*/
 		// Temporary thing...
 		unset($sanitized['rewrite_slug']);
 		unset($sanitized['rewrite_with_front']);
 		
		// We grab everything, then override specific $keys as needed. 
		foreach ($raw as $key => $value )
		{
			if ( !preg_match('/^_.*/', $key) )
			{
				$sanitized[$key] = self::_get_value($raw, $key);
			}
		}
		
		// Specific overrides below:
		// post_type is the only required field
		$sanitized['post_type'] = self::_get_value($raw,'post_type');
		$sanitized['post_type'] = strtolower($sanitized['post_type']);
		$sanitized['post_type'] = preg_replace('/[^a-z|_]/', '_', $sanitized['post_type']);
		$sanitized['post_type'] = substr($sanitized['post_type'], 0, 20);

		// Our form passes integers and strings, but WP req's literal booleans		
		$sanitized['show_ui'] 				= (bool) self::_get_value($raw,'show_ui');
		$sanitized['public'] 				= (bool) self::_get_value($raw,'public');
		$sanitized['show_in_nav_menus'] 	= (bool) self::_get_value($raw,'show_in_nav_menus');
		$sanitized['can_export'] 			= (bool) self::_get_value($raw,'can_export');
		$sanitized['use_default_menu_icon'] = (bool) self::_get_value($raw,'use_default_menu_icon');
		$sanitized['hierarchical'] 			= (bool) self::_get_value($raw,'hierarchical'); 
		
		// Special handling for menu_position bc 0 is handled differently than a literal null
		if ( (int) self::_get_value($raw,'menu_position') )
		{
			$sanitized['menu_position']	= (int) self::_get_value($raw,'menu_position',null);
		}
		else
		{
			$sanitized['menu_position']	= null;
		}
		// menu_icon... the user will lose any URL in the box if they save with this checked!
		if( $sanitized['use_default_menu_icon'] )
		{
			unset($sanitized['menu_icon']);// = null;
		}
		
		if (empty($sanitized['query_var']))
		{
			$sanitized['query_var'] = FALSE;
		}
		// Rewrites
		switch ($sanitized['permalink_action'])
		{
			case '/%postname%/':
				$sanitized['rewrite'] = TRUE;
				break;
			case 'Custom':
				$sanitized['rewrite']['slug'] = $raw['rewrite_slug'];
				$sanitized['rewrite']['with_front'] = (bool) $raw['rewrite_with_front'];
				break;
			case 'Off':
			default:
				$sanitized['rewrite'] = FALSE;
		}

		//print_r($sanitized); exit;

		return $sanitized;
	}

	/*------------------------------------------------------------------------------
	INPUT: $def (mixed) associative array describing a single post-type.
	------------------------------------------------------------------------------*/
	private static function _save_post_type_settings($def)
	{
		$key = $def['post_type'];
		$all_post_types = get_option( self::db_key, array() );
		// Update existing settings if this post-type has already been added
		if ( isset($all_post_types[$key]) )
		{
			$all_post_types[$key] = array_merge($all_post_types[$key], $def);	
		}
		// OR, create a new node in the data structure for our new post-type
		else
		{
			$all_post_types[$key] = $def;
		}
		update_option( self::db_key, $all_post_types );
	}


	/*------------------------------------------------------------------------------
	The custom_fields array consists of form element definitions that are used when
	editing or creating a new post.  But when we want to *edit* that definition, 
	we have to create new form elements that allow us to edit each part of the 
	original definition, e.g. we need a text element to allow us to edit 
	the "label", we need a textarea element to allow us to edit the "description",
	etc.   

	INPUT: $field_def (mixed) a single custom field definition.  Something like:
	
		Array
		(
            [label] => Rating
            [name] => rating
            [description] => MPAA rating
            [type] => dropdown
            [options] => Array
                (
                    [0] => PG
                    [1] => PG-13
                    [2] => R
                )

            [sort_param] => 		
		)
		
	OUTPUT: a modified version of the $custom_field_def_template, with values updated 
	based on the incoming $field_def.  The 'options' are handled in a special way: 
	they are moved to the 'special' key -- this causes the FormGenerator to generate 
	text fields for each one so the user can edit the options for their dropdown.
	------------------------------------------------------------------------------*/
	private static function _transform_data_structure_for_editing($field_def)
	{
		// Collects all 5 translated field definitions for this $field_def
		$translated_defs = array();
		
		// Copying over all elments from the self::$custom_field_def_template
		foreach ( $field_def as $attr => $val )
		{	
			// Is this $attr an editable item for which we must generate a form element?
			if (isset(self::$custom_field_def_template[$attr]) )
			{
				foreach (self::$custom_field_def_template[$attr] as $key => $v )
				{
					$translated_defs[$attr][$key] = self::$custom_field_def_template[$attr][$key];
				}					
			}
			// Special handling: 'options' really belong to 'type'
			elseif ( $attr == 'options' && !empty($val) )
			{
				$translated_defs['type']['special'] = $val;
			}
		}
		
		// Populate the new form definitions with their values from the original
		foreach ( $translated_defs as $field => &$def)
		{
			if ( isset($field_def[$field]))
			{
				$def['value'] = $field_def[$field];
			}
			else
			{
				$def['value'] = '';
			}
			// Associate the group of new elements back to this definition.
			$def['def_i'] = self::$def_i; 
		}
		return $translated_defs;
	}	


	
	//! Public Functions
	/*------------------------------------------------------------------------------
	Load CSS and JS for admin folks in the manager.  Note that we have to verbosely 
	ensure that thickbox css and js are loaded: normally they are tied to the 
	"main content" area of the content type, so thickbox would otherwise fail
	if your custom post_type doesn't use the main content type.
	Errors: TO-DO. 
	------------------------------------------------------------------------------*/
	public static function admin_init()
	{
		// $E = new WP_Error();
		// include('errors.php');
		// self::$Errors = $E;
		wp_register_style('CustomContentTypeManager_class'
			, CUSTOM_CONTENT_TYPE_MGR_URL . '/css/create_or_edit_post_type_class.css');
		wp_register_style('CustomContentTypeManager_gui'
			, CUSTOM_CONTENT_TYPE_MGR_URL . '/css/create_or_edit_post_type.css');
		wp_enqueue_style('CustomContentTypeManager_class');
		wp_enqueue_style('CustomContentTypeManager_gui');	
		// Hand-holding
		wp_enqueue_script( 'thickbox' );
		wp_enqueue_style( 'thickbox' );
			
	}
	
	/*------------------------------------------------------------------------------
	Adds a link to the settings directly from the plugins page.  This filter is 
	called for each plugin, so we need to make sure we only alter the links that
	are displayed for THIS plugin.
	
	INPUT (determined by WordPress):
	$file is the path to plugin's main file (the one with the info header), 
		relative to the plugins directory, e.g. 'custom-content-type-manager/index.php'
	$links is an hash of links to display with the name => translation e.g.
		array('deactivate' => 'Deactivate')
	OUTPUT: $links array.
	------------------------------------------------------------------------------*/
	public static function add_plugin_settings_link($links, $file)
	{
		if ( $file == basename(self::get_basepath()) . '/index.php' ) 
		{
			$settings_link = sprintf('<a href="%s">%s</a>'
				, admin_url( 'options-general.php?page='.self::admin_menu_slug )
				, __('Settings')
			);
			array_unshift( $links, $settings_link );
		}

		return $links;
	}
	
	
	// Defines the diretory for this plugin.
	public static function get_basepath(){
		return dirname(dirname(__FILE__));
	}
	 
	/*------------------------------------------------------------------------------
	Create custom post type menu
	------------------------------------------------------------------------------*/
	public static function create_admin_menu()
	 {
		add_options_page(
			'Custom Content Types',					// page title
			'Custom Content Types',	 				// menu title
			'manage_options', 						// capability
			self::admin_menu_slug, 					// menu-slug (should be unique)
			'CustomContentTypeManager::page_main_controller'	// callback function
		);
	}
	
	
	/*------------------------------------------------------------------------------
	Print errors if they were thrown by the tests. Currently this is triggered as 
	an admin notice so as not to disrupt front-end user access, but if there's an
	error, you should fix it! The plugin may behave erratically!
	------------------------------------------------------------------------------*/
	public static function print_notices()
	{
		if ( !empty(CCTMtests::$errors) )
		{
			$error_items = '';
			foreach ( CCTMtests::$errors as $e )
			{
				$error_items .= "<li>$e</li>";
			}
			print '<div id="custom-post-type-manager-warning" class="error"><p><strong>'
			.__('The &quot;Custom Content Type Manager&quot; plugin encountered errors! It cannot load!')
			.'</strong> '
			."<ul style='margin-left:30px;'>$error_items</ul>"
			.'</p>'
			.'</div>';
		}
	}
	
	
	/*------------------------------------------------------------------------------
	Register custom post-types, one by one. Data is stored in the wp_options table
	in a structure that matches exactly what the register_post_type() function
	expectes as arguments. 
	See wp-includes/posts.php for examples of how WP registers the default post types
	
	$def = Array
	(
	    'supports' => Array
	        (
	            'title',
	            'editor'
	        ),
	
	    'post_type' => 'book',
	    'singular_label' => 'Book',
	    'label' => 'Books',
	    'description' => 'What I&#039;m reading',
	    'show_ui' => 1,
	    'capability_type' => 'post',
	    'public' => 1,
	    'menu_position' => '10',
	    'menu_icon' => '', 
	    'custom_content_type_mgr_create_new_content_type_nonce' => 'd385da6ba3',
	    'Submit' => 'Create New Content Type',
	    'show_in_nav_menus' => '', 
	    'can_export' => '', 
	    'is_active' => 1,
	);

	FUTURE:
		register_taxonomy( $post_type,
			$cpt_post_types,
			array( 'hierarchical' => get_disp_boolean($cpt_tax_type["hierarchical"]),
			'label' => $cpt_label,
			'show_ui' => get_disp_boolean($cpt_tax_type["show_ui"]),
			'query_var' => get_disp_boolean($cpt_tax_type["query_var"]),
			'rewrite' => array('slug' => $cpt_rewrite_slug),
			'singular_label' => $cpt_singular_label,
			'labels' => $cpt_labels
		) );
	------------------------------------------------------------------------------*/
	public static function register_custom_post_types() 
	{	

		$data = get_option( self::db_key, array() );
		foreach ($data as $post_type => $def) 
		{
			if ( isset($def['is_active']) 
				&& !empty($def['is_active']) 
				&& !in_array($post_type, self::$built_in_post_types)) 
			{	
				register_post_type( $post_type, $def );
			}
		}
	
	}
	
		
	/*------------------------------------------------------------------------------
	This is the function called when someone clicks on the settings page.  
	The job of a controller is to process requests and route them.
	------------------------------------------------------------------------------*/
	public static function page_main_controller() 
	{
		if (!current_user_can('manage_options'))  
		{
			wp_die( __('You do not have sufficient permissions to access this page.') );
		}
		$action 		= (int) self::_get_value($_GET,self::action_param,0);
		$post_type 		= self::_get_value($_GET,self::post_type_param);
		
		switch($action)
		{
			case 1: // create new custom post type
				self::_page_create_new_post_type();
				break;
			case 2: // update existing custom post type. Override form def.
				self::$post_type_form_definition['post_type']['type'] = 'readonly';
				self::$post_type_form_definition['post_type']['description'] = 'The name of the post-type cannot be changed. The name may show up in your URLs, e.g. ?movie=star-wars. This will also make a new theme file available, starting with prefix named "single-", e.g. <strong>single-movie.php</strong>.';
				self::_page_edit_post_type($post_type);
				break;
			case 3: // delete existing custom post type
				self::_page_delete_post_type($post_type);
				break;
			case 4: // Manage Custom Fields for existing post type	
				self::_page_manage_custom_fields($post_type);
				break;
			case 5: // TODO: Manage Taxonomies for existing post type
				break;
			case 6: // Activate post type
				self::_page_activate_post_type($post_type);
				break;
			case 7: // Deactivate post type
				self::_page_deactivate_post_type($post_type);
				break;
			default: // List all post types	
				self::_page_show_all_post_types();
		}
	}
}
/*EOF*/