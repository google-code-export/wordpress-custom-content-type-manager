<?php
/*------------------------------------------------------------------------------
This is the motor under the hood for the ajax-media-selector.  
I'm kinda drawing a blank on what documentation to write here... but that 1989 film
"Akira" is really one of the best films I've ever seen.  You should check it out.
------------------------------------------------------------------------------*/
class MediaSelector
{

	public $post_mime_type;
	public $fieldname;
	public $s; // search term
	public $page;
	public $m;
	public $mode; // if set, then we cough up AJAX results, otherwise, we cough up a whole page

	private $taxonomies = array(); // taxonomies assigned to 'attachment' post_type
	private $results_per_page = 10;
		
	// Unfortunately, this isn't EXACTLy what's in the db... but the wp_posts.post_mime_type *begins* with these
	private $valid_post_mime_types = array( 'image','video','audio','all');
	
	
	private $media_type_option_tpl = '<li><span onclick="javascript:get_search_results(\'[+mime_type+]\');">[+mime_type_label+] <span class="mime_type_count">([+count+])</span></li>';
	
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	public function __construct()
	{
		print_r( $this->_get_search_results() ); exit;
		$this->_read_inputs(); // sets values.	
	//	print_r( get_defined_constants() ); exit;
		$output = '';
		if ( $this->mode )
		{
			$output = $this->return_Ajax();
		}
		else
		{
			$output = $this->return_iFrame();
		}
		print $output;
	}

	//! Private Functions
	/*------------------------------------------------------------------------------
	Count the # of posts avail. for this particular mime-type. 
	INPUT: simplified, e.g. 'image' (not image/tiff)
	OUTPUT: integer
	------------------------------------------------------------------------------*/
	private function _count_posts_this_mime_type($mime_type)
	{
		global $wpdb; 
		
		$query = "SELECT post_status, COUNT( * ) AS num_posts FROM {$wpdb->posts} 
			WHERE post_type = 'attachment'
			AND post_mime_type LIKE %s  GROUP BY post_status";
		$raw_cnt = $wpdb->get_results( $wpdb->prepare( $query, $mime_type.'%' ), ARRAY_A );
		
		if ( empty($raw_cnt) )
		{
			return 0;
		}
		else
		{
			return (int) $raw_cnt[0]['num_posts'];
		}
	}
	
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _format_search_results($data)
	{
		$hash = array();
		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/media_item.tpl');
	}
	
	/*------------------------------------------------------------------------------
	How many do we want to display? This is either all of them, or we just show the
	one passed to us.
	OUTPUT: HTML
	------------------------------------------------------------------------------*/
	private function _get_mime_types_for_listing($filter='all')
	{
		$avail_post_mime_types = array();
		if ($filter=='all')
		{
			$avail_post_mime_types = get_available_post_mime_types('attachment');
		}
		else
		{
			$avail_post_mime_types = array($filter);
		}
		return $avail_post_mime_types;
	}

	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _get_pagination_links()
	{
		return '<p>Pagination links go here...</p>';
	}

