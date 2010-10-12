<?php
/*------------------------------------------------------------------------------
Included by the media form element type.  The media form element types can launch
an AJAX resource browser for selecting images, so they need a lot of extra
PHP and JavaScript.

------------------------------------------------------------------------------*/
$modal_title = __('Choose Media'); // update so it includes the field name.
$div_id_modal_content = 'hidden_modal_content_'.$fieldname;
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
		var search_term = jQuery("#media_search_term").val();
		jQuery.get("<?php print CUSTOM_CONTENT_TYPE_MGR_URL; ?>/media-selector.php", { "post_mime_type":post_type,"fieldname":"<?php print $fieldname; ?>", "s":search_term }, write_results_to_page);
		console.log('Search Term:' + search_term); 
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

<div id="<?php print $div_id_modal_content; ?>" style="display: none">
	<form id="filter" action="" method="get">
	
	<p id="media-search-term-box" class="search-box">
		<input type="text" id="media_search_term" name="s" value="" />
		<span class="button" onclick="javascript:get_search_results('all');"><?php _e('Search Media'); ?></span>
	</p>
	
	<ul class="subsubsub">
		<?php print $media_type_list_items; ?>
	</ul>

	<div class="tablenav">			
		<div class="alignleft actions">
			<select name="m">
				<?php print $date_options; ?>
			</select>
		</div>
	
		<br class="clear" />		
	</div>
	</form>
	<div id="ajax_media_selector_results" style="overflow:auto"></div>
</div>

<a href='#' class="button choose_media_button" onclick="tb_show('<?php print $modal_title; ?>', '#TB_inline?inlineId=<?php print $div_id_modal_content; ?>','false'); return false;"><?php print $click_me_txt; ?></a><br/>