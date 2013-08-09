<?php
function fetch_google_plus_share_count($url) {
	$query = http_build_query(array( 'url' => $url ));
	$url = 'https://apis.google.com/u/0/_/+1/sharebutton?' . $query;
	
	$ch = curl_init();
	curl_setopt_array($ch, array(
		CURLOPT_URL => $url,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_HTTPHEADER => array( 'accept-language: en-GB,da;q=0.8,en-US;q=0.6' )
	));
	
	$content = curl_exec($ch);
	curl_close($ch);
	
	if($content === false) {
		throw new \RuntimeException("Couldn't connect to the Google Plus API, it might have changed.");
	}
	
	$count_matches = array();
	if(preg_match('|id="aggregateCount".*?>([^<]+)<|', $content, $count_matches) == 1) {
		return $count_matches[1];
	} else {
		throw new \RuntimeException("Couldn't find the aggregateCount to the Google Plus API, the markup might have changed, check: " . $base_url . $query);
	}
}

echo "google.com shares: " .  fetch_google_plus_share_count("http://www.google.com/") . "\n";
?>