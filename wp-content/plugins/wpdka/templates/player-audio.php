<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<audio controls="controls" preload="true" title="Lydspor til <?php echo WPChaosClient::get_object()->title; ?>" style="width:100%;height:100%;" width="100%" height="100%">
<?php 
//Loop through each file and skip those whose format is not audio ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) :
	if($file->FormatType != 'Audio') continue;
	$ext = substr($file->URL, strrpos($file->URL, ".")+1);
?>
	<source src="<?php echo $file->URL; ?>" type="audio/<?php echo $ext; ?>" />
<?php endforeach; ?>
</audio>
