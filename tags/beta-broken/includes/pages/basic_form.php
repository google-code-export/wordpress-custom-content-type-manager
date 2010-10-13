<?php
/*------------------------------------------------------------------------------
Basic form template. Made for use by the CustomPostTypeManager class.
This template expects the following variables:

	$style; 		// can be used to print <style> block above the form.
	$page_header; 	// appears at the top of the page  
	$fields;		// any additional form fields
	$action_name; 	// used by wp_nonce_field
	$nonce_name; 	// used by wp_nonce_field
	$submit;		// text that appears on the primary submit button
	
------------------------------------------------------------------------------*/
?>
<?php print $style; ?>
<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php print $page_header ?></h2>
	
	<?php print $msg; ?>

	<form id="custom_post_type_manager_basic_form" method="post">
	
		<?php print $fields; ?>
	
		<?php wp_nonce_field($action_name, $nonce_name); ?>
	
		<div class="custom_content_type_mgr_form_controls">
			<input type="submit" name="Submit" class="button-primary" value="<?php print $submit; ?>" />
			<a class="button" href="?page=<?php print self::admin_menu_slug;?>"><?php _e('Cancel'); ?></a> 
		</div>
	
	</form>
</div>