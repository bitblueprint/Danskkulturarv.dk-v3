<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<video controls="controls" preload="true">
<?php 
/*poster="<?php echo WPChaosClient::get_object()->thumbnail; ?>"*/
//Loop through each file and skip those whose format is not video ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) : if($file->FormatType != 'Video') continue; ?>
<?php
$extdot = strrpos($file->URL, ".");
$ext = substr($file->URL, $extdot+1);
 ?>
<source src="<?php echo $file->URL; ?>" type="video/<?php echo $ext; ?>" />
<?php endforeach; ?>
</video>