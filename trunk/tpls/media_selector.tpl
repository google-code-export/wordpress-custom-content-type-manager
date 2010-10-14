<html>
<head>
	<title>Ajax Media Selector</title>
	<!-- This is loaded via a thickbox iFrame from the WP manager when a media field is generated-->
	<script type="text/javascript" src="../../../../wp-includes/js/jquery/jquery.js"></script>

	<link href="[+media_selector_stylesheet+]" rel="stylesheet" type="text/css">
	

</head>
<body onload="javascript:search_media();">

	<script type="text/javascript">	
		function send_back_to_wp( attachment_id, thumbnail_html )
		{
			jQuery('#[+fieldname+]').val(attachment_id);
			jQuery('#[+fieldname+]_media').html(attachment_id);
			tb_remove();
			return false;
		}
		
		/*------------------------------------------------------------------------------
		
		------------------------------------------------------------------------------*/
		function search_media()
		{
			jQuery.get("[+ajax_controller_url+]", { "mode":"query", "s":"search_query","fieldname":"[+fieldname+]" }, write_results_to_page);
			console.log('[+fieldname+]');
		}


		/*------------------------------------------------------------------------------
		SYNOPSIS: 
			Write the incoming data to the page. 
		INPUT: 
			data = the html to write to the page
			status = an HTTP code to designate 200 OK or 404 Not Found
			xhr = object
		OUTPUT: 
			Writes HTML data to the 'ajax_search_results_go_here' id.
		------------------------------------------------------------------------------*/
		function write_results_to_page(data,status, xhr) 
		{
			console.log('in here...'); 
			if (status == "error") {
				var msg = "Sorry but there was an error: ";
		    	console.error(msg + xhr.status + " " + xhr.statusText);
			}
			else
			{
				jQuery('#ajax_search_results_go_here').html(data);
			}
		}		
		
	</script>

<h1>I'm the Media SelectoRRRR</h1>
<div id="[+div_id+]">
	<!-- p id="media-search-term-box" class="search-box">
		<input type="text" id="media_search_term" name="s" value="" />
		<span class="button" onclick="javascript:get_search_results('all','<?php print $fieldname; ?>','<?php print CUSTOM_CONTENT_TYPE_MGR_URL; ?>/media-selector.php');"><?php _e('Search Media'); ?></span>
	</p -->
	
	<ul class="subsubsub">
		[+media_type_list_items+]
	</ul>

	<!-- div class="tablenav">			
		<div class="alignleft actions">
			<select name="m">
				<?php print $date_options; ?>
			</select>
		</div>	
	</div -->

</div>
<span onclick="javascript:search_media();">Testing...</span>
<br class="clear" />
<div id="ajax_search_results_go_here" style="overflow:auto">[+default_results+]</div>

</body>
</html>