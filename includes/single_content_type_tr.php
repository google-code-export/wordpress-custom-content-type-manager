<tr class='<?php print $class; ?>'>
	<td class='plugin-title'><strong><?php print $post_type; ?></strong></td>
	<td class='desc'>
		<p><?php print $description; ?></p>
	</td>
</tr>
<tr class='<?php print $class; ?> second'>
	<td class='plugin-title'>
		<div class="row-actions-visible">
<?php 
//------------------------------------------------------------------------------
// We got 4 ways to end this story...
// Active Built-in Post Types
if ($is_active && in_array($post_type, self::$built_in_post_types)):	
//------------------------------------------------------------------------------
?>		
			<span class='deactivate'>
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=7&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="Deactivate this content type">Deactivate</a>
			</span>
		</div>
	</td>
	<td class='desc'>
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title="">Manage Custom Fields</a>
	</td>

<?php 
//------------------------------------------------------------------------------
// Inactive Built-In Post Types
elseif (!$is_active && in_array($post_type, self::$built_in_post_types) ): 
//------------------------------------------------------------------------------
?>
			<span class='activate'>
				<a href="?page=<?php print self::admin_menu_slug;?>&<?php print self::action_param; ?>=6&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="Activate custom field management" class="edit">Activate</a>
			</span>
			<span class='delete'></span>
		</div>
	</td>
	
	<td class='desc'>
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title="">Manage Custom Fields</a>
	</td>


	
<?php 
//------------------------------------------------------------------------------
// Active Custom Post Types
elseif ($is_active): 
//------------------------------------------------------------------------------
?>

			<span class='activate'>
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=7&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="Deactivate this content type">Deactivate</a>
			</span>
			<span class='delete'></span>
		</div>
	</td>
	
	<td class='desc'>
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=2&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="">Edit</a> |
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title="">Manage Custom Fields</a>
	</td>

<?php 
//------------------------------------------------------------------------------
// Inactive Custom Post Types
else: 				
//------------------------------------------------------------------------------
?>
			<span class='activate'>
				<a href="?page=<?php print self::admin_menu_slug;?>&<?php print self::action_param; ?>=6&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="Activate this content type" class="edit">Activate</a> | 
			</span>
			<span class='delete'>
				<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=3&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="Delete this content type" class="delete">Delete</a>
			</span>
		</div>
	</td>
	<td class='desc'>
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=2&<?php print self::post_type_param; ?>=<?php print $post_type; ?>" title="">Edit</a> |
		<a href="?page=<?php print self::admin_menu_slug; ?>&<?php print self::action_param; ?>=4&<?php print self::post_type_param; ?>=<?php print $post_type;?>" title="">Manage Custom Fields</a>
	</td>
<?php
//------------------------------------------------------------------------------ 
endif; 
//------------------------------------------------------------------------------
?>
</tr>