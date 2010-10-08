<?php if ( ! defined('WP_CONTENT_DIR')) exit('No direct script access allowed');
/*------------------------------------------------------------------------------
The loader.php is called only when we've checked for any potential conflicts
with function names, class names, or constant names. With so many WP plugins
available and so many potential conflicts out there, I've attempted to 
avoid the resulting headaches as much as possible.
------------------------------------------------------------------------------*/
define('CUSTOM_CONTENT_TYPE_MGR_PATH', dirname(__FILE__));
define('CUSTOM_CONTENT_TYPE_MGR_URL', WP_PLUGIN_URL .'/'. basename(dirname(__FILE__) ) );
//wp_register_script('do-nothing-xyzzy', CUSTOM_CONTENT_TYPE_MGR_URL. '/do-nothing-xyzzy.js');
   // enqueue the script
//wp_enqueue_script('do-nothing-xyzzy');

// If you do this sooner rather than later, it won't show up in the admin...
add_action( 'wp_print_scripts', 'my_deregister_javascript', 100 );

function my_deregister_javascript() {
	wp_deregister_script( 'media-upload' );
}

//wp_deregister_script('do-nothing-xyzzy');
/*
function xyz123()
{
	print_r( func_get_args());
	exit;
}

add_filter('admin_head', 'xyz123');
*/
// Required Files
include_once('includes/CustomPostTypeManager.php');
include_once('includes/FormGenerator.php');
include_once('includes/StandardizedCustomFields.php');
include_once('includes/functions.php');

// Register any custom post-types
add_action( 'init', 'CustomPostTypeManager::register_custom_post_types', 0 );

// create custom plugin settings menu
add_action('admin_menu', 'CustomPostTypeManager::create_admin_menu');
add_filter('plugin_action_links', 'CustomPostTypeManager::add_plugin_settings_link', 10, 2 );


// Standardize Fields
add_action( 'do_meta_boxes', 'StandardizedCustomFields::remove_default_custom_fields', 10, 3 );
add_action( 'admin_menu', 'StandardizedCustomFields::create_meta_box' );
add_action( 'save_post', 'StandardizedCustomFields::save_custom_fields', 1, 2 );



/*------------------------------------------------------------------------------
Array
(
    [type] => From Computer
    [type_url] => From URL
    [gallery] => Gallery
    [library] => Media Library
)
------------------------------------------------------------------------------*/



function simplify_media_tabs($tabs) 
{
//	print_r($tabs); exit;
	unset($tabs['type_url']);
	unset($tabs['gallery']);
	return $tabs;
}
add_filter('media_upload_tabs', 'simplify_media_tabs');


/*
The get_media_item function inside the includes/media.php file 

Removing these filters doesn't do it:

remove_filter('async_upload_image', 'get_media_item', 10, 2);
remove_filter('async_upload_audio', 'get_media_item', 10, 2);
remove_filter('async_upload_video', 'get_media_item', 10, 2);
remove_filter('async_upload_file', 'get_media_item', 10, 2);

#0  get_media_item(140, Array ([errors] => )) called at [/Users/everett/Sites/wordpress3/html/wp-admin/includes/media.php:1140]
#1  get_media_items(, Array ()) called at [/Users/everett/Sites/wordpress3/html/wp-admin/includes/media.php:1996]
#2  media_upload_library_form(Array ()) called at [(null):0]
#3  call_user_func_array(media_upload_library_form, Array ([0] => Array ())) called at [/Users/everett/Sites/wordpress3/html/wp-admin/includes/media.php:338]
#4  wp_iframe(media_upload_library_form, Array ()) called at [/Users/everett/Sites/wordpress3/html/wp-admin/includes/media.php:801]
#5  media_upload_library() called at [(null):0]
#6  call_user_func_array(media_upload_library, Array ([0] => )) called at [/Users/everett/Sites/wordpress3/html/wp-includes/plugin.php:425]
#7  do_action(media_upload_library) called at [/Users/everett/Sites/wordpress3/html/wp-admin/media-upload.php:120]



So are targets are:
wp-admin/includes/media.php: media_upload_library_form() function -- we want to change labeling on "Save All Changes"
get_media_item() -- Use as featured image and other JS stuff 
*/

