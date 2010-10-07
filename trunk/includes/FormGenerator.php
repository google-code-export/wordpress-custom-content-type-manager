<?php
/*------------------------------------------------------------------------------
This generates form elements (e.g. text inputs, dropdowns) by passing the 
generate() function a definition data object.  This plugin does not 
generate the entire form! The developer is expected to supply his own <form>
tags, nonces, and submit buttons as required.

USAGE:

$my_def = array(
		array(
			'name'			=> 'my_custom_field', 
			'label'			=> 'My Custom Field',
			'value'			=> 'default value',
			'extra'			=> ' size="10"', // used inside the HTML input element
			'description'	=> 'A bit more information about the field',	
			'type'			=> 'text',
			'sort_param'	=> 1,			// smaller #s appear at the top
		)
);

$html = FormGenerator::generate($my_def);

// ... then, later, in your template, something like:

<form action="#" method="post">
	<?php print $html; ?>
	<?php wp_nonce_field('my_demo_action', 'my_demo_nonce'); ?>
	<input type="submit" name="Submit" value="Submit" />
</form>

------------------------------------------------------------------------------*/
class FormGenerator
{
	const prefix = 'custom_content_';
	public static $sorting_column = 'sort_param';
	public static $element_wrapper_class = 'formgenerator_element_wrapper';
		
	// A div wraps each element, their ids have an integer appended to the prefix, e.g. 
	// <div id="custom_field_number_5"> 
	public static $element_wrapper_id_prefix = 'custom_field_number_';
		
	// You can use these to put in headings or styling
	public static $before_elements = '';
	public static $after_elements = '';

	// simple iterator used whenever we need a serial #, e.g. form element or div count
	public static $i = 0;  
	
	//! Private Functions
	/*------------------------------------------------------------------------------
	The following '_get_xxx_element' functions each generate a single form element
	(with the exception of the dropbox function, which under special circumstances
	will generate additional inputs for each value passed to its 'special' array.
	
	INPUT: $data (array) contains an associative array describing how the element
	should look with keys for name, title, description, and type (see top of this 
	file for an example).
	OUTPUT: HTML text.
	------------------------------------------------------------------------------*/

	/*------------------------------------------------------------------------------
	The checked value is hard-coded to '1' for compatibility with boolean functions
	------------------------------------------------------------------------------*/
	private static function _get_checkbox_element($data)
	{ 
//		print_r($data); 
		if (!isset($data['checked_value']) || empty($data['checked_value']))
		{
			$data['checked_value'] = 1;
		}
		$tpl ='
			<input type="checkbox" name="[+name+]" class="formgenerator_checkbox" id="[+id+]" value="[+checked_value+]" [+is_checked+] [+extra+]/> 
			<label for="[+id+]" class="formgenerator_label formgenerator_checkbox_label" id="formgenerator_label_[+name+]">[+label+]</label>';
		// Special handling to see if the box is checked.
		if ( $data['value'] == $data['checked_value'] )
		{
			$data['is_checked'] = 'checked="checked"';
		}
		else
		{
			$data['is_checked'] = '';
		}
	
		return self::parse($tpl, $data);
	}

	
	/*------------------------------------------------------------------------------
	The dropdown is special: it requires that you supply an array of options in its
	'options' key to specify each possible value in the dropdown.
	Also, extra inputs will be generated if the dropdown function is passed arguments
	in its 'special' array.  This situation only arises when we need to provide a
	form for EDITING dropdown arguments instead of just DISPLAYING them.
	NOTE: for each option in 'options', the value stored equals the value displayed.
	------------------------------------------------------------------------------*/
	private static function _get_dropdown_element($data)
	{
		//print_r($data); 
		// Some error messaging.
		if ( !isset($data['options']) || !is_array($data['options']) )
		{
			return '<p><strong>Custom Content Error:</strong> No options supplied for '.$data['name'].'</p>';
		}
		
		$tpl = '
			<label for="[+name+]" class="formgenerator_label" id="formgenerator_label_[+name+]">[+label+]</label>
				<select name="[+name+]" class="formgenerator_dropdown formgenerator_dropdown_label" id="[+name+]"[+extra+]>
					[+options+]  
				</select>
				[+special+]';
		
		$option_str = '';
		foreach ( $data['options'] as $option )
		{
			if ( empty($option) )
			{
				$option_str .= '<option value="">Pick One</option>';
			}
			else
			{
				$option = htmlspecialchars($option); // Filter the values
				$is_selected = '';
				if ( isset($data['value']) && $data['value'] == $option )
				{
					$is_selected = 'selected="selected"';
				}
				$option_str .= '<option value="'.$option.'" '.$is_selected.'>'.$option.'</option>';
			}
		}
		
		if ( isset($data['special']) && is_array($data['special']) )
		{
			
			$data['special'] = self::_get_special($data);
		}
		
		$data['options'] = $option_str; // overwrite the array with the string.
		
		return self::parse($tpl, $data);
	}
	
