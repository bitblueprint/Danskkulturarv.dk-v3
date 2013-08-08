<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
$object = WPChaosClient::get_object();
?>
<div id="main-jwplayer">
<p style="text-align:center;">Loading the player ...</p>
</div>

<script type="text/javascript">
var jwplayerOptions = {
	image: "<?php echo $object->thumbnail ?>",
	file: "",
	skin: "<?php echo get_template_directory_uri() . '/lib/jwplayer/five.xml' ?>",
	width: "100%"
};
</script>