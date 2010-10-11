<?php
/*------------------------------------------------------------------------------
Included by the media form element type.  The media form element types can launch
an AJAX resource browser for selecting images, so they need a lot of extra
code: PHP and JavaScript.

------------------------------------------------------------------------------*/
$modal_title = __('Choose Media'); // update so it inclues the field name.
$div_id_modal_content = 'hiddenModalContent';
$click_me_txt = __('Choose Media');

/* make variables to support multiple media fields
s
m
$div_id_modal_content

*/
/*
$msg .= sprintf("<a href='#' onclick=\"tb_show('%s', '#TB_inline?&inlineId=%s','false'); return false;\">%s</a>"
	, $modal_title
	, $div_id_modal_content
	, $click_me_txt
);
*/
//------------------------------------------------------------------------------
?>

<script type="text/javascript">
	
//	jQuery(document).ready(main);

	/* Listeners */
	function main()
	{
		/*jQuery("#s").keyup(get_search_results); */
	}
	
	/*------------------------------------------------------------------------------
	SYNOPSIS:
		Query our external search page
	INPUT: 
		none; reads values from the id="s" text input (the search query)
	OUTPUT:
		triggers the write_results_to_page() function, writes to console for logging.
	------------------------------------------------------------------------------*/
	function get_search_results(post_type)
	{
		var search_query = jQuery("#s").val();
	
		jQuery.get("<?php print CUSTOM_CONTENT_TYPE_MGR_URL; ?>/media-selector.php", { "post_mime_type":post_type,"fieldname":"<?php print $fieldname; ?>" }, write_results_to_page);
	}
	
	/*------------------------------------------------------------------------------
	SYNOPSIS: 
		Write the incoming data to the page. 
	INPUT: 
		data = the html to write to the page
		status = an HTTP code to designate 200 OK or 404 Not Found
		xhr = object
	OUTPUT: 
		Writes HTML data to the "ajax_search_results_go_here" id.
	------------------------------------------------------------------------------*/
	function write_results_to_page(data,status, xhr) 
	{
		if (status == "error") {
			var msg = "Sorry but there was an error: ";
	    	console.error(msg + xhr.status + " " + xhr.statusText);
		}
		else
		{
			jQuery("#ajax_media_selector_results").html(data);
		}
	}
</script>

<div id="hiddenModalContent" style="display: none">
	<form id="filter" action="" method="get">
	
	<p id="media-search" class="search-box">
		<label class="screen-reader-text" for="media-search-input">Search Media:</label>
	
		<input type="text" id="media-search-input" name="s" value="" />
		<input type="submit" value="Search Media" class="button" />
	</p>
	
	<ul class="subsubsub">
		<li><span onclick="javascript:get_search_results('all')">All Types</span> 
		| </li>
		<li><span onclick="javascript:get_search_results('image')" class="current">Images <span class="count">(<span id="image-counter">6</span>)</span></span> 
		| </li>
		<li>
			<span onclick="javascript:get_search_results('video')">Video <span class="count">(<span id="video-counter">1</span>)</span></span>
		</li>
	</ul>

	<div class="tablenav">			
		<div class="alignleft actions">
			<select name="m">
				<?php /* ???? make dynamic  */ ?>
				<option value="0">Show all dates</option>
				<option value="201010">October 2010</option>
			</select>
			<input type="submit" id="post-query-submit" value="Filter &#187;" class="button-secondary" />
		</div>
	
		<br class="clear" />		
	</div>
	</form>
	<div id="ajax_media_selector_results" style="overflow:auto"></div>
</div>

<a href='#' onclick="tb_show('<?php print $modal_title; ?>', '#TB_inline?inlineId=<?php print $div_id_modal_content; ?>','false'); return false;"><?php print $click_me_txt; ?></a>