// This one controls the widget 
//add_filter('admin_post_thumbnail_html', 'xyzzy');
/* $form_fields contains something like this:
Array
(
    [post_title] => Array
        (
            [label] => Title
            [value] => IMG_5026
            [required] => 1
        )

    [image_alt] => Array
        (
            [value] => 
            [label] => Alternate Text
            [helps] => Alt text for the image, e.g. &#8220;The Mona Lisa&#8221;
        )

    [post_excerpt] => Array
        (
            [label] => Caption
            [value] => 
        )

    [post_content] => Array
        (
            [label] => Description
            [value] => 
            [input] => textarea
        )

    [url] => Array
        (
            [label] => Link URL
            [input] => html
            [html] => 
	<input type='text' class='text urlfield' name='attachments[140][url]' value='http://localhost:8888/wp-content/uploads/2010/09/IMG_5026.jpg' /><br />
	<button type='button' class='button urlnone' title=''>None</button>
	<button type='button' class='button urlfile' title='http://localhost:8888/wp-content/uploads/2010/09/IMG_5026.jpg'>File URL</button>
	<button type='button' class='button urlpost' title='http://localhost:8888/?attachment_id=140'>Post URL</button>

            [helps] => Enter a link URL or click above for presets.
        )

    [menu_order] => Array
        (
            [label] => Order
            [value] => 0
        )

    [align] => Array
        (
            [label] => Alignment
            [input] => html
            [html] => <input type='radio' name='attachments[140][align]' id='image-align-none-140' value='none' checked='checked' /><label for='image-align-none-140' class='align image-align-none-label'>None</label>
<input type='radio' name='attachments[140][align]' id='image-align-left-140' value='left' /><label for='image-align-left-140' class='align image-align-left-label'>Left</label>
<input type='radio' name='attachments[140][align]' id='image-align-center-140' value='center' /><label for='image-align-center-140' class='align image-align-center-label'>Center</label>
<input type='radio' name='attachments[140][align]' id='image-align-right-140' value='right' /><label for='image-align-right-140' class='align image-align-right-label'>Right</label>
        )

    [image-size] => Array
        (
            [label] => Size
            [input] => html
            [html] => <div class='image-size-item'><input type='radio' name='attachments[140][image-size]' id='image-size-thumbnail-140' value='thumbnail' checked='checked' /><label for='image-size-thumbnail-140'>Thumbnail</label> <label for='image-size-thumbnail-140' class='help'>(150&nbsp;&times;&nbsp;150)</label></div>
<div class='image-size-item'><input type='radio' name='attachments[140][image-size]' id='image-size-medium-140' value='medium' /><label for='image-size-medium-140'>Medium</label> <label for='image-size-medium-140' class='help'>(300&nbsp;&times;&nbsp;225)</label></div>
<div class='image-size-item'><input type='radio'  disabled='disabled'name='attachments[140][image-size]' id='image-size-large-140' value='large' /><label for='image-size-large-140'>Large</label></div>
<div class='image-size-item'><input type='radio' name='attachments[140][image-size]' id='image-size-full-140' value='full' /><label for='image-size-full-140'>Full Size</label> <label for='image-size-full-140' class='help'>(1024&nbsp;&times;&nbsp;768)</label></div>
        )

)
*/
function simplify_image_form($form_fields, $post)
{
//	print_r($form_fields); exit;
	unset($form_fields['url']);
	unset($form_fields['align']);
	unset($form_fields['image-size']);
	return $form_fields;
}
add_filter('attachment_fields_to_edit', 'simplify_image_form', 11, 2);


/*EOF*/