	/*------------------------------------------------------------------------------
	Used to create a media upload field.
	For "featured images", WP stores the post ID of the attachment post_type as a 
	normal foreign-key relationship. It stores the post_type.ID in a custom field
	(wp_postmeta) named _thumbnail_id.
	
	For image post-types that you upload using WP's Media menu, WP uses the standard
	post columns MOSTLY: 
		Title 		--> post_title
		Alt Text	--> wp_postmeta table. _wp_attachment_image_alt field for this post.
		Caption		--> post_excerpt
		Description --> post_content
		
	wp_options: _wp_attached_file path from wp-content/uploads to original image, e.g. '2010/09/IMG_0796.jpg'
	There is also serialized data for _wp_attachment_metadata with all kinds of data
	about the image:
Array
(
    [width] => 2736
    [height] => 3648
    [hwstring_small] => height='96' width='72'
    [file] => 2010/09/IMG_0796.jpg
    [sizes] => Array
        (
            [thumbnail] => Array
                (
                    [file] => IMG_0796-150x150.jpg
                    [width] => 150
                    [height] => 150
                )

            [medium] => Array
                (
                    [file] => IMG_0796-225x300.jpg
                    [width] => 225
                    [height] => 300
                )

            [large] => Array
                (
                    [file] => IMG_0796-768x1024.jpg
                    [width] => 768
                    [height] => 1024
                )

            [post-thumbnail] => Array
                (
                    [file] => IMG_0796-940x198.jpg
                    [width] => 940
                    [height] => 198
                )

        )

    [image_meta] => Array
        (
            [aperture] => 3.2
            [credit] => 
            [camera] => Canon IXY DIGITAL 920 IS
            [caption] => 
            [created_timestamp] => 1247696209
            [copyright] => 
            [focal_length] => 6.935
            [iso] => 640
            [shutter_speed] => 0.0166666666667
            [title] => 
        )

)
		
	------------------------------------------------------------------------------*/
	private static function _get_media_element($data)
	{
/*
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_test_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<input type="text" name="[+name+]" class="formgenerator_text" id="[+name+]" value="[+value+]"[+extra+]/>';
		return self::parse($tpl, $data);
	http://localhost:8888/wp-admin/media-upload.php?post_id=92&type=andimage&andfield=1&flash=0&TB_iframe=true&width=640&height=508	
*/	
	global $post;
	$id = $post->ID;
	return '<p class="hide-if-no-js"><a title="'.__('Choose media').'" href="media-upload.php?post_id='.$id.'&type=image&TB_iframe=1" id="set-post-thumbnail" class="thickbox">Set Image?</a></p>';
	}
	
	/*------------------------------------------------------------------------------
	This special function helps us when we need to edit the options of an existing
	dropdown.  It is ONLY called by the _get_dropdown_element() function.
	------------------------------------------------------------------------------*/
	private static function _get_special($data)
	{
		$special_str = '<div id="custom_field_number_'.FormGenerator::$i.'_dropdown">
			<strong>Dropdown Options</strong> 
			<span class="button" onclick="javascript:addDropdownOption(this.parentNode.id,'
				.$data['def_i'].')">Add Option</span>';
		$i = 0;		
		foreach ($data['special'] as $opt )
		{
			
			$special_str .= sprintf('
				<div id="custom_field_number_%d_dropdown_opt%d">
					<input class="" name="custom_fields[%d][options][]" value="%s" type="text"> 
					<span class="button" onclick="javascript:removeDiv(this.parentNode.id)">Remove</span>
				</div>'
				, FormGenerator::$i	// used to correctly name this div as part of the parent dropdown 
				, $i 				// used only for a unique option name
				, $data['def_i']	// identifies the correct place in the $_POST array
				, htmlspecialchars($opt)
			);
			$i++;
		}
		$special_str .= '</div>';

		return $special_str;
	
	}
	
	//------------------------------------------------------------------------------
	private static function _get_text_element($data)
	{
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_test_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<input type="text" name="[+name+]" class="formgenerator_text" id="[+name+]" value="[+value+]"[+extra+]/>';
		return self::parse($tpl, $data);
	}
	
