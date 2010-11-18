<?php
/*------------------------------------------------------------------------------
CCTM = Custom Content Type Manager

This is the main class for the Custom Content Type Manager plugin.

This class handles the creation and management of custom post-types (also 
referred to as 'content-types').  It requires the FormGenerator.php, 
StandardizedCustomFields.php, and the CCTMtests.php files/classes to work.
------------------------------------------------------------------------------*/
class CCTM
{	
	const name 		= 'Custom Content Type Manager';
	const txtdomain = 'custom-content-type-mgr'; // used for localization
	
	// Required versions (referenced in the CCTMtest class).
	const wp_req_ver 	= '3.0.1';
	const php_req_ver 	= '5.2.6';
	const mysql_req_ver = '5.0.41';

	// Used to uniquely identify an option_name in the wp_options table 
	// ALL data describing the post types and their custom fields lives there.
	// DELETE FROM `wp_options` WHERE option_name='custom_content_types_mgr_data'; 
	// would clean out everything this plugin knows.
	const db_key 	= 'custom_content_types_mgr_data';
	
	// Used to uniquely identify this plugin's menu page in the WP manager
	const admin_menu_slug = 'custom_content_type_mgr';

	// These parameters identify where in the $_GET array we can find the values
	// and how URLs are constructed, e.g. some-admin-page.php?a=123&pt=xyz
	const action_param 			= 'a';
	const post_type_param 		= 'pt';

	// integer iterator used to uniquely identify groups of field definitions for 
	// CSS and $_POST variables
	public static $def_i = 0; 

	// Built-in post-types that can have custom fields, but cannot be deleted.
	public static $built_in_post_types = array('post','page');
	
	// Names that are off-limits for custom post types b/c they're already used by WP
	public static $reserved_post_types = array('post','page','attachment','revision'
		,'nav_menu','nav_menu_item');
	
	// Custom field names are not allowed to use the same names as any column in wp_posts
	public static $reserved_field_names	= array('ID','post_author','post_date','post_date_gmt',
		'post_content','post_title','post_excerpt','post_status','comment_status','ping_status',
		'post_password','post_name','to_ping','pinged','post_modified','post_modified_gmt',
		'post_content_filtered','post_parent','guid','menu_order','post_type','post_mime_type',
		'comment_count');
	
	// Future-proofing: post-type names cannot begin with 'wp_'
	// See: http://codex.wordpress.org/Custom_Post_Types	
	// FUTURE: List any other reserved prefixes here (if any)
	public static $reserved_prefixes = array('wp_');

	public static $Errors;	// used to store WP_Error object (FUTURE TODO)
	
	/*------------------------------------------------------------------------------
	This var stores the big definition for the forms that allow users to define 
	custom post-types. The form is generated in a way so that when it is posted, it 
	can be easily passed to WP's register_post_type() function.

	We populate the value via the setter function, _set_post_type_form_definition(), 
	but we do not have a getter. Since we are lazy, and PHP doesn't require 
	getters/setters, we would have forgone the setter function if possible, but we 
	had to use a setter simply to avoid the PHP syntax errors that would have 
	errupted had we tried something like this:
	
		public $myvar = array( 'val' => __('somevalue') );	
	------------------------------------------------------------------------------*/
	public static $post_type_form_definition = array();
	
