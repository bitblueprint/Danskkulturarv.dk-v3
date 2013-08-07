<?php
$service_path = $argv[1];
$service_email = $argv[2];
$service_password = $argv[3];
$object_guid = $argv[4];

const DKA_CROWD_SCHEMA_GUID = 'a37167e0-e13b-4d29-8a41-b0ffbaa1fe5f';

//For CHAOS lib
set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/../wp-content/plugins/wpchaosclient/lib/chaos-client/src/");
require("CaseSensitiveAutoload.php");
spl_autoload_extensions(".php");
spl_autoload_register("CaseSensitiveAutoload");

use \CHAOS\Portal\Client\PortalClient;
$chaos = new PortalClient($service_path, '1d08d667-d6f7-4680-828d-593cac49497e');

$metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkac:DKACrowd xmlns:dkac='http://www.danskkulturarv.dk/DKA-Crowd.xsd'></dkac:DKACrowd>");
$metadataXML->addChild('Views', '0');
$metadataXML->addChild('Shares', '0');
$metadataXML->addChild('Likes', '0');
$metadataXML->addChild('Ratings', '0');
$metadataXML->addChild('AccumulatedRate', '0');
$slug = 'noget-godt-'.rand(1000, 9999);
//$slug = 'konseilspræsident-c-th-zahle-paa-politikens-redaktion';
//$slug = 'nakskov-skibsværft';
$metadataXML->addChild('Slug', $slug);
$metadataXML->addChild('Tags');

$loginReponse = $chaos->EmailPassword()->Login($service_email, $service_password);
if(!$loginReponse->WasSuccess()) {
	throw new RuntimeException($loginReponse->Error()->Message());
} elseif(!$loginReponse->EmailPassword()->WasSuccess()) {
	throw new RuntimeException($loginReponse->EmailPassword()->Error()->Message());
}

$objectReponse = $chaos->Object()->Get($object_guid, null, null, 0, 1, true);
if(!$objectReponse->WasSuccess()) {
	throw new RuntimeException($objectReponse->Error()->Message());
} elseif(!$objectReponse->MCM()->WasSuccess()) {
	throw new RuntimeException($objectReponse->MCM()->Error()->Message());
}
$revisionID = null;
if($objectReponse->MCM()->TotalCount() == 1) {
	$objects = $objectReponse->MCM()->Results();
	$object = $objects[0];
	foreach($object->Metadatas as $metadata) {
		if($metadata->MetadataSchemaGUID == DKA_CROWD_SCHEMA_GUID) {
			$revisionID = $metadata->RevisionID;
		}
	}
} else {
	throw new RuntimeException("None or too many objects found: Check the object GUID.");
}

if($revisionID != null) {
	echo "Object has crowd metadata in revision $revisionID\n";
}

$metadataReponse = $chaos->Metadata()->Set($object_guid, DKA_CROWD_SCHEMA_GUID, 'da', $revisionID, $metadataXML->asXML());
if(!$metadataReponse->WasSuccess()) {
	throw new RuntimeException($metadataReponse->Error()->Message());
} elseif(!$metadataReponse->MCM()->WasSuccess()) {
	throw new RuntimeException($metadataReponse->MCM()->Error()->Message());
}

echo "Waiting 5 secs for Solr to update its index.\n";
sleep(5);

$objectReponse = $chaos->Object()->Get('DKA-Crowd-Slug_string:'.$slug, null, null, 0, 1, true);
if(!$objectReponse->WasSuccess()) {
	throw new RuntimeException($objectReponse->Error()->Message());
} elseif(!$objectReponse->MCM()->WasSuccess()) {
	throw new RuntimeException($objectReponse->MCM()->Error()->Message());
}
if($objectReponse->MCM()->TotalCount() == 1) {
	echo "Great success!\n";
} else {
	throw new RuntimeException("Test failed!");
}