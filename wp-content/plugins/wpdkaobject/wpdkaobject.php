<?php
/**
 * @package WP DKA Object
 * @version 1.0
 */
/*
Plugin Name: WordPress DKA Object
Plugin URI: 
Description: Module applying functionality to manipulate CHAOS material specific for DKA. Depends on WordPress Chaos Client.
Author: Joachim Jensen <joachim@opensourceshift.com>
Version: 1.0
Author URI: 
*/

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPDKAObject {

	//List of plugins depending on
	private $plugin_dependencies = array(
		'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client',
	);

	const DKA_SCHEMA_GUID = '00000000-0000-0000-0000-000063c30000';
	const DKA2_SCHEMA_GUID = '5906a41b-feae-48db-bfb7-714b3e105396';
	const DKA_CROWD_SCHEMA_GUID = '';
	const DKA_CROWD_LANGUAGE = 'da';
	const FREETEXT_LANGUAGE = 'da';
	public static $ALL_SCHEMA_GUIDS = array(self::DKA_SCHEMA_GUID, self::DKA2_SCHEMA_GUID);

	/**
	 * Construct
	 */
	public function __construct() {

		//add_action('admin_init',array(&$this,'check_chaosclient'));

		if($this->check_chaosclient()) {
			// Define the free-text search filter.
			$this->define_attribute_filters();

			// Define the free-text search filter.
			$this->define_search_filters();
			
			// Define a filter for object creation.
			$this->define_object_construction_filters();
		}

	}

	const TYPE_VIDEO = 'series';
	const TYPE_AUDIO = 'audio';
	const TYPE_UNKNOWN = 'unknown';

	/**
	 * Determine type of a CHAOS object based
	 * on the included file formats
	 * @param  WPChaosObject $object 
	 * @return string
	 */
	public static function determine_type($object) {
		
		foreach($object->getObject()->Files as $file) {
			if($file->FormatType == 'Video')
				return self::TYPE_VIDEO;
		}
		return self::TYPE_UNKNOWN;
	}

	/**
	 * Define attributes to be used on a WPChaosObject
	 * with XML content
	 * @return void 
	 */
	public function define_attribute_filters() {
		// Registering namespaces.
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka', 'http://www.danskkulturarv.dk/DKA.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka2', 'http://www.danskkulturarv.dk/DKA2.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, $object) {
			return $value . $object->metadata(
				array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
				array('/dka2:DKA/dka2:Title/text()', '/DKA/Title/text()')
			);
		}, 10, 2);
		
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Organization/text()', '/DKA/Organization/text()')
			);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'description', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Description/text()', '/DKA/Description/text()')
			);
		}, 10, 2);
	
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:FirstPublishedDate/text()', '/DKA/FirstPublishedDate/text()')
			);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, $object) {
			return $value = WPDKAObject::determine_type($object);
		}, 10, 2);
	}

	/**
	 * Convert search parameters to SOLR query
	 * @return string 
	 */
	public function define_search_filters() {
		// Free text search.
		add_filter('wpchaos-solr-query', function($query, $query_vars) {
			if($query) {
				$query = array($query);
			} else {
				$query = array();
			}
				
			if(array_key_exists(WPChaosSearch::QUERY_KEY_FREETEXT, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$freetext = $query_vars[WPChaosSearch::QUERY_KEY_FREETEXT];
				$freetext = WPDKAObject::escapeSolrValue($freetext);
				$searches = array();
				foreach(WPDKAObject::$ALL_SCHEMA_GUIDS as $schemaGUID) {
					$searches[] = sprintf("(m%s_%s_all:(%s))", $schemaGUID, WPDKAObject::FREETEXT_LANGUAGE, $freetext);
				}
				$query[] = '(' . implode("+OR+", $searches) . ')';
			}
				
			return implode("+AND+", $query);
		}, 10, 2);
	}
	
	public function define_object_construction_filters() {
		add_filter(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_FILTER, function(\CHAOS\Portal\Client\Data\Object $object) {
			if(!$object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID)) {
				// The object has not been extended with the crowd matadata schema.
				$objectGUID = $object->getObject()->GUID;
				$metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkac:DKACrowd xmlns:dkac='http://www.danskkulturarv.dk/DKA.crowd.xsd' xmlns:xsi='http://www.w3.org/2001/XMLSchema-instance'></dkac:DKACrowd>");
				//$metadataXML->registerXPathNamespace('dkac', 'http://www.danskkulturarv.dk/DKA.crowd.xsd');
				$metadataXML->addChild('Views', '0');
				$metadataXML->addChild('Shares', '0');
				$metadataXML->addChild('Likes', '0');
				$metadataXML->addChild('Ratings', '0');
				$metadataXML->addChild('AccumulatedRate', '0');
				$metadataXML->addChild('Slug', WPDKAObject::generateSlug($object));
				$metadataXML->addChild('Tags');
				
				// TODO: Set this metadata schema, when it's created in the service.
				//var_dump(htmlentities($metadataXML->asXML()));
				/*
				WPChaosClient::instance()->Metadata()->Set(
					$objectGUID,
					WPDKAObject::DKA_CROWD_SCHEMA_GUID,
					WPDKAObject::DKA_CROWD_LANGUAGE,
					null,
					$metadataXML
				);
				*/
			}
			return $object;
		}, 10, 1);
	}
	
	/**
	 * Generate a slug from a chaos object.
	 * @param \CHAOS\Portal\Client\Data\Object $object The object to generate the slug from.
	 * @return string The slug generated - prepended with a nummeric postfix to prevent douplicates.
	 */
	public static function generateSlug(\CHAOS\Portal\Client\Data\Object $object) {
		$title = apply_filters(WPChaosClient::OBJECT_FILTER_PREFIX.'title', "", $object);
		
		$postfix = 0;
		$slug_base = sanitize_title_with_dashes($title);
		
		// Check if this results in dublicates.
		do {
			if($postfix == 0) {
				$slug = $slug_base; // Not needed
			} else {
				$slug = "$slug_base-$postfix";
			}
			$postfix++; // Try the next
		} while(self::getObjectFromSlug($slug) != null); // Until no object is returned.
		
		return $slug;
	}
	
	/**
	 * Gets an object from the CHAOS Service from an alphanummeric, lowercase slug.
	 * @param string $slug The slug to search for.
	 * @throws \RuntimeException If an error occurs in the service.
	 * @return NULL|\CHAOS\Portal\Client\Data\Object The object matching the slug.
	 */
	public static function getObjectFromSlug($slug) {
		// TODO: Use this instead, when DKA-Slug is added to the index.
		// $response = WPChaosClient::instance()->Object()->Get("DKA-Slug:'$slug'");
		
		$response = WPChaosClient::instance()->Object()->GetSearchSchema($slug, self::DKA_CROWD_SCHEMA_GUID, self::DKA_CROWD_LANGUAGE, null, 0, 1);
		if(!$response->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->Error()->Message());
		} elseif (!$response->MCM()->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->MCM()->Error()->Message());
		} else {
			$count = $response->MCM()->TotalCount();
			if($count == 0) {
				return null;
			} elseif ($count > 1) {
				warn("CHAOS returned more than one ($count) object for this slug: ". htmlentities($slug));
			}
			$result = $response->MCM()->Results();
			return new \CHAOS\Portal\Client\Data\Object($result[0]);
		}
	}

	/**
	 * Check if dependent plugins are active
	 * 
	 * @return void 
	 */
	public function check_chaosclient() {
		//$plugin = plugin_basename( __FILE__ );
		$dep = array();
		//if(is_plugin_active($plugin)) {
			foreach($this->plugin_dependencies as $class => $name) {
				if(!in_array($class,get_option('active_plugins'))) {
					$dep[] = $name;
				}
			}
			if(!empty($dep)) {
				//deactivate_plugins(array($plugin));
				add_action( 'admin_notices', function() use (&$dep) { 
					echo '<div class="error"><p><strong>WordPress DKA Object</strong> needs <strong>'.implode('</strong>, </strong>',$dep).'</strong> to be activated.</p></div>';
				},10);
				return false;
			}
		//}
		return true;
	}

	/**
	 * Escape characters to be used in SOLR
	 * @param  string $string 
	 * @return string         
	 */
	public static function escapeSolrValue($string)
	{
		$match = array('\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' ');
		$replace = array('\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
		$string = str_replace($match, $replace, $string);
	
		return $string;
	}

}
//Instantiate
new WPDKAObject();

//eol