	//------------------------------------------------------------------------------
	private static function _get_textarea_element($data)
	{
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_textarea_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<textarea name="[+name+]" class="formgenerator_textarea" id="[+name+]" [+extra+]>[+value+]</textarea>';
		return self::parse($tpl, $data);	
	}


	//------------------------------------------------------------------------------
	private static function _get_wysiwyg_element($data)
	{
		$tpl = '
			<label for="[+name+]" class="formgenerator_label formgenerator_wsyiwyg_label" id="formgenerator_label_[+name+]">[+label+]</label>
			<textarea name="[+name+]" class="formgenerator_wysiwyg" id="[+name+]" [+extra+]>[+value+]</textarea>
			<script type="text/javascript">
				jQuery( document ).ready( function() {
					jQuery( "[+name+]" ).addClass( "mceEditor" );
					if ( typeof( tinyMCE ) == "object" && typeof( tinyMCE.execCommand ) == "function" ) {
						tinyMCE.execCommand( "mceAddControl", false, "[+name+]" );
					}
				});
			</script>';	
		return self::parse($tpl, $data);
	}
	
	

	//! Public Functions	
	/*------------------------------------------------------------------------------
	This is the workhorse function of this entire class. See top of this file for
	a usage example.
	INPUT: $field_defs_array (mixed) a definition array of arrays
	OUTPUT: HTML text.
	------------------------------------------------------------------------------*/
	public static function generate($field_defs_array)
	{
		if ( empty($field_defs_array) )
		{
			return '';
		}		
	
		usort($field_defs_array, 'FormGenerator::sort_recordset');

		$output = '';
		
		foreach ( $field_defs_array as $def_i => $field_def ) 
		{
			$output_this_field = '';
			if (!isset($field_def['id']))
			{
				$field_def['id'] = $field_def['name'];
			}
			$field_def['i'] = FormGenerator::$i;
			switch ( $field_def['type'] ) 
			{
				case 'checkbox':
					$output_this_field .= self::_get_checkbox_element($field_def);
					break;
				case 'dropdown':
					$output_this_field .= self::_get_dropdown_element($field_def);
					break;
				case 'media':
					$output_this_field .= self::_get_media_element($field_def);
					break;
				case 'textarea':
					$output_this_field .= self::_get_textarea_element($field_def);
					break;
				case 'wysiwyg':
					$output_this_field .= self::_get_wysiwyg_element($field_def);
					break;
				case 'text':
				default: 
					$output_this_field .= self::_get_text_element($field_def);
					break;
			}
			// optionally add description
			if ( isset($field_def['description']) && !empty($field_def['description']) ) 
			{
				$output_this_field .= '
					<span class="formgenerator_description">'.$field_def['description'].'</span>';
			}
			
			// Append this field to the main $output
			$output .= sprintf('
				<div class="%s" id="%s">
					%s
				</div>'
				, self::$element_wrapper_class
				, self::$element_wrapper_id_prefix.FormGenerator::$i
				, $output_this_field
			);
			FormGenerator::$i++;
		}
		// Wrap output
		$output = self::$before_elements . $output . self::$after_elements;
		
		return $output;
	}


	/*------------------------------------------------------------------------------
	SYNOPSIS: a simple parsing function for basic templating.
	INPUT:
		$tpl (str): a string containing [+placeholders+]
		$hash (array): an associative array('key' => 'value');
	OUTPUT
		string; placeholders corresponding to the keys of the hash will be replaced
		with the values and the string will be returned.
	------------------------------------------------------------------------------*/
	public static function parse($tpl, $hash) 
	{
	
	    foreach ($hash as $key => $value) 
	    {
	    	if ( !is_array($value) )
	    	{
	        	$tpl = str_replace('[+'.$key.'+]', $value, $tpl);
	        }
	    }
	    
	    // Remove any unparsed [+placeholders+]
	    $tpl = preg_replace('/\[\+(.*?)\+\]/', '', $tpl);
	    
	    return $tpl;
	}

	/*------------------------------------------------------------------------------
	Callback function used by usort(). Sorts a recordset: an array of hashes, e.g.

		array(
			array('name' => 'Abe', 'sort_param' => '2'),
			array('name' => 'Bob', 'sort_param' => '1'),
		)

	Technique from: http://www.the-art-of-web.com/php/sortarray/
	------------------------------------------------------------------------------*/
	public static function sort_recordset($a, $b)
	{
		return strnatcmp($a[self::$sorting_column], $b[self::$sorting_column]);
	}
	
} // End class
/*EOF*/