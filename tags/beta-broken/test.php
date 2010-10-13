<?php
// To tie into WP, we come in through the backdoor, by including the config.
require_once( realpath('../../../').'/wp-config.php' );

?>
<script type="text/javascript">	
	function send_back_to_wp(x)
	{
		jQuery('#dicky').val(x);
		tb_remove();
		return false;
	}
</script>

<p>I'm here...</p>
<p><a href="" onclick="javascript:send_back_to_wp('1');return false;">1</a></p>
<p><a href="" onclick="javascript:send_back_to_wp('2');return false;">2</a></p>
<a href="http://localhost:8888/wp-content/plugins/wordpress-custom-content-type-manager/other.php" target="_self" class="thickbox">other...</a>