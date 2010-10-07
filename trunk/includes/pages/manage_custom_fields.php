<script language="javascript">

	// Each form element on this page uses this integer to help identify it uniquely
	var element_i = <?php print $element_cnt; ?>;
	
	// Each custom field definition includes several form elements grouped together by this integer
	var def_i = <?php print $def_cnt; ?>;
	
	// This is used to identify individual options for the dropdown input type.
	var option_i = 0;

	/*------------------------------------------------------------------------------
	Adds a set of form elements which together are known as a "definition" and which
	together describe a custom field. Note the use of the "global" def_i integer:
	it is used to mark the place place of this new definition in the $_POST array:
		$_POST['custom_fields'][def_i] = array( ); 
	------------------------------------------------------------------------------*/
	function addFieldDefinition()
	{
		// a group of fields is grouped into a single definition, tracked by def_i 
		def_i++;
		var new_field_def_html = '<?php print $new_field_def_js; ?>';
		jQuery('#new_custom_fields_wrapper').append(new_field_def_html);

	}
	
	/*------------------------------------------------------------------------------
	When a user is dealing with a dropbox custom field class, he must be able to 
	add new options to it.
	
	INPUT:
		dropdownDivId (string) id of the parent div that wraps the entire dropdown
			menu and all its options.
		i (integer) the definition number (def_i) of this particular definition.
		
	OUTPUT: 
		Appends HTML to the div identified by dropdownDivId.
		Increments the option_i variable.
	------------------------------------------------------------------------------*/
	function addDropdownOption(dropdownDivId, i)
	{		
		var newOption = '<div id="'+dropdownDivId+'_opt'+option_i+'">' +
			'<input type="text" class="" name="custom_fields['+i+'][options][]" value=""/> ' +
			'<span class="button" onclick="javascript:removeDiv(this.parentNode.id)">Remove</span>' +
			'</div>'; 
		jQuery('#'+dropdownDivId).append(newOption); 
		option_i++;
	}
	
	/*------------------------------------------------------------------------------
	This function is a listener on all "type" dropdowns. If a "dropdown" type is 
	selected, we need to dynamically alter the form to allow the user to enter
	options for the dropdown. Likewise, if the user selects a different input type,
	e.g. checkbox, this function will remove the additional fields from the form.
	
	INPUT:
		container_id (string) the id of the div containing the definition fields.
		inputType (string) the current value of the "type" dropdown, e.g. 'textarea'.
		i (integer) the definition number (def_i) of this particular definition.
		
	OUTPUT:
		- If 'dropdown' is selected as the inputType, then HTML form controls are 
		appended to the parent div (container_id) and single dropdown option is 
		appended to this new div.
		- If any other input type is selected, the options div is removed (deleted). 
	------------------------------------------------------------------------------*/
	function addRemoveDropdown(container_id,inputType, i)
	{
		if ( inputType == 'dropdown' )
		{
			var dropdownDivId = container_id + '_dropdown';
			var dropdownHtml = '<div id="'+dropdownDivId+'">' + 
				'<strong>Dropdown Options</strong> ' +
				'<span class="button" onclick="javascript:addDropdownOption(this.parentNode.id, '+i+')">Add Option</span>' +
				'</div>';
			jQuery('#'+ container_id).append(dropdownHtml);
			addDropdownOption(dropdownDivId, i);

		}
		else
		{
			jQuery('#'+ container_id + '_dropdown_opt'+option_i).remove();
		}
	}
	
	/*------------------------------------------------------------------------------
	Removes an entire div from the page, identified by its id attribute.
	INPUT: node_id (str) CSS id of the div
	OUTPUT: none. The HTML element id node_id is removed from the page.
	------------------------------------------------------------------------------*/	
	function removeDiv(node_id)
	{	
		jQuery('#'+node_id).remove();
	}
	
</script>


<style>
	.formgenerator_label {
		display:block;
		font-weight:bold;
	}
	.formgenerator_description {
		display:block;
		font-style:italic;
	}
	
	.formgenerator_wrapper {
		margin-top: 10px;
	}
</style>


<div class="wrap">
	<?php screen_icon(); ?>
	<h2><?php print $post_type; ?>: Custom Fields <a href="#" class="button" onClick="javascript:addFieldDefinition();">Add Custom Field</a></h2>
	
	<?php print $msg; ?>

	<form id="manage_custom_fields" action="#" method="post" />
		<?php wp_nonce_field($action_name, $nonce_name); ?>
		<?php print $fields; ?>
		
		<div id="new_custom_fields_wrapper">
		</div>
		

<br/>

		<div class="custom_content_type_mgr_form_controls">
			<input type="submit" name="Submit" class="button-primary" value="Save Changes" />  
			<a class="button" href="?page=<?php print self::admin_menu_slug;?>">Done</a> 
		</div>
	</form>
</div>