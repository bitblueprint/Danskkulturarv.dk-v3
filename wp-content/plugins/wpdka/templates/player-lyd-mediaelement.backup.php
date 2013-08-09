<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<audio controls="controls" preload="true" autoplay="true" title="Lydspor til <?php echo WPChaosClient::get_object()->title; ?>" style="width:100%;height:100%;" width="100%" height="100%">
<?php 
//Loop through each file and skip those whose format is not audio ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) :
	if($file->FormatType != 'Audio') continue;
	$ext = substr($file->URL, strrpos($file->URL, ".")+1);
?>
	<source src="<?php echo htmlspecialchars($file->URL); ?>" type="audio/<?php echo $ext; ?>" />
<?php endforeach; ?>
</audio>
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
		abouttext: "<?php printf(__('About %s','wpdka'),get_bloginfo('title')); ?>",
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