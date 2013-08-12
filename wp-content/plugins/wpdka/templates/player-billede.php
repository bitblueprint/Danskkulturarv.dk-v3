<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<?php //Loop through each file and skip those whose format is not image ?>
<div class="flexslider">
	<ul class="slides">
<?php foreach(WPChaosClient::get_object()->Files as $file) :
	if($file->FormatType != 'Image' || $file->FormatCategory != 'Image Source') continue;
	$title = sprintf(esc_attr__('Image %s for %s'),$file->Filename,WPChaosClient::get_object()->title);
?>
		<li>
			<img src="<?php echo urlencode($file->URL); ?>" title="<?php echo $title; ?>" alt="<?php echo $title; ?>">
		</li>
<?php ;endforeach; ?>
	</ul>
</div>
