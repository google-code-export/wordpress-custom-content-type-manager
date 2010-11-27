<div class="wrap">
<style type="text/css">
	.sample_code_textarea { 
		width: 100%; 
		margin: 0; 
		padding: 0; 
		border-width: 0; }
</style>


	<?php screen_icon(); ?>
	<h2>Custom Content Type Manager <a href="?page=<?php print self::admin_menu_slug; ?>" class="button add-new-h2"><?php _e('Back'); ?></a></h2>

	<p>
		<?php print $msg; ?>
	</p>
	<br />

	<textarea cols="80" rows="60" style="sample_code_textarea"><?php print $sample_code; ?></textarea>


		
</div>