/*
Matches up to media_element.php div:
<div id="ajax_media_selector_results_<?php print $fieldname; ?>" style="overflow:auto"></div>
*/
var target_id = 'ajax_media_selector_results_';
/* Listeners */
function main()
{
	/*jQuery("#s").keyup(get_search_results); */
}

/*------------------------------------------------------------------------------
SYNOPSIS:
	Query our external search page
INPUT: 
	post_type (str) e.g. post, page, or any custom post type
	fieldname (str)
	ajax_controller_url (str) where we get our extra HTML
	
	also reads values from the id="s" text input (the search query)
OUTPUT:
	triggers the write_results_to_page() function, writes to console for logging.
------------------------------------------------------------------------------*/
function get_search_results(post_type, fieldname, ajax_controller_url)
{
	target_id = target_id + fieldname;
	var search_term = jQuery("#media_search_term").val();
	jQuery.get( ajax_controller_url, { "post_mime_type":post_type,"fieldname":fieldname, "s":search_term }, write_results_to_page);
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
		jQuery("#"+target_id).html(data);
	}
}