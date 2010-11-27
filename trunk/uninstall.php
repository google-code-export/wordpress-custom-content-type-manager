<?php
/*------------------------------------------------------------------------------
This is run only when this plugin is uninstalled. All cleanup code goes here.
In general, I tried to make this plugin as clean as possible. You can add your
own functions to your theme files to support custom fields, but only a single
option was used in the wp_options table -- that's where I stashed the serialized
array that stored all the settings and such. 
register_uninstall_hook
WP_UNINSTALL_PLUGIN
------------------------------------------------------------------------------*/>

function uninstall_cctm()
{

}

/*EOF*/