	/*------------------------------------------------------------------------------
	This array defines the form used for all new custom field definitions.
	The variable is populated via a setter: _set_custom_field_def_template() for
	the same reason as the $post_type_form_definition var above (see above).
	
	See the _page_manage_custom_fields() function for when and how these forms 
	are used and handled.
	------------------------------------------------------------------------------*/
	public static $custom_field_def_template = array();

	
	//! Private Functions
	/*------------------------------------------------------------------------------
	Generate HTML portion of our manage custom fields form. This is in distinction
	to the JS portion of the form, which uses a slightly different format.

	self::$def_i is used to track the definition #.  All 5 output fields will use 
	the same $def_i number to identify their place in the $_POST array.
	
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

	OUTPUT: An HTML form, length depends on the # of field defs.
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
	ride some of the names used by the _get_html_field_defs() function so the 
	resulting HTML/Javascript will inherit values from Javascript variables dynamically
	as the user adds new form fields on the fly.
	
	Here +def_i+ represents a JS concatenation, where def_i is a JS variable.
	------------------------------------------------------------------------------*/
	private static function _get_javascript_field_defs()
	{
		$def = self::$custom_field_def_template;
		foreach ($def as $row_id => &$field)
		{
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
			<a class="button" href="#" onClick="javascript:removeDiv(this.parentNode.id);">'
			.__('Remove This Field').'</a>
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
	
	Built-in post types 'page' and 'post' are considered valid (i.e. existing) by 
	default, even if they haven't been explicitly defined for use by this plugin
	so long as the 2nd argument, $search_built_ins, is not overridden to false.
	
	INPUT: 
		$post_type (string) the lowercase database slug identifying a post type.
		$search_built_ins (boolean) whether or not to search inside the 
			$built_in_post_types array. 
			
	OUTPUT: boolean true | false indicating whether this is a valid post-type
	------------------------------------------------------------------------------*/
	private static function _is_existing_post_type($post_type, $search_built_ins=true)
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
			return false;
		}
		// Check to see if we've stored this $post_type before
		elseif ( array_key_exists($post_type, $data) )
		{
			return true;
		}
		// Check the built-ins
		elseif ( $search_built_ins && in_array($post_type, self::$built_in_post_types) )
		{
			return true;
		}
		else
		{
			return false;
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

		// get current values from database (if any)
		$data = get_option( self::db_key, array() );
		$data[$post_type]['is_active'] = 1;
		update_option( self::db_key, $data );
		
		// Often, PHP scripts use the header() function to refresh a page, but
		// WP has already claimed those, so we use a JavaScript refresh instead.
		// Refreshing the page ensures that active post types are added to menus.	
		$msg = '
			<script type="text/javascript">
				window.location.replace("?page='.self::admin_menu_slug.'");
			</script>';
		// I think this $msg gets lost, but future TODO: use a 'flash' msg to display it 
		// even after the page refresh.	
		self::_page_show_all_post_types($msg);  
	}
	
	/*------------------------------------------------------------------------------
	Create a new post type
	------------------------------------------------------------------------------*/
	private static function _page_create_new_post_type()
	{
		// Variables for our template (I'm cheating here, loading in-line styles
		// becase the enqueue stuff is too heavy). TODO: use enqueue function to 
		// load this only on the required pages.
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
			$error_msg = self::_post_type_name_has_errors($sanitized_vals['post_type']);

			if ( empty($error_msg) )
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
					
				$msg = "<div class='error'>$error_msg</div>";
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
			<script type="text/javascript">
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
		if (!self::_is_existing_post_type($post_type, false ) )
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
	Edit an existing post type. Changing the unique post-type identifier (i.e. name)
	is not allowed. 
	------------------------------------------------------------------------------*/
	private static function _page_edit_post_type($post_type)
	{
		// We can't edit built-in post types
		if (!self::_is_existing_post_type($post_type, false ) )
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
			$error_msg = self::_post_type_name_has_errors($sanitized_vals['post_type']);

			if ( empty($error_msg) )
			{				
				self::_save_post_type_settings($sanitized_vals);

				$msg = '
					<script type="text/javascript">
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
				$msg = "<div class='error'>$error_msg</div>";
			}
		}

		// get current values from database
		$data = get_option( self::db_key, array() );
		
		// Populate the form $def with values from the database
		$def = self::_populate_form_def_from_data($def, $data[$post_type]);
		$fields = FormGenerator::generate($def);
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
		
		// Validate/Save data if it was properly submitted
		if ( !empty($_POST) && check_admin_referer($action_name,$nonce_name) )
		{
			$error_msg = array(); // used as a flag
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
						$error_msg[] = sprintf(
							__('%s contains invalid characters.',CCTM::txtdomain)
							, '<strong>'.$cf['name'].'</strong>');
						$cf['name'] = preg_replace('/[^a-z_]/','',$cf['name']);
					}
					if ( strlen($cf['name']) > 20 )
					{
						$cf['name'] = substr($cf['name'], 0 , 20);
						$error_msg[] = sprintf(
							__('%s is too long.',CCTM::txtdomain)
							, '<strong>'.$cf['name'].'</strong>');
					}
					if ( in_array($cf['name'], self::$reserved_field_names ) )
					{
						$error_msg[] = sprintf(
							__('%s is a reserved name.',CCTM::txtdomain)
							, '<strong>'.$cf['name'].'</strong>');						
					}
				}
			}
			if ($error_msg)
			{
				foreach ( $error_msg as &$e )
				{
					$e = '<li>'.$e.'</li>';
				}
				
				$msg = sprintf('<div class="error">
					<h3>%1$s</h3>
					%2$s %3$s %4$s
					<ul style="margin-left:30px">
						%5$s
					</ul>
					</div>'
					, __('There were errors in the names of your custom fields.', CCTM::txtdomain)
					, __('Names must not exceed 20 characters in length.', CCTM::txtdomain)
					, __('Names may contain the letters a-z and underscores only.', CCTM::txtdomain)
					, __('You cannot name your field using any reserved name.', CCTM::txtdomain)
					, implode("\n", $error_msg)
				);
			}
			else
			{
				update_option( self::db_key, $data );
				$msg = sprintf('<div class="updated">%s</p></div>'
						, sprintf(__('Custom fields for %s have been <strong>updated</strong>', CCTM::txtdomain)
							, '<em>'.$post_type.'</em>'
						)
					);
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
			$x = sprintf( __('The %s post type does not have any custom fields yet.', CCTM::txtdomain)
				, '<em>'.$post_type.'</em>' );
			$y = __('Click the button above to add a custom field.', CCTM::txtdomain );
			$msg .= sprintf('<div class="updated">%s %s</div>', $x, $y);
		}

