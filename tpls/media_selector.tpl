<html>
<head>
	<title>Ajax Media Selector</title>
	<!-- This is loaded via a thickbox iFrame from the WP manager when a media field is generated-->
	<script type="text/javascript" src="../../../../wp-includes/js/jquery/jquery.js"></script>

	<link href="[+media_selector_stylesheet+]" rel="stylesheet" type="text/css">
	

	<script type="text/javascript">
	
		/*------------------------------------------------------------------------------
		Clears the search form
		------------------------------------------------------------------------------*/
		function clear_search()
		{
			jQuery("#media_search_term").val(''); 
			search_media("[+default_mime_type+]");
		}
	
		/*------------------------------------------------------------------------------
		Where the magic happens: this sends our selection back to WordPress
		------------------------------------------------------------------------------*/
		function send_back_to_wp( attachment_id, thumbnail_html )
		{
			jQuery('#[+fieldname+]').val(attachment_id);
			jQuery('#[+fieldname+]_media').html(thumbnail_html);
			tb_remove();
			return false;
		}
		
		/*------------------------------------------------------------------------------
		Main AJAX function to kick off the query.
		------------------------------------------------------------------------------*/
		function search_media(mime_type)
		{
			var search_term = jQuery("#media_search_term").val();
			var yyyymm = jQuery("#m").val();
			jQuery.get("[+ajax_controller_url+]", { "mode":"query", "s":search_term,"fieldname":"[+fieldname+]","post_mime_type":mime_type,"m":yyyymm }, write_results_to_page);
			console.log('[+fieldname+]');
		}

		/*------------------------------------------------------------------------------
		Show / Hide 
		------------------------------------------------------------------------------*/
		function toggle_image_detail(css_id)
		{
			jQuery('#'+css_id).slideToggle(400);
	    	return false;
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



</head>
<body>

<div id="[+div_id+]">
	<p id="media-search-term-box" class="search-box">
		<input type="text" id="media_search_term" name="s" value="" />
		<span class="button" onclick="javascript:search_media('[+default_mime_type+]');">[+search_label+]</span>
		<span class="button" onclick="javascript:clear_search();">[+clear_label+]</span>
	</p>
	
	<h2>Narrow Results</h2>
	<ul class="subsubsub">
		[+media_type_list_items+]
	</ul>

	<div class="tablenav">			
		<div class="alignleft actions">
			<select name="m" onchange="javascript:search_media('[+default_mime_type+]');">
				[+date_options+]
			</select>
		</div>	
	</div>

</div>

<br class="clear" />
<!-- style="overflow:auto" -->
<div id="ajax_search_results_go_here">[+default_results+]</div>

</body>
</html>