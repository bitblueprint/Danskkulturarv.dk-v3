<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */

function generate_file_label($file) {
	$quality_matches = array();
	if(preg_match('/[\d]+k/i', $file->URL, $quality_matches)) {
		$quality = ' ('. strtoupper($quality_matches[0]) .')';
	} else {
		$quality = '';
	}
	return $file->Token . $quality;
}

$object = WPChaosClient::get_object();

$playlist_sources = array();
foreach($object->Files as $file) {
	if($file->FormatType == "Audio") {
		$playlist_sources[] = array(
			"file" => $file->URL,
			"label" => generate_file_label($file)
		);
	}
}

$sharing_link = site_url($_SERVER["REQUEST_URI"]);

$options = array(
	"skin" => get_template_directory_uri() . '/lib/jwplayer/five.xml',
	"width" => "100%",
	"height" => 24,
	"logo" => array(
		"file" => get_template_directory_uri() . '/img/dka-logo-jwplayer.png',
		"hide" => true,
		"link" => site_url(),
		"margin" => 20
	),
	"abouttext" => __("Om Dansk Kulturarv"),
	"aboutlink" => site_url('om'),
	"playlist" => array(array(
		"image" => $object->thumbnail,
		"mediaid" => $object->GUID,
		"sources" => $playlist_sources,
		"title" => $object->title
	)),
	"autostart" => true,
	"ga" => array()
);

WPDKA::print_jwplayer($options);