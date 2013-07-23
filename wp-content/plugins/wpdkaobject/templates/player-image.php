<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<?php //Loop through each file and skip those whose format is not image ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) : if($file->FormatType != 'Image' || $file->FormatID == 10) continue; ?>

<?php var_dump($file); ?>

<strong><?php echo $file->URL; ?></strong>

<?php endforeach; ?>