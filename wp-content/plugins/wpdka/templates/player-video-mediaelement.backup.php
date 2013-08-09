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
	
	// Check if the file URL contains the (flv|mp4|mp3): part, just after the streamers URL.
	$matches = array();
	$streamer_regexp_escaped = $streamer;
	$streamer_regexp_escaped = str_replace('.', '\.', $streamer_regexp_escaped);
	if(preg_match("~^$streamer_regexp_escaped(((?:flv|mp4|mp3):)?.*?.([\w]+))$~", $file->URL, $matches) > 0) {
		$filename = $matches[1];
		$rtmpPathSeperator = $matches[2];
		$extension = $matches[3];
		if(empty($rtmpPathSeperator)) {
			// No RTMP seperator found, using the extension.
			$file->URL = $streamer . $extension . ':' . $filename;
		}
	}
	
	
	// If rtmp streaming is used, this is the mime type
	if($file->Token == "RTMP Streaming") {
		$mimetype = "video/rtmp";
	} elseif($file->Token == "HLS Streaming") {
		$mimetype = null;
	} else {
		$mimetype = "video/$ext";
	}
	
	if($mimetype) {
		$mimetype = ' type="' . $mimetype . '"';
	}
	/*
	$streamer = $streamer ? ' data-streamer="'.$streamer.'"' : null;
	*/
?>
	<source src="<?php echo htmlspecialchars($file->URL); ?>"<?php echo $mimetype; ?> />
<?php endforeach; ?>
</video>