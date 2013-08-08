<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
$object = WPChaosClient::get_object();
function generate_file_label($file) {
	$quality_matches = array();
	if(preg_match('/[\d]+k/i', $file->URL, $quality_matches)) {
		$quality = ' ('. strtoupper($quality_matches[0]) .')';
	} else {
		$quality = '';
	}
	return $file->Token . $quality;
}
?>
<div id="main-jwplayer">
<p style="text-align:center;">Loading the player ...</p>
</div>

<script type="text/javascript">
jwplayer.key="<?php echo get_option('wpdka-jwplayer-api-key') ?>";
$("#main-jwplayer").each(function () {
	jwplayer(this).setup({
		skin: "<?php echo get_template_directory_uri() . '/lib/jwplayer/five.xml' ?>",
		width: "100%",
		aspectratio: "4:3",
		logo: {
			file: "<?php echo get_template_directory_uri() . '/img/dka-logo-jwplayer.png' ?>",
			hide: true,
			link: "<?php echo site_url() ?>",
			margin: 20,
		},
		abouttext: "Om Dansk Kulturarv",
		aboutlink: "<?php echo site_url('om') ?>",
		playlist: [{
			image: "<?php echo $object->thumbnail ?>",
			mediaid: "<?php echo $object->GUID ?>",
			sources: [
			<?php foreach($object->Files as $file):?>
				<?php if($file->FormatType == "Video"):?>
				{
					file: "<?php echo $file->URL ?>",
					label: "<?php echo generate_file_label($file) ?>"
				},
				<?php endif;?>
			<?php endforeach;?>
			],
			title: "<?php echo $object->title ?>",
		}]
	});
})
</script>