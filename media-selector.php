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

$postslist = get_posts('post_type=attachment&numberposts=10&order=ASC&orderby=title');
foreach ($postslist as $post)
{
	$id = $post->ID;
	// keyword (thumbnail, medium, large or full)
	print wp_get_attachment_image( $id, 'thumbnail' ) . '<br/>';
	print $post->post_title . "<br/>";
}

------------------------------------------------------------------------------*/
require_once( realpath('../../../').'/wp-config.php' );

if ( !current_user_can('upload_files') )
{
	wp_die(__('You do not have permission to upload files.'));
}

// Controlled by URL parameters
$default_search_args = array(
	'post_mime_type' => 'image',
	'page' => 0,
	'm' => '', # month
	's'	=> '', # Search term

);

//$post_mime_type = $_GET['post_mime_type'];
//------------------------------------------------------------------------------
?>
<style>
	.mini-thumbnail {
		vertical-align:middle;
	}
	.media_detail {
		display:none;
		margin-left:40px;
	}
	.toggler {
		float: right;
		margin-right: 200px;
	}
</style>
<script type="text/javascript" src="../../../../wp-includes/js/jquery/jquery.js"></script>
<script type="text/javascript">
	jQuery(document).ready(main);

	/* Listeners */
	function main()
	{
	}

	function toggle_image_detail(css_id)
	{
		console.log(css_id);
		jQuery('#'+css_id).slideToggle(400);
    	return false;
	}

</script>


<div id="media-items">

<?php
$args = array(
	'post_type' => 'attachment',
	'numberposts' => 3,
	'post_mime_type' => 'image',
	'order' => 'ASC',
	'orderby' => 'title',
	'field_name' => '',	# gotta use this in the radio buttons

);
// Read supplied get args. Filter them!!!
foreach ($args as $k => $v)
{
	if ( isset($_GET[$k]))
	{
		$args[$k] = $_GET[$k];
	}
}

$postslist = get_posts($args);
//$postslist = get_posts('post_type=attachment&mime_type=image&numberposts=10&order=ASC&orderby=title');
foreach ($postslist as $post):

	$id = $post->ID;
	// keyword (thumbnail, medium, large or full)
	//print $id; exit;
//	$src = wp_get_attachment_image( $id, 'thumbnail' );
	list($src, $thumb_w, $thumb_h) = wp_get_attachment_image_src( $id, 'thumbnail' );
	list($med_src, $med_w, $med_h) = wp_get_attachment_image_src( $id, 'medium' );
	list($full_src, $w, $h) = wp_get_attachment_image_src( $id, 'full' );
	
	preg_match('#.*/(.*)$#', $full_src, $matches);
	$filename = $matches[1];
//	$title = $post->post_title . "<br/>";
?>

<div id="media-item-<?php print $post->ID; ?>">

	
	<div width="400px">
		<input type="radio" name="" id="media-option-<?php print $post->ID; ?>"> 
		<label for="media-option-<?php print $post->ID; ?>">
			<img class="mini-thumbnail" src="<?php print $src; ?>" height="30" width="30" alt='' />
			<span class="title"><?php print $post->post_title; ?></span>
		</label>
		<span class="toggler" onclick="javascript:toggle_image_detail('media-detail-<?php print $post->ID; ?>');">Show</span>
	</div>
	
	<div id="media-detail-<?php print $post->ID; ?>" class="media_detail">
		<table class="media-detail">
			<thead class="media-item-info" id="media-head-<?php print $post->ID; ?>">
				<tr valign='top'>
					<td class="A1B1" id="thumbnail-head-<?php print $post->ID; ?>">
						<p>
						<img class="thumbnail" src="<?php print $med_src; ?>" height="<?php print $med_h;?>" width="<?php print $med_w; ?>" alt=''/></p>
					</td>
					<td>
						<p><strong>File name:</strong> <?php print $filename; ?></p>
						<p><strong>File type:</strong> <?php print $post->post_mime_type; ?></p>	
						<p><strong>Upload date:</strong> <?php the_time('F j, Y'); ?> at <?php the_time('g:i a'); ?></p>
						<p><strong>Dimensions:</strong> <span id='media-dims-<?php print $post->ID; ?>'><?php print $h; ?>&nbsp;&times;&nbsp;<?php print $w; ?></span></p>
						<p><a href='http://localhost:8888/?attachment_id=<?php print $post->ID; ?>' target='_blank'>View</a></p>
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