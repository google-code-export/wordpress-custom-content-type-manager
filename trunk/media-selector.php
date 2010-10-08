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
require_once( realpath('../../../').'/wp-config.php' );

if ( !current_user_can('upload_files') )
{
	wp_die(__('You do not have permission to upload files.'));
}
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" dir="ltr" lang="en-US">
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
	<title>Custom Content Type Manager - Media Selector</title> 
</head>
<body>
<?php
$postslist = get_posts('post_type=attachment&numberposts=10&order=ASC&orderby=title');
foreach ($postslist as $post) : 
	$id = $post->ID;
	// keyword (thumbnail, medium, large or full)
	print wp_get_attachment_image( $id, 'thumb' ) . '<br/>';
	print $post->post_title . "<br/>";
endforeach; 
?>
</body>
</html>