		$fields = self::_get_html_field_defs($def);
		//print_r($def); exit; 
		// Gets a form definition ready for use inside of a JS variable
		$new_field_def_js = self::_get_javascript_field_defs();
		
		include('pages/manage_custom_fields.php');
	}
	
	/*------------------------------------------------------------------------------
	Show what a single page for this custom post-type might look like.  This is 
	me throwing a bone to template editors and creators.
	
	I'm using a tpl and my parse() function because I got to print out sample PHP
	code and it's too much of a pain in the ass to include PHP without it executing.
	------------------------------------------------------------------------------*/
	private static function _page_sample_template($post_type)
	{
		// Validate post type
		if (!self::_is_existing_post_type($post_type) )
		{
			self::_page_display_error();
			return;
		}

		$current_theme_name = get_current_theme(); 
		$current_theme_path = get_stylesheet_directory(); 
		
		$hash = array();
		$data = get_option( self::db_key, array() );
		$tpl = file_get_contents( CCTM_PATH.'/tpls/sample_template_code.tpl');
		$tpl = htmlentities($tpl);
//		print '<textarea width="80">'.$tpl.'</textarea>'; exit;
		$msg = sprintf( __('WordPress supports a custom theme file for each registered post-type (content-type). Copy the text below into a file named <strong>%s</strong> and save it into your active theme.', CCTM::txtdomain)
			, 'single-'.$post_type.'.php'
		);
		$msg .= sprintf( __('You are currently using the %1$s theme. Save the file into the %2$s directory.',CCTM::txtdomain)
			, '<strong>'.$current_theme_name.'</strong>'
			, '<strong>'.$current_theme_path.'</strong>'
		);
		$sample_code = StandardizedCustomFields::parse($tpl, $hash);
		//$sampe_code = htmlentities($sample_code);
		//$sampe_code = htmlspecialchars_decode($sample_code);

		include('pages/sample_template.php');
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
				$is_active = true;
			}
			else
			{
				$class = 'inactive';
				$is_active = false;
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
	OUTPUT: null if there are no errors, otherwise return a string describing an error.
	------------------------------------------------------------------------------*/
	private static function _post_type_name_has_errors($post_type)
	{
		$errors = null;
		
		$taxonomy_names_array = get_taxonomies('','names');

		if ( empty($post_type) )
		{
			return __('Name is required.', CCTM::txtdomain);
		}

		foreach ( self::$reserved_prefixes as $rp )
		{
			if ( preg_match('/^'.preg_quote($rp).'.*/', $post_type) )
			{
				return sprintf( __('The post type name cannot begin with %s because that is a reserved prefix.', CCTM::txtdomain)
					, $rp);
			}		
		}

		
		// Is reserved name?
		if ( in_array($post_type, self::$reserved_post_types) )
		{
			$msg = __('Please choose another name.', CCTM::txtdomain );
			$msg .= ' ';
			$msg .= sprintf( __('%s is a reserved name.', CCTM::txtdomain )
				, '<strong>'.$post_type.'</strong>' );
			return $msg;
		}
		// Make sure the post-type name does not conflict with any registered taxonomies
		elseif ( in_array( $post_type, $taxonomy_names_array) )
		{
			$msg = __('Please choose another name.', CCTM::txtdomain );
			$msg .= ' ';
			$msg .= sprintf( __('%s is already in use as a registered taxonomy name.', CCTM::txtdomain)
				, $post_type );
		}
		// If this is a new post_type or if the $post_type name has been changed, 
		// ensure that it is not going to overwrite an existing post type name.
		else
		{
			$data = get_option( self::db_key, array() );
			if ( in_array( $post_type, array_keys($data) ) )
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
			$sanitized['query_var'] = false;
		}
		// Rewrites
		switch ($sanitized['permalink_action'])
		{
			case '/%postname%/':
				$sanitized['rewrite'] = true;
				break;
			case 'Custom':
				$sanitized['rewrite']['slug'] = $raw['rewrite_slug'];
				$sanitized['rewrite']['with_front'] = (bool) $raw['rewrite_with_front'];
				break;
			case 'Off':
			default:
				$sanitized['rewrite'] = false;
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
	This is sorta a reflexive form definition: it defines the form required to 
	define a form. Not that names imply arrays, e.g. name="custom_fields[3][label]".
	This is intentional: since all custom field definitions are stored as a serialized
	array in the wp_options table, we have to treat all defs as a kind of recordset
	(i.e. an array of similar hashes).
	 
	[+def_i+] gets used by Javascript for on-the-fly adding of form fields (where
	def_i is a Javascript variable indicating the definition number (or i for integer).
	------------------------------------------------------------------------------*/
	private static function _set_custom_field_def_template()
	{
		$def['label']['name']			= 'custom_fields[[+def_i+]][label]';
		$def['label']['label']			= __('Label', CCTM::txtdomain);
		$def['label']['value']			= '';
		$def['label']['extra']			= '';			
		$def['label']['description']	= '';
		$def['label']['type']			= 'text';
		$def['label']['sort_param']		= 1;

		$def['name']['name']			= 'custom_fields[[+def_i+]][name]';
		$def['name']['label']			= __('Name', CCTM::txtdomain);
		$def['name']['value']			= '';
		$def['name']['extra']			= '';			
		$def['name']['description']		= __('The name identifies the <em>option_name</em> in the <em>wp_postmeta</em> database table. You will use this name in your template functions to identify this custom field.', CCTM::txtdomain);
		$def['name']['type']			= 'text';
		$def['name']['sort_param']		= 2;

		$def['description']['name']			= 'custom_fields[[+def_i+]][description]';
		$def['description']['label']		= __('Description',CCTM::txtdomain);
		$def['description']['value']		= '';
		$def['description']['extra']		= '';
		$def['description']['description']	= '';
		$def['description']['type']			= 'textarea';
		$def['description']['sort_param']	= 3;

		$def['type']['name']		= 'custom_fields[[+def_i+]][type]';
		$def['type']['label']		= __('Input Type', CCTM::txtdomain);
		$def['type']['value']		= 'text';
		$def['type']['extra']		= ' onchange="javascript:addRemoveDropdown(this.parentNode.id,this.value, [+def_i+])"';
		$def['type']['description']	= '';
		$def['type']['type']		= 'dropdown';
		$def['type']['options']		= array('checkbox','dropdown','media','relation','text','textarea','wysiwyg');
		$def['type']['sort_param']	= 4;

		$def['sort_param']['name']			= 'custom_fields[[+def_i+]][sort_param]';
		$def['sort_param']['label']			= __('Sort Order',CCTM::txtdomain);
		$def['sort_param']['value']			= '';
		$def['sort_param']['extra']			= ' size="2" maxlength="4"';
		$def['sort_param']['description']	= __('This controls where this field will appear on the page. Fields with smaller numbers will appear higher on the page.',CCTM::txtdomain);
		$def['sort_param']['type']			= 'text';
		$def['sort_param']['sort_param']	= 5;


		self::$custom_field_def_template = $def;
	}

	/*------------------------------------------------------------------------------
	Used when creating or editing Post Types	
	------------------------------------------------------------------------------*/
	private static function _set_post_type_form_definition()
	{
		$def =	array();
		
		$def['post_type']['name'] 			= 'post_type';
		$def['post_type']['label'] 			= __('Name', CCTM::txtdomain). ' *';
		$def['post_type']['value'] 			= '';
		$def['post_type']['extra'] 			= '';
		$def['post_type']['description'] 	= __('Unique singular name to identify this post type in the database, e.g. "movie","book". This may show up in your URLs, e.g. ?movie=star-wars. This will also make a new theme file available, starting with prefix named "single-", e.g. <strong>single-movie.php</strong>. The name should be lowercase with only letters and underscores. This name cannot be changed!', CCTM::txtdomain);
		$def['post_type']['type'] 			= 'text';
		$def['post_type']['sort_param'] 	= 1;
			
		$def['singular_label']['name']			= 'singular_label';
		$def['singular_label']['label']			= __('Singular Label', CCTM::txtdomain);
		$def['singular_label']['value']			= '';
		$def['singular_label']['extra']			= '';
		$def['singular_label']['description']	= __('Human readable single instance of this content type, e.g. "Post"', CCTM::txtdomain);
		$def['singular_label']['type']			= 'text';
		$def['singular_label']['sort_param']	= 2;

		$def['label']['name']			= 'label';
		$def['label']['label']			= __('Menu Label (Plural)', CCTM::txtdomain);
		$def['label']['value']			= '';
		$def['label']['extra']			= '';
		$def['label']['description']	= __('Plural name used in the admin menu, e.g. "Posts"', CCTM::txtdomain);
		$def['label']['type']			= 'text';
		$def['label']['sort_param']		= 3;

		$def['description']['name']			= 'description';
		$def['description']['label']		= __('Description', CCTM::txtdomain);
		$def['description']['value']		= '';
		$def['description']['extra']		= '';
		$def['description']['description']	= '';	
		$def['description']['type']			= 'textarea';
		$def['description']['sort_param']	= 4;

		$def['show_ui']['name']			= 'show_ui';
		$def['show_ui']['label']			= __('Show Admin User Interface', CCTM::txtdomain);
		$def['show_ui']['value']			= '1';
		$def['show_ui']['extra']			= '';
		$def['show_ui']['description']	= __('Should this post type be visible on the back-end?', CCTM::txtdomain);
		$def['show_ui']['type']			= 'checkbox';
		$def['show_ui']['sort_param']	= 5;

		$def['capability_type']['name']			= 'capability_type';
		$def['capability_type']['label']		= __('Capability Type', CCTM::txtdomain);
		$def['capability_type']['value']		= 'post';
		$def['capability_type']['extra']		= '';
		$def['capability_type']['description']	= __('The post type to use for checking read, edit, and delete capabilities. Default: "post"', CCTM::txtdomain);
		$def['capability_type']['type']			= 'text';
		$def['capability_type']['sort_param']	= 6;

		$def['public']['name']			= 'public';
		$def['public']['label']			= __('Public', CCTM::txtdomain);
		$def['public']['value']			= '1';
		$def['public']['extra']			= '';
		$def['public']['description']	= __('Should these posts be visible on the front-end?', CCTM::txtdomain);
		$def['public']['type']			= 'checkbox';
		$def['public']['sort_param']	= 7;
	
		$def['hierarchical']['name']		= 'hierarchical';
		$def['hierarchical']['label']		= __('Hierarchical', CCTM::txtdomain);
		$def['hierarchical']['value']		= '';
		$def['hierarchical']['extra']		= '';
		$def['hierarchical']['description']	= __('Allows parent to be specified (Page Attributes should be checked)', CCTM::txtdomain);
		$def['hierarchical']['type']		= 'checkbox';
		$def['hierarchical']['sort_param']	= 8;
		
		$def['supports_title']['name']			= 'supports[]';
		$def['supports_title']['id']			= 'supports_title';
		$def['supports_title']['label']			= __('Title', CCTM::txtdomain);
		$def['supports_title']['value']			= 'title';
		$def['supports_title']['checked_value'] = 'title';
		$def['supports_title']['extra']			= '';
		$def['supports_title']['description']	= __('Post Title', CCTM::txtdomain);
		$def['supports_title']['type']			= 'checkbox';
		$def['supports_title']['sort_param']	= 20;

		$def['supports_editor']['name']			= 'supports[]';
		$def['supports_editor']['id']			= 'supports_editor';
		$def['supports_editor']['label']		= __('Content', CCTM::txtdomain);
		$def['supports_editor']['value']		= 'editor';
		$def['supports_editor']['checked_value'] = 'editor';
		$def['supports_editor']['extra']		= '';
		$def['supports_editor']['description']	= __('Main content block.', CCTM::txtdomain);
		$def['supports_editor']['type']			= 'checkbox';
		$def['supports_editor']['sort_param']	= 21;

		$def['supports_author']['name']			= 'supports[]';
		$def['supports_author']['id']			= 'supports_author';
		$def['supports_author']['label']		= __('Author', CCTM::txtdomain);
		$def['supports_author']['value']		= '';
		$def['supports_author']['checked_value'] = 'author';
		$def['supports_author']['extra']		= '';
		$def['supports_author']['description']	= __('Track the author.', CCTM::txtdomain);
		$def['supports_author']['type']			= 'checkbox';
		$def['supports_author']['sort_param']	= 22;

		$def['supports_thumbnail']['name']		= 'supports[]';
		$def['supports_thumbnail']['id'] 		= 'supports_thumbnail';
		$def['supports_thumbnail']['label'] 	= __('Thumbnail', CCTM::txtdomain);
		$def['supports_thumbnail']['value'] 	= '';
		$def['supports_thumbnail']['checked_value' ] = 'thumbnail';
		$def['supports_thumbnail']['extra'] 		= '';
		$def['supports_thumbnail']['description'] 	= __('Featured image (the activetheme must also support post-thumbnails)', CCTM::txtdomain);
		$def['supports_thumbnail']['type'] 			= 'checkbox';
		$def['supports_thumbnail']['sort_param'] 	= 23;

		$def['supports_excerpt']['name']			= 'supports[]';
		$def['supports_excerpt']['id']				= 'supports_excerpt';
		$def['supports_excerpt']['label']			= __('Excerpt', CCTM::txtdomain);
		$def['supports_excerpt']['value']			= '';
		$def['supports_excerpt']['checked_value'] = 'excerpt';
		$def['supports_excerpt']['extra']			= '';
		$def['supports_excerpt']['description']		= __('Small summary field.', CCTM::txtdomain);
		$def['supports_excerpt']['type']			= 'checkbox';
		$def['supports_excerpt']['sort_param']	= 24;

		$def['supports_trackbacks']['name']				= 'supports[]';
		$def['supports_trackbacks']['id']				= 'supports_trackbacks';
		$def['supports_trackbacks']['label']			= __('Trackbacks', CCTM::txtdomain);
		$def['supports_trackbacks']['value']			= '';
		$def['supports_trackbacks']['checked_value']	= 'trackbacks';
		$def['supports_trackbacks']['extra']			= '';
		$def['supports_trackbacks']['description']		= '';
		$def['supports_trackbacks']['type']				= 'checkbox';
		$def['supports_trackbacks']['sort_param']		= 25;

		$def['supports_custom-fields']['name']			= 'supports[]';
		$def['supports_custom-fields']['id']			= 'supports_custom-fields';
		$def['supports_custom-fields']['label']			= __('Supports Custom Fields', CCTM::txtdomain);
		$def['supports_custom-fields']['value']			= '';
		$def['supports_custom-fields']['checked_value'] = 'custom-fields';
		$def['supports_custom-fields']['extra']			= '';
		$def['supports_custom-fields']['description']	= '';
		$def['supports_custom-fields']['type']			= 'checkbox';
		$def['supports_custom-fields']['sort_param']	= 26;

		$def['supports_comments']['name']			= 'supports[]';
		$def['supports_comments']['id']				= 'supports_comments';
		$def['supports_comments']['label']			= __('Enable Comments', CCTM::txtdomain);
		$def['supports_comments']['value']			= '';
		$def['supports_comments']['checked_value'] 	= 'comments';
		$def['supports_comments']['extra']			= '';
		$def['supports_comments']['description']	= '';
		$def['supports_comments']['type']			= 'checkbox';
		$def['supports_comments']['sort_param']		= 27;

		$def['supports_revisions']['name']			= 'supports[]';
		$def['supports_revisions']['id']			= 'supports_revisions';
		$def['supports_revisions']['label']			= __('Store Revisions', CCTM::txtdomain);
		$def['supports_revisions']['value']			= '';
		$def['supports_revisions']['checked_value'] = 'revisions';
		$def['supports_revisions']['extra']			= '';
		$def['supports_revisions']['description']	= '';
		$def['supports_revisions']['type']			= 'checkbox';
		$def['supports_revisions']['sort_param']	= 28;

		$def['supports_page-attributes']['name']			= 'supports[]';
		$def['supports_page-attributes']['id']				= 'supports_page-attributes';
		$def['supports_page-attributes']['label']			= __('Enable Page Attributes', CCTM::txtdomain);
		$def['supports_page-attributes']['value']			= '';
		$def['supports_page-attributes']['checked_value'] 	= 'page-attributes';
		$def['supports_page-attributes']['extra']			= '';
		$def['supports_page-attributes']['description']		= __('(template and menu order; hierarchical must be checked)', CCTM::txtdomain);
		$def['supports_page-attributes']['type']			= 'checkbox';
		$def['supports_page-attributes']['sort_param']		= 29;

			
		$def['menu_position']['name']			= 'menu_position';
		$def['menu_position']['label']			= __('Menu Position', CCTM::txtdomain);
		$def['menu_position']['value']			= '';
		$def['menu_position']['extra']			= '';
		$def['menu_position']['description']	= 
			sprintf('%1$s 
				<ul style="margin-left:40px;">
					<li><strong>5</strong> - %2$s</li>
					<li><strong>10</strong> - %3$s</li>
					<li><strong>20</strong> - %4$s</li>
					<li><strong>60</strong> - %5$s</li>
					<li><strong>100</strong> - %6$s</li>
				</ul>'
				, __('This setting determines where this post type should appear in the left-hand admin menu. Default: null (below Comments)', CCTM::txtdomain)
				, __('below Posts', CCTM::txtdomain)
				, __('below Media', CCTM::txtdomain)
				, __('below Posts', CCTM::txtdomain)
				, __('below Pages', CCTM::txtdomain)
				, __('below first separator', CCTM::txtdomain)
				, __('below second separator', CCTM::txtdomain)
			);
		$def['menu_position']['type']			= 'text';
		$def['menu_position']['sort_param']		= 30;

			
		$def['menu_icon']['name']			= 'menu_icon';
		$def['menu_icon']['label']			= __('Menu Icon', CCTM::txtdomain);
		$def['menu_icon']['value']			= '';
		$def['menu_icon']['extra']			= '';
		$def['menu_icon']['description']	= __('Menu icon URL.', CCTM::txtdomain);
		$def['menu_icon']['type']			= 'text';
		$def['menu_icon']['sort_param']		= 31;

		$def['use_default_menu_icon']['name']			= 'use_default_menu_icon';
		$def['use_default_menu_icon']['label']			= __('Use Default Menu Icon', CCTM::txtdomain);
		$def['use_default_menu_icon']['value']			= '1';
		$def['use_default_menu_icon']['extra']			= '';
		$def['use_default_menu_icon']['description']	= __('If checked, your post type will use the posts icon', CCTM::txtdomain);
		$def['use_default_menu_icon']['type']			= 'checkbox';
		$def['use_default_menu_icon']['sort_param']		= 32;

		$def['rewrite_slug']['name']		= 'rewrite_slug';
		$def['rewrite_slug']['label']		= __('Rewrite Slug', CCTM::txtdomain);
		$def['rewrite_slug']['value']		= '';
		$def['rewrite_slug']['extra']		= '';
		$def['rewrite_slug']['description']	= __("Prepend posts with this slug - defaults to post type's name", CCTM::txtdomain);
		$def['rewrite_slug']['type']		= 'text';
		$def['rewrite_slug']['sort_param']	= 35;

		$def['rewrite_with_front']['name']			= 'rewrite_with_front';
		$def['rewrite_with_front']['label']			= __('Rewrite with Permalink Front', CCTM::txtdomain);
		$def['rewrite_with_front']['value']			= '1';
		$def['rewrite_with_front']['extra']			= '';
		$def['rewrite_with_front']['description']	= __("Allow permalinks to be prepended with front base - defaults to checked", CCTM::txtdomain);
		$def['rewrite_with_front']['type']			= 'checkbox';
		$def['rewrite_with_front']['sort_param']	= 35;

		$def['rewrite']['name']			= 'permalink_action';
		$def['rewrite']['label']		= __('Permalink Action', CCTM::txtdomain);
		$def['rewrite']['value']		= 'Off';
		$def['rewrite']['options']		= array('Off','/%postname%/'); // ,'Custom'),
		$def['rewrite']['extra']		= '';
		$def['rewrite']['description']	= sprintf(
			'%1$s
			<ul style="margin-left:20px;">
				<li><strong>Off</strong> - %2$s</li>
				<li><strong>/%postname%/</strong> - %3$s</li>
				<!--li><strong>Custom</strong> - Evaluate the contents of slug</li-->
			<ul>'
				, __('Use permalink rewrites for this post_type? Default: Off', CCTM::txtdomain)
				, __('URLs for custom post_types will always look like: http://site.com/?post_type=book&p=39 even if the rest of the site is using a different permalink structure.', CCTM::txtdomain)
				, __('You MUST use the custom permalink structure: "/%postname%/". Other formats are <strong>not</strong> supported.  Your URLs will look like http://site.com/movie/star-wars/', CCTM::txtdomain)
			);
		$def['rewrite']['type']			= 'dropdown';
		$def['rewrite']['sort_param']	= 37;


		$def['query_var']['name']			= 'query_var';
		$def['query_var']['label']			= __('Query Variable', CCTM::txtdomain);
		$def['query_var']['value']			= '';
		$def['query_var']['extra']			= '';
		$def['query_var']['description']	= __('(optional) Name of the query var to use for this post type.
			E.g. "movie" would make for URLs like http://site.com/?movie=star-wars. 
			If blank, the default structure is http://site.com/?post_type=movie&p=18', CCTM::txtdomain);
		$def['query_var']['type']			= 'text';
		$def['query_var']['sort_param']	= 38;

		$def['can_export']['name']			= 'can_export';
		$def['can_export']['label']			= __('Can Export', CCTM::txtdomain);
		$def['can_export']['value']			= '1';
		$def['can_export']['extra']			= '';
		$def['can_export']['description']	= __('Can this post_type be exported.', CCTM::txtdomain);
		$def['can_export']['type']			= 'checkbox';
		$def['can_export']['sort_param']		= 40;

		$def['show_in_nav_menus']['name']			= 'show_in_nav_menus';
		$def['show_in_nav_menus']['label']			= __('Show in Nav Menus', CCTM::txtdomain);
		$def['show_in_nav_menus']['value']			= '1';
		$def['show_in_nav_menus']['extra']			= '';
		$def['show_in_nav_menus']['description']	= __('Whether post_type is available for selection in navigation menus. Default: value of public argument', CCTM::txtdomain);
		$def['show_in_nav_menus']['type']			= 'checkbox';
		$def['show_in_nav_menus']['sort_param']	= 40;
	
		self::$post_type_form_definition = $def;
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
    	load_plugin_textdomain( CCTM::txtdomain, '', CCTM_PATH );
	
		// Set our form defs in this, our makeshift constructor.
		self::_set_post_type_form_definition();
		self::_set_custom_field_def_template();
		
		// $E = new WP_Error();
		// include('errors.php');
		// self::$Errors = $E;
		wp_register_style('CCTM_class'
			, CCTM_URL . '/css/create_or_edit_post_type_class.css');
		wp_register_style('CCTM_gui'
			, CCTM_URL . '/css/create_or_edit_post_type.css');
		wp_enqueue_style('CCTM_class');
		wp_enqueue_style('CCTM_gui');	
		// Hand-holding: If your custom post-types omit the main content block, 
		// then thickbox will not be queued.
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
			'CCTM::page_main_controller'	// callback function
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
			$msg = sprintf( __('The %s plugin encountered errors! It cannot load!', CCTM::txtdomain)
				, CCTM::name);
			printf('<div id="custom-post-type-manager-warning" class="error">
				<p>
					<strong>%$1s</strong>
					<ul style="margin-left:30px;">
						%2$s
					</ul>
				</p>
				</div>'
				, $msg
				, $error_items);
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
				self::$post_type_form_definition['post_type']['description'] = __('The name of the post-type cannot be changed. The name may show up in your URLs, e.g. ?movie=star-wars. This will also make a new theme file available, starting with prefix named "single-", e.g. <strong>single-movie.php</strong>.',CCTM::txtdomain);
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
			case 8: // Show an example of custom field template
				self::_page_sample_template($post_type);
				break;
			default: // Default: List all post types	
				self::_page_show_all_post_types();
		}
	}	
}
/*EOF*/