<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<video controls="controls" preload="true" title="Video til <?php echo WPChaosClient::get_object()->title; ?>" style="width:100%;height:100%;" width="100%" height="100%">
<?php 
/*poster="<?php echo WPChaosClient::get_object()->thumbnail; ?>"*/
//Loop through each file and skip those whose format is not video ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) :
	if($file->FormatType != 'Video') continue;
	$ext = substr($file->URL, strrpos($file->URL, ".")+1);
?>
	<source src="<?php echo htmlspecialchars($file->URL); ?>" type="video/<?php echo $ext; ?>" />
<?php endforeach; ?>
</video>