	/*------------------------------------------------------------------------------
	Son of a bitch... you can't use query_posts here because the global $wp_the_query
	isn't defined yet.  get_posts() works, however.  Jeezus H. Christ. Crufty ill-defined
	API functions.
	http://shibashake.com/wordpress-theme/wordpress-query_posts-and-get_posts
	
	Options: 
		$mime_type
		$searchterm
		$limit
		$offset
		
	------------------------------------------------------------------------------*/
	private function _get_search_results()
	{
/*
		$args = array(
			'post_type' => 'attachment',
			'posts_per_page' => 5,
		); 
		$posts = query_posts('post_type=attachment&post_status=publish&orderby=title&order=ASC');
*/
		
		global $wpdb; 
		
		$query = "SELECT 
				{$wpdb->posts}.ID, 
				{$wpdb->posts}.post_title, 
				{$wpdb->posts}.post_content, 
				{$wpdb->posts}.post_mime_type, 
				{$wpdb->posts}.post_modified, 
				{$wpdb->posts}.guid
			FROM {$wpdb->posts} 
			WHERE 
				{$wpdb->posts}.post_type = 'attachment' "
				. $this->_sql_get_searchterm()
				. $this->_sql_get_post_mime_type()
			. "LIMIT " 
			. $this->results_per_page 
			. $this->_sql_get_offset();
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		return $results;
	}
	
	
	/*------------------------------------------------------------------------------
	Read inputs from the $_GET array.
	------------------------------------------------------------------------------*/
	private function _read_inputs()
	{
		if ( isset($_GET['post_mime_type']) && !empty($_GET['post_mime_type']) )
		{
			if ( !in_array( $_GET['post_mime_type'],  $valid_post_mime_types ) )
			{
				wp_die(__('Invalid post_mime_type.')); 
			}
			$this->post_mime_type = $_GET['post_mime_type'];
		}
		else
		{
			$this->post_mime_type = 'all';
		}
		
		// Get fieldname
		if ( isset($_GET['fieldname']) && !empty($_GET['fieldname']) )
		{
			if ( preg_match('/[^a-z_\-]/i', $_GET['fieldname']) )
			{
				wp_die(__('Invalid field_name.'));   // Only a-z, _, - is allowed.
			}
			$this->fieldname = $_GET['fieldname'];
		}
		else
		{
			wp_die(__('Invalid field_name.'));
		}
		
		// Search term
		if ( isset($_GET['s']) && !empty($_GET['s']) )
		{
			$this->search_term = $_GET['s'];
		}
		
		// TO-DO: pagination
		if ( isset($_GET['page']))
		{
			$this->page = (int) $_GET['page'];
		}
		
		// TO-DO: monthly archives
		if ( isset($_GET['m']))
		{
			$this->m = (int) $_GET['m'];
		}
		
		// Determines if this is an AJAX request or an iFrame
		if ( isset($_GET['mode']) )
		{
			$this->mode = TRUE;
		}
	}

	/*------------------------------------------------------------------------------
	SELECT * from wp_posts where DATE_FORMAT(post_modified, '%Y%m') = '201009';
	SELECT DISTINCT DATE_FORMAT(post_modified, '%Y%m') FROM wp_posts;
	------------------------------------------------------------------------------*/
	private function _sql_get_datestuff()
	{
		global $wpdb;
		if ( $this->m )
		{
			$query = " AND {$wpdb->posts}.post_mime_type LIKE %s";
			return $wpdb->prepare( $query, $this->post_mime_type.'%' );
		}
		else
		{
			return '';
		}
	}



	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _sql_get_offset()
	{
		global $wpdb;
		if ( $this->page )
		{
			$offset = $this->page * $this->results_per_page;
			$query = ' OFFSET ' . (int) $offset;
			return $query;
		}
		else
		{
			return '';
		}
	}
	
	/*------------------------------------------------------------------------------
	Construct the part of the query for searching by mime type
	------------------------------------------------------------------------------*/
	private function _sql_get_post_mime_type()
	{
		global $wpdb;
		if ( $this->post_mime_type != 'all' )
		{
			$query = " AND {$wpdb->posts}.post_mime_type LIKE %s";
			return $wpdb->prepare( $query, $this->post_mime_type.'%' );			
		}
		else
		{
			return '';
		}
	}
	
