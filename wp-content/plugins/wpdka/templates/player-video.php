<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
?>
<video controls="controls" autoplay="true" preload="true" title="Video til <?php echo WPChaosClient::get_object()->title; ?>" style="width:100%;height:100%;" width="100%" height="100%" id="player">
<?php 
/*poster="<?php echo WPChaosClient::get_object()->thumbnail; ?>"*/
//Loop through each file and skip those whose format is not video ?>
<?php foreach(WPChaosClient::get_object()->Files as $file) :
	$streamer = $file->Streamer ? ' data-streamer="'.$file->Streamer.'"' : null;
	if($file->FormatType != 'Video') continue;
	if($streamer) {
		//$file->URL = substr($file->URL, strlen($file->Streamer));
	}

	//if rtmp streaming is used, this is the mime type
	if($file->Token == "RTMP Streaming") {
		$ext = "rtmp";
	} else {
		$ext = substr($file->URL, strrpos($file->URL, ".")+1);
	}
?>
	<source src="<?php echo htmlspecialchars($file->URL); ?>" type="video/<?php echo $ext; ?>"<?php echo $streamer; ?> />
<?php endforeach; ?>
</video>