<?php
/*------------------------------------------------------------------------------
Intended to be accessed by the Custom Content Type Manager when
adding images/media to a custom form field.

stdClass Object
(
    [ID] => 134
    [post_author] => 1
    [post_date] => 2010-10-07 01:46:42
    [post_date_gmt] => 2010-10-07 01:46:42
    [post_content] => 
    [post_title] => 39C 008
    [post_excerpt] => 
    [post_status] => inherit
    [comment_status] => open
    [ping_status] => open
    [post_password] => 
    [post_name] => 39c-008
    [to_ping] => 
    [pinged] => 
    [post_modified] => 2010-10-07 01:46:42
    [post_modified_gmt] => 2010-10-07 01:46:42
    [post_content_filtered] => 
    [post_parent] => 0
    [guid] => http://localhost:8888/wp-content/uploads/2010/10/39C-008.avi
    [menu_order] => 0
    [post_type] => attachment
    [post_mime_type] => video/avi
    [comment_count] => 0
    [filter] => raw
)
------------------------------------------------------------------------------*/
// To tie into WP, we come in through the backdoor, by including the config.
require_once( realpath('../../../').'/wp-config.php' );

if ( !current_user_can('upload_files') )
{
	wp_die(__('You do not have permission to upload files.'));
}

// Used by the get_posts() function, but some of these we need to manipulate via URL params.
$args = array(
	'post_type' => 'attachment',
	'numberposts' => 10,
	'post_mime_type' => 'image',
	'order' => 'ASC',
	'orderby' => 'title',
);
$page = 0;
$m = '';
$fieldname = 'media_field';

// Read supplied get args. Filter them!!!
if ( isset($_GET['post_mime_type']) && !empty($_GET['post_mime_type']) )
{
	if ( preg_match('/[^a-z]/', $_GET['post_mime_type']) )
	{
		exit;  // Only a-z is allowed.
	}
	$args['post_mime_type'] = $_GET['post_mime_type'];
}
if ( isset($_GET['fieldname']) && !empty($_GET['fieldname']) )
{
	if ( preg_match('/[^a-z_\-]/i', $_GET['post_mime_type']) )
	{
		exit;  // Only a-z, _, - is allowed.
	}
	$fieldname = $_GET['fieldname'];
}

if ( isset($_GET['page']))
{
	$page = (int) $_GET['page'];
}
if ( isset($_GET['m']))
{
	$page = (int) $_GET['m'];
}


//$post_mime_type = $_GET['post_mime_type'];
//------------------------------------------------------------------------------
?>
<style>
	<?php print file_get_contents('css/media_selector.css'); ?>
</style>
<script type="text/javascript" src="../../../../wp-includes/js/jquery/jquery.js"></script>
<script type="text/javascript">
	function toggle_image_detail(css_id)
	{
		jQuery('#'+css_id).slideToggle(400);
    	return false;
	}
	
	function update_selection(id, thumbnail_html)
	{
		jQuery('#<?php print $fieldname; ?>').val(id);
		jQuery('#<?php print $fieldname; ?>_media').html(thumbnail_html);		
	}
</script>


<div id="media-items">

<?php
//------------------------------------------------------------------------------
$postslist = get_posts($args);
//print_r($postslist); exit;
foreach ($postslist as $post):
//------------------------------------------------------------------------------
	$id = $post->ID;
	$thumbnail_html = '';
	$medium_html = '';
	// It's an image
	if (preg_match('/^image/', $post->post_mime_type) )
	{
		list($src, $w, $h) = wp_get_attachment_image_src( $post->ID, 'thumbnail');
		$thumbnail_html = sprintf('<img class="mini-thumbnail" src="%s" height="30" width="30" alt="" />'
			, $src);
		$medium_html = wp_get_attachment_image( $post->ID, 'medium' );
		$preview_html = wp_get_attachment_image( $post->ID, 'thumbnail' );
	}
	// It's not an image
	else
	{
		list($src, $w, $h) = wp_get_attachment_image_src( $post->ID, 'thumbnail', TRUE );
		$thumbnail_html = sprintf('<img src="%s" class="attachment-medium" width="30" height="30" alt=""/>', $src);
		$medium_html = wp_get_attachment_image( $post->ID, 'medium', TRUE );
		$preview_html = wp_get_attachment_image( $post->ID, 'thumbnail', TRUE );
	}
	# Passed via JS, so we gotta prep it.
	$preview_html = preg_replace('/"/', "'", $preview_html); 
	$preview_html = preg_replace("/'/", "\'", $preview_html);
	
	preg_match('#.*/(.*)$#', $post->guid, $matches);
	$filename = $matches[1];
?>
<div id="media-item-<?php print $post->ID; ?>">
	<div width="400px">
		<input type="radio" name="<?php print $fieldname;?>" id="media-option-<?php print $post->ID; ?>"> 
		<label for="media-option-<?php print $post->ID; ?>">
			<?php print $thumbnail_html; ?>			
			<span class="title"><?php print $post->post_title; ?></span>
		</label>
		<span class="select_me" onclick="javascript:update_selection('<?php print $post->ID; ?>','<?php print $preview_html; ?>')">Select me</span>
		<span class="toggler" onclick="javascript:toggle_image_detail('media-detail-<?php print $post->ID; ?>');">Show</span>
	</div>
	
	<div id="media-detail-<?php print $post->ID; ?>" class="media_detail">
		<table class="media-detail">
			<thead class="media-item-info" id="media-head-<?php print $post->ID; ?>">
				<tr valign='top'>
					<td class="A1B1" id="thumbnail-head-<?php print $post->ID; ?>">
						<p>
							<?php print $medium_html; ?>
						</p>
					</td>
					<td>
						<p><strong>File name:</strong> <?php print $filename; ?></p>
						<p><strong>File type:</strong> <?php print $post->post_mime_type; ?></p>	
						<p><strong>Upload date:</strong> <?php the_time('F j, Y'); ?> at <?php the_time('g:i a'); ?></p>
						<p><strong>Dimensions:</strong> <span id='media-dims-<?php print $post->ID; ?>'><?php print $h; ?>&nbsp;&times;&nbsp;<?php print $w; ?></span></p>
						<p><a href='http://localhost:8888/?attachment_id=<?php print $post->ID; ?>' target='_blank'>View Original</a></p>
					</td>
				</tr>
			</thead>
			<tbody>
			</tbody>
		</table>
	</div>

<?php
//------------------------------------------------------------------------------
endforeach;
//------------------------------------------------------------------------------
?>
</div>