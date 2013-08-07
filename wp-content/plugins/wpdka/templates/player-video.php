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
	// We're not interested in anything but a video.
	if($file->FormatType != 'Video') continue;
	// What is the file-type extension?
	$ext = substr($file->URL, strrpos($file->URL, ".")+1);
	// Was a streamer derived?
	$streamer = $file->Streamer ?: null;

	if($streamer && strpos($streamer, $ext.':') === false && $ext == 'flv') {
		//$file->URL = $streamer .$ext. ':' . substr($file->URL, strlen($file->Streamer));
		$file->URL = $ext. ':' . substr($file->URL, strlen($file->Streamer));
	}

	// If rtmp streaming is used, this is the mime type
	if($file->Token == "RTMP Streaming") {
		$ext = "rtmp";
	}
	$streamer = $streamer ? ' data-streamer="'.$streamer.'"' : null;
?>
	<source src="<?php echo htmlspecialchars($file->URL); ?>" type="video/<?php echo $ext; ?>"<?php echo $streamer; ?> />
<?php endforeach; ?>
</video>