	/*------------------------------------------------------------------------------
	Construct the part of the query for searching by name.
	------------------------------------------------------------------------------*/
	private function _sql_get_searchterm()
	{
		global $wpdb;
		if ( !empty($this->s) )
		{
			$query = " AND ( 
				{$wpdb->posts}.post_title LIKE %s 
				OR {$wpdb->posts}.post_content LIKE %s 
				OR {$wpdb->posts}.post_excerpt LIKE %s 
			)";
			return $wpdb->prepare( $query, '%'.$this->s.'%', '%'.$this->s.'%', '%'.$this->s.'%' );
		}
		else
		{
			return '';
		}
	}	
	
	
	//! Public Functions
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	public function get_date_options()
	{
		$date_options = '<option value="0">Show all dates</option>
				<option value="201010">October 2010</option>';
	}
	
	
	/*------------------------------------------------------------------------------
	private $media_type_option_tpl = '<li><span onclick="javascript:get_search_results(\'[+mime_type+]\');">[+mime_type_label+] <span class="count">(<span class="mime_type_count">[+count+]</span>)</span></li>
	------------------------------------------------------------------------------*/
	public function get_post_mime_type_options($filter='all')
	{
		global $wpdb;
		
		$avail_post_mime_types = $this->_get_mime_types_for_listing($filter);
	
		$hash = array(
			'mime_type' => '',
			'mime_type_label' => '',
			'count' => '',
			'offset' => '',
		);
		
		$media_type_list_items = sprintf($media_type_option_tpl,'all',__('All Types'),$separator);
				
		// Format the list items for menu...
		foreach ( $avail_post_mime_types as $mt )
		{
			// Change complex mime_types (e.g. image/tiff) to simple, e.g. "image"
			$hash['mime_type'] 			= preg_replace('#/.*$#', '', $mt);
			$hash['mime_type_label']	= __(ucfirst($hash['mime_type']));
			$hash['count'] 				= $this->_count_posts_this_mime_type($mt);
			$hash['offset']				= $this->_get_offset();			
		}
	

		return $media_type_list_items;
	}
	
	/*------------------------------------------------------------------------------
	TO-DO.
	Which taxonomies are assigned to 'attachments'? 
	GD'it... every time you need something serious, the architecture lets you down.
	http://old.nabble.com/query_posts-with-custom-taxonomy-and-custom-post-type-td28258047.html
	SELECT wp_terms.name 
	FROM wp_terms 
	JOIN wp_term_taxonomy ON wp_terms.term_id=wp_term_taxonomy.term_id
	JOIN 
	------------------------------------------------------------------------------*/
	public function get_attachment_taxonomies()
	{
		$attachment_taxonomies = array();
		
		$Taxonomies = get_taxonomies(null, 'objects');
		foreach ( $Taxonomies as $name => $obj )
		{
			if ( in_array('attachment', $obj->object_type ) )
			{
				$attachment_taxonomies[] = $name;	
			}
		}
		
		$this->taxonomies = $attachment_taxonomies;
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
	public static function parse($tpl, $hash) 
	{
	
	    foreach ($hash as $key => $value) 
	    {
	    	if ( !is_array($value) )
	    	{
	        	$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	        }
	    }
	    
	    // Remove any unparsed [+placeholders+]
	    $tpl = preg_replace('/\[\+(.*?)\+\]/', '', $tpl);
	    
	    return $tpl;
	}	
	
	
	/*------------------------------------------------------------------------------
	Called if the ajax-media-selector.php page is called via AJAX. Returns options
	for selecting a specific media item
	------------------------------------------------------------------------------*/
	public function return_Ajax()
	{
		$hash = array();
		$hash['content'] = $this->_get_search_results();
		$hash['pagination_links'] = $this->_get_pagination_links();
		
		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/items_wrapper.tpl');
		return $this->parse($tpl, $hash);
	}
	
	
	/*------------------------------------------------------------------------------
	Called if the ajax-media-selector.php page is loaded in a Thickbox iFrame.
	------------------------------------------------------------------------------*/
	public function return_iFrame()
	{

		$hash = array();
		$hash['jquery_path'] = '../../../../../wp-includes/js/jquery/jquery.js';
		$hash['url'] = CUSTOM_CONTENT_TYPE_MGR_URL;
		$hash['ajax_controller_url'] = CUSTOM_CONTENT_TYPE_MGR_URL . '/ajax-media-selector.php';
		$hash['media_selector_stylesheet'] = CUSTOM_CONTENT_TYPE_MGR_URL . '/css/media_selector.css';
		$hash['fieldname'] = $this->fieldname;
		$hash['default_results'] = $this->return_Ajax(); // Default results
		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/media_selector.tpl');
		return $this->parse($tpl, $hash);
	}
	

}
/*EOF*/