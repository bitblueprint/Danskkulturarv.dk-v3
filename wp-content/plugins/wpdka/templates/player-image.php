<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<?php //Loop through each file and skip those whose format is not image ?>
<div class="flexslider">
	<ul class="slides">
<?php foreach(WPChaosClient::get_object()->Files as $file) : if($file->FormatType != 'Image' || $file->FormatID == 10) continue; ?>
		<li>
			<img src="<?php echo $file->URL; ?>" />
		</li>
<?php endforeach; ?>
	</ul>
</div>
  

