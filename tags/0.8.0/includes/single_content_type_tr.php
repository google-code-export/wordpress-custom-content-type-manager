<?php
/*------------------------------------------------------------------------------
This include is a bit of a cluster.  I wanted to demonstrate how to use 
"traditional" PHP files for templates, but they dont' agree with me: this thing
got out of hand I think...
------------------------------------------------------------------------------*/
?>
<tr class='<?php print $class; ?>'>
	<td class="plugin-title"><strong><?php print $post_type; ?></strong></td>
	<td class="desc">
		<p><?php print $description; ?></p>
	</td>
</tr>
<tr class='<?php print $class; ?> second'>
	<td class="plugin-title">
		<div class="row-actions-visible">
<?php 
//------------------------------------------------------------------------------
// We got 4 ways to end this story...
// Active Built-in Post Types
if ($is_active && in_array($post_type, self::$built_in_post_types)):	
//------------------------------------------------------------------------------
?>		
			<span class='deactivate'>
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=7&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="<?php _e('Deactivate this content type', CCTM::txtdomain); ?>"><?php _e('Deactivate',CCTM::txtdomain); ?></a>
			</span>
		</div>
	</td>
	<td class="desc">
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('Manage Custom Fields', CCTM::txtdomain); ?></a> 
		| 
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=8&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('View Sample Template', CCTM::txtdomain); ?> 
	</td>

<?php 
//------------------------------------------------------------------------------
// Inactive Built-In Post Types
elseif (!$is_active && in_array($post_type, self::$built_in_post_types) ): 
//------------------------------------------------------------------------------
?>
			<span class="activate">
				<a href="?page=<?php print self::admin_menu_slug;?>&<?php print self::action_param; ?>=6&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="<?php _e('Activate custom field management', CCTM::txtdomain); ?>" class="edit"><?php _e('Activate', CCTM::txtdomain); ?></a>
			</span>
			<span class="delete"></span>
		</div>
	</td>
	
	<td class="desc">
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('Manage Custom Fields', CCTM::txtdomain); ?></a>
		| 
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=8&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('View Sample Template', CCTM::txtdomain); ?>
	</td>

	
<?php 
//------------------------------------------------------------------------------
// Active Custom Post Types -- include the "deactivate" links
elseif ($is_active): 
//------------------------------------------------------------------------------
?>

			<span class="activate">
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=7&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="<?php _e('Deactivate this content type', CCTM::txtdomain); ?>"><?php _e('Deactivate', CCTM::txtdomain); ?></a>
			</span>
			<span class="delete"></span>
		</div>
	</td>
	
	<td class="desc">
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=2&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title=""><?php _e('Edit', CCTM::txtdomain); ?></a> 
		|
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('Manage Custom Fields', CCTM::txtdomain); ?></a>
		| 
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=8&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('View Sample Template', CCTM::txtdomain); ?>
	</td>

<?php 
//------------------------------------------------------------------------------
// Inactive Custom Post Types
else: 				
//------------------------------------------------------------------------------
?>
			<span class="activate">
				<a href="?page=<?php print self::admin_menu_slug;?>&<?php print self::action_param; ?>=6&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="<?php _e('Activate this content type', CCTM::txtdomain); ?>" class="edit"><?php _e('Activate', CCTM::txtdomain); ?></a> | 
			</span>
			<span class="delete">
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=3&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="<?php _e('Delete this content type', CCTM::txtdomain); ?>" class="delete"><?php _e('Delete', CCTM::txtdomain); ?></a>
			</span>
		</div>
	</td>
	<td class="desc">
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=2&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title=""><?php _e('Edit', CCTM::txtdomain); ?></a>
		|
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('Manage Custom Fields', CCTM::txtdomain); ?></a>
		| 
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=8&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title=""><?php _e('View Sample Template', CCTM::txtdomain); ?>	
	</td>
<?php
//------------------------------------------------------------------------------ 
endif; 
//------------------------------------------------------------------------------
?>
</tr>