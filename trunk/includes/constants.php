<?php
/*------------------------------------------------------------------------------
These are defined in here because they have to be referenced by the AJAX
controllers as well as the main plugin. Sorry for the weirdness. 
------------------------------------------------------------------------------*/
define('CUSTOM_CONTENT_TYPE_MGR_PATH', dirname( dirname( __FILE__ ) ) );
define('CUSTOM_CONTENT_TYPE_MGR_URL', WP_PLUGIN_URL .'/'. basename( dirname (dirname( __FILE__ ) ) ) );
/*EOF*/