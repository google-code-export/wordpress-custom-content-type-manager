<?php
class MediaSelector
{

	
	public function __construct()
	{
	
	}

	
	/*------------------------------------------------------------------------------
	
	------------------------------------------------------------------------------*/
	private function _get_avaliable_mime_types($filter)
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
	public function get_post_mime_type_options($filter='all')
	{
		global $wpdb;
		
		$avail_post_mime_types = $this->_get_avaliable_mime_types($filter);
	
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
}
/*EOF*/