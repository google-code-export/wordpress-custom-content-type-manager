<?php
/*------------------------------------------------------------------------------
This is the motor under the hood for the post-selector.  
I'm kinda drawing a blank on what documentation to write here... but that 1989 film
"Akira" is really one of the best films I've ever seen.  You should check it out.
------------------------------------------------------------------------------*/
class PostSelector
{

	// Incoming URL parameters
	public $post_type;
	public $post_mime_type;
	public $fieldname;
	public $s; // search term
	public $page;
	public $m;
	public $mode; // if set, then we cough up AJAX results, otherwise, we cough up a whole page


	private $Pagination; // Pagination object. See Pagination.php
	private $taxonomies = array(); // taxonomies assigned to this post_type
	private $results_per_page = 6;

	private $cnt; // number of search results
	private $SQL; // store the query here for debugging.
		
	// Unfortunately, this isn't EXACTLy what's in the db... but the wp_posts.post_mime_type *begins* with these
	private $valid_post_mime_types = array( 'image','video','audio','all');
	
	
	private $media_type_option_tpl = '<li><span onclick="javascript:search_media(\'[+mime_type+]\');">[+mime_type_label+] <span class="mime_type_count">([+count+])</span> &nbsp;</li>';
	
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	public function __construct()
	{
		
		$this->_read_inputs(); // sets values from URL	

		$this->Pagination = new Pagination();
		$offset = $this->Pagination->page_to_offset( $this->page,$this->results_per_page );
		$this->Pagination->set_offset($offset);
		$this->Pagination->set_results_per_page( $this->results_per_page );


		$output = '';
		if ( $this->mode )
		{
			$output = $this->return_Ajax();
		}
		else
		{
			$output = $this->return_iFrame();
		}
	//	print $this->SQL; exit;

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
	This formats all post-types (no image previews, just title and stuff).
	$hash['post_id'] = '';
	$hash['preview_html'] = '';
	$hash['select_label'] = '';
	$hash['thumbnail_html'] = '';
	$hash['post_title'] = '';
	$hash['show_hide_label'] = '';
	$hash['detail_image'] = '';
	$hash['details'] = '';
	$hash['original_post_url'] = '';
	$hash['view_original_label'] = '';
	------------------------------------------------------------------------------*/
	private function _format_results($results)
	{
		$output = '';
		
		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/single_item.tpl');

		foreach ( $results as $r )
		{

			if ( $r['post_type'] == 'attachment' )
			{
				$r = $this->_format_attachment_result($r);
			}
			else
			{
				$r = $this->_format_result($r);			
			}

			$output .= $this->parse($tpl, $r);
		}
		
		return $output;
	}


	/*------------------------------------------------------------------------------
	Formats attachment posts
	------------------------------------------------------------------------------*/
	private function _format_attachment_result($r)
	{
		if (preg_match('/^image/', $r['post_mime_type']) )
		{
			list($src, $w, $h) = wp_get_attachment_image_src( $r['post_id'], 'thumbnail');
			$r['thumbnail_html'] = sprintf('<img class="mini-thumbnail" src="%s" height="30" width="30" alt="" />'
				, $src);
			$r['detail_image'] = wp_get_attachment_image( $r['post_id'], 'medium' );
			$preview_html = wp_get_attachment_image( $r['post_id'], 'thumbnail' );
			list($src, $full_w, $full_h) = wp_get_attachment_image_src( $r['post_id'], 'full');
			$r['dimensions'] = '<strong>'.__('Dimensions').':</strong> <span id="media-dims-'. $r['post_id'] .'">'.$full_w.'&nbsp;&times;&nbsp;'.$full_h.'</span><br/>';
			}
		// It's not an image
		else
		{
			list($src, $w, $h) = wp_get_attachment_image_src( $r['post_id'], 'thumbnail', TRUE);
			$r['thumbnail_html'] = sprintf('<img class="mini-thumbnail" src="%s" height="30" width="30" alt="" />'
				, $src);
			$r['detail_image'] = wp_get_attachment_image( $r['post_id'], 'medium', TRUE );
			$preview_html = wp_get_attachment_image($r['post_id'], 'thumbnail', TRUE );
			$r['dimensions'] = '';
		}

		# Passed via JS, so we gotta prep it.
		$preview_html .= '<span class="formgenerator_label">'.$r['post_title'].'</span><br/>';
		$preview_html = preg_replace('/"/', "'", $preview_html); 
		$preview_html = preg_replace("/'/", "\'", $preview_html);
		$r['preview_html'] = $preview_html;
		
		preg_match('#.*/(.*)$#', $r['original_post_url'], $matches);
		
		
		
		$r['filename'] = $matches[1];
				
		$r['select_label'] 		= __('Select');
		$r['show_hide_label'] 	= __('Show / Hide');			

		$r['view_original_label'] = __('View Original');


		$r['details'] = '<strong>'.__('Filename').':</strong> '.$r['filename'].'<br/>
					<strong>'.__('File Type').':</strong> '.$r['post_mime_type'].'<br/>
					<strong>'.__('Date Uploaded').':</strong> '.$r['post_modified'].'<br/>'
					. $r['dimensions'];

		return $r;

	}
	
	/*------------------------------------------------------------------------------
	Formats individual posts (all but attachments)
	------------------------------------------------------------------------------*/
	private function _format_result($r)
	{
		list($src, $w, $h) = wp_get_attachment_image_src( $r['post_id'], 'thumbnail', TRUE);
		$r['thumbnail_html'] = sprintf('<img class="mini-thumbnail" src="%s" height="30" width="30" alt="" />'
				, $src);
		$r['detail_image'] = wp_get_attachment_image( $r['post_id'], 'medium' );

		# Passed via JS, so we gotta prep it.
		$preview_html = '<span class="formgenerator_label">'.$r['post_title'].'</span><br/>';
		$preview_html = preg_replace('/"/', "'", $preview_html); 
		$preview_html = preg_replace("/'/", "\'", $preview_html);
		$r['preview_html'] = $preview_html;
				
		$r['select_label'] 		= __('Select');
		$r['show_hide_label'] 	= __('Show / Hide');			

		$r['mime_type_label'] 	= __('File Type');
		$r['view_original_label'] = __('View Original');
		$r['upload_date_label']	= __('Date Uploaded');

		$r['details'] = '<strong>'.__('Title').':</strong> '.$r['post_title'].'<br/>
					<strong>'.__('Excerpt').':</strong> '.$r['post_excerpt'].'<br/>
					<strong>'.__('Modified').':</strong> '.$r['post_modified'];
		return $r;
	
	}
	
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _format_yearmonth($results)
	{
		$output = '<option value="0">'.__('Choose Date').'</option>';
		foreach ( $results as $r )
		{
			$output .= '<option value="'.$r['yyyymm'].'">'.__($r['month']).' '.$r['year'].'</option>';	
		}
		return $output;
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
	Read inputs from the $_GET array.
	------------------------------------------------------------------------------*/
	private function _read_inputs()
	{
		// Which post types will we be searching for?
		if ( isset($_GET['post_type']) && !empty($_GET['post_type']) )
		{
			if ( preg_match('/[^a-z_\-]/i', $_GET['post_type']) )
			{
				wp_die(__('Invalid post_type.'));   // Only a-z, _, - is allowed.
			}
			$this->post_type = $_GET['post_type'];
		}
	
		if ( isset($_GET['post_mime_type']) && !empty($_GET['post_mime_type']) )
		{
			if ( !in_array( $_GET['post_mime_type'],  $this->valid_post_mime_types ) )
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
			$this->s = $_GET['s'];
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
	Son of a bitch... you can't use query_posts here because the global $wp_the_query
	isn't defined yet.  get_posts() works, however.  Jeezus H. Christ. Crufty ill-defined
	API functions.
	http://shibashake.com/wordpress-theme/wordpress-query_posts-and-get_posts
	
	This is the main SQL query constructor. Home rolled...
	It's meant to be called by the various querying functions:
		query_search()
		query_count()
		query_distinct_dates()
		
	Options: 
		$mime_type
		$searchterm
		$limit
		$offset
		
	------------------------------------------------------------------------------*/
	private function _sql($select, $limit=0,$use_offset=FALSE)
	{
		global $wpdb; 
		
		$query = "SELECT "
			. $select
			. " FROM {$wpdb->posts} 
			WHERE 
				1"
				. $this->_sql_filter_post_type()
				. $this->_sql_filter_searchterm()
				. $this->_sql_filter_post_mime_type()
				. $this->_sql_filter_post_status()
			. $this->_sql_filter_limit($limit)  
			. $this->_sql_filter_offset($use_offset);
			
		$results = $wpdb->get_results( $query, ARRAY_A );
		
		$this->SQL = $query;
//		print $query; exit;
		return $results;
	}



	/*------------------------------------------------------------------------------
	SELECT * from wp_posts where DATE_FORMAT(post_modified, '%Y%m') = '201009';
	SELECT DISTINCT DATE_FORMAT(post_modified, '%Y%m') FROM wp_posts;
	------------------------------------------------------------------------------*/
	private function _sql_filter_yearmonth()
	{
		global $wpdb;
		if ( $this->m )
		{
			$query = " AND DATE_FORMAT({$wpdb->posts}.post_modified, '%Y%m') = %s";
			return $wpdb->prepare( $query, $this->post_mime_type.'%' );
		}
		else
		{
			return '';
		}
	}


	/*------------------------------------------------------------------------------
	$limit should be passed in as $this->results_per_page; (like when you're selecting
	rows) or as zero (like when you're counting rows).
	------------------------------------------------------------------------------*/
	private function _sql_filter_limit($limit=0)
	{
		global $wpdb;
		if ( $limit )
		{
			$query = ' LIMIT ' . $limit;
			return $query;
		}
		else
		{
			return '';
		}
	}


	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _sql_filter_offset($use_offset)
	{
		global $wpdb;
		if ( $use_offset && $this->page )
		{
			$offset = ($this->page - 1) * $this->results_per_page;
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
	private function _sql_filter_post_mime_type()
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
	Post status
	------------------------------------------------------------------------------*/
	private function _sql_filter_post_status()
	{
		global $wpdb;
		return " AND {$wpdb->posts}.post_status IN ('publish','inherit')";
	}
	
	
	/*------------------------------------------------------------------------------
	Filters based on post_type
	------------------------------------------------------------------------------*/
	private function _sql_filter_post_type()
	{
		global $wpdb;
		if ( !empty($this->post_type) )
		{
			$query = " AND {$wpdb->posts}.post_type = %s";
			return $wpdb->prepare( $query, $this->post_type );			
		}
		else
		{
			return '';
		}	
	
	}
	
	
	/*------------------------------------------------------------------------------
	Construct the part of the query for searching by name.
	------------------------------------------------------------------------------*/
	private function _sql_filter_searchterm()
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

	/*------------------------------------------------------------------------------
	SELECT DISTINCT DATE_FORMAT(post_modified, '%Y%m') FROM wp_posts;
	------------------------------------------------------------------------------*/
	private function _sql_select_count()
	{
		return ' COUNT(*) as cnt';
	}
	
	/*------------------------------------------------------------------------------
	Which columns do we normally return?
	------------------------------------------------------------------------------*/
	private function _sql_select_columns()
	{
		global $wpdb;
		
		return " {$wpdb->posts}.ID as 'post_id', 
			{$wpdb->posts}.guid as 'original_post_url',
			{$wpdb->posts}.*";
	}
	
	
	/*------------------------------------------------------------------------------
	SELECT DISTINCT DATE_FORMAT(post_modified, '%Y%m') FROM wp_posts;
	http://dev.mysql.com/doc/refman/5.1/en/date-and-time-functions.html#function_date-format
	------------------------------------------------------------------------------*/
	private function _sql_select_yearmonth()
	{
		global $wpdb;
		return " DISTINCT DATE_FORMAT({$wpdb->posts}.post_modified, '%Y%m') as 'yyyymm',
			YEAR({$wpdb->posts}.post_modified) as 'year',
			DATE_FORMAT({$wpdb->posts}.post_modified, '%M') as 'month'";
	}
	
	
	

	
	
	//! Public Functions
	/*------------------------------------------------------------------------------
	private $media_type_option_tpl = '<li><span onclick="javascript:get_search_results(\'[+mime_type+]\');">[+mime_type_label+] <span class="count">(<span class="mime_type_count">[+count+]</span>)</span></li>
	------------------------------------------------------------------------------*/
	public function get_post_mime_type_options($filter='all')
	{
		if ( empty($this->post_type) || $this->post_type != 'attachment' )
		{
			return '';
		}
		
		global $wpdb;
		
		$avail_post_mime_types = $this->_get_mime_types_for_listing($filter);
/* 		print_r($avail_post_mime_types); exit; */
		// Change complex mime_types (e.g. image/tiff) to simple, e.g. "image"
		foreach ( $avail_post_mime_types as &$mt)
		{
			$mt = preg_replace('#/.*$#', '', $mt);
		}
		$avail_post_mime_types = array_unique($avail_post_mime_types);
/* 		print_r($avail_post_mime_types); exit;		 */
		$output = '';				
		
		// Format the list items for menu...
		foreach ( $avail_post_mime_types as $mt )
		{
			$hash['mime_type'] 			= $mt;
			$hash['mime_type_label']	= __(ucfirst($hash['mime_type']));
			$hash['count'] 				= $this->_count_posts_this_mime_type($mt);
			$output .= $this->parse($this->media_type_option_tpl, $hash);
		}

		return $output;
	}


	/*------------------------------------------------------------------------------
	TO-DO: if empty, then we gotta provide filtering for this
	------------------------------------------------------------------------------*/
	public function get_post_type_options($post_type)
	{
		return '';
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
	
	------------------------------------------------------------------------------*/
	public function get_post($post_id)
	{
	
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
	OUTPUT: integer
	------------------------------------------------------------------------------*/
	public function query_count_results()
	{
		$results = $this->_sql( $this->_sql_select_count(), FALSE, FALSE);
		if ( !empty($results) )
		{
			return $results[0]['cnt'];
		}
		else
		{
			return 0;
		}
	}


	/*------------------------------------------------------------------------------
	Get distinct year-month combos
	------------------------------------------------------------------------------*/
	public function query_distinct_yearmonth()
	{
		$results = $this->_sql( $this->_sql_select_yearmonth(), FALSE, FALSE);
		$output = $this->_format_yearmonth($results);
		return $output;
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
	public function query_results()
	{

		$results = $this->_sql( $this->_sql_select_columns(), $this->results_per_page, TRUE);

		if ( empty($results) )
		{
			return '<p>'. __('Sorry, no results found.').'</p>';
		}

//		print_r($results); exit;
		$output = $this->_format_results($results);

		return $output;		
	}

	
	
	/*------------------------------------------------------------------------------
	Called if the post-selector.php page is called via AJAX, AND also called manually
	via PHP the first time the return_iFrame function runs. Returns options
	for selecting a specific media item
	------------------------------------------------------------------------------*/
	public function return_Ajax()
	{
		$hash = array();
		$hash['content'] = $this->query_results();
		$this->cnt = $this->query_count_results();
		$hash['pagination_links'] = $this->Pagination->paginate($this->cnt);

		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/items_wrapper.tpl');
		return $this->parse($tpl, $hash);
	}
	
	
	/*------------------------------------------------------------------------------
	Called if the post-selector.php page is loaded in a Thickbox iFrame.
	------------------------------------------------------------------------------*/
	public function return_iFrame()
	{
		$hash = array();

		$hash['jquery_path'] 				= '../../../../../wp-includes/js/jquery/jquery.js';
		$hash['url'] 						= CUSTOM_CONTENT_TYPE_MGR_URL;
		$hash['ajax_controller_url'] 		= CUSTOM_CONTENT_TYPE_MGR_URL . '/post-selector.php';
		$hash['media_selector_stylesheet'] 	= CUSTOM_CONTENT_TYPE_MGR_URL . '/css/media_selector.css';
		$hash['media_selector_css'] 		= file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH . '/css/media_selector.css');
		$hash['fieldname'] 					= $this->fieldname;
		$hash['page']						= $this->page;
		$hash['default_results'] 			= $this->return_Ajax(); // Default results
		$hash['default_mime_type'] 			= $this->post_mime_type;
		$hash['search_label'] 				= __('Search');
		$hash['clear_label'] 				= __('Reset');
		$hash['post_type_list_items']		= $this->get_post_type_options($this->post_type);
		$hash['media_type_list_items'] 		= $this->get_post_mime_type_options($this->post_mime_type);
		$hash['date_options'] 				= $this->query_distinct_yearmonth();
		
		$tpl = file_get_contents( CUSTOM_CONTENT_TYPE_MGR_PATH.'/tpls/main.tpl');

		return $this->parse($tpl, $hash);
	}
	

}
/*EOF*/