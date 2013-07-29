<?php
/**
 * @package WP DKA
 * @version 1.0
 */

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPDKAObject {

	const OBJECT_TYPE_ID = 36;
	const DKA_SCHEMA_GUID = '00000000-0000-0000-0000-000063c30000';
	const DKA2_SCHEMA_GUID = '5906a41b-feae-48db-bfb7-714b3e105396';
	const DKA_CROWD_SCHEMA_GUID = 'a37167e0-e13b-4d29-8a41-b0ffbaa1fe5f';
	const DKA_CROWD_LANGUAGE = 'da';
	const FREETEXT_LANGUAGE = 'da';
	
	const DKA_CROWD_SLUG_SOLR_FIELD = 'DKA-Crowd-Slug_string';
	
	public static $ALL_SCHEMA_GUIDS = array(self::DKA_SCHEMA_GUID, self::DKA2_SCHEMA_GUID);

	/**
	 * Construct
	 */
	public function __construct() {

		// add_action('admin_init',array(&$this,'check_chaosclient'));
	
		// Define the free-text search filter.
		$this->define_attribute_filters();
		
		// Define a filter for object creation.
		$this->define_object_construction_filters();
		
		$this->define_single_object_page();

		add_filter('widgets_init',array(&$this,'register_widgets'));
	}

	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'audio';
	const TYPE_IMAGE = 'image';
	const TYPE_IMAGE_AUDIO = 'image-audio';
	const TYPE_UNKNOWN = 'unknown';

	public static $format_types = array(
		WPDKAObject::TYPE_AUDIO => array(
			'class' => 'icon-volume-up',
			'title' => 'Lyd',
			),
		WPDKAObject::TYPE_IMAGE_AUDIO => array(
			'class' => 'icon-picture-sound',
			'title' => 'Billeder og lyd',
		),
		WPDKAObject::TYPE_VIDEO => array(
			'class' => 'icon-film',
			'title' => 'Video',
		),
		// This is not yet supported by the metadata.
		//WPDKAObject::TYPE_UNKNOWN => array(
		//	'class' => 'icon-file-text',
		//	'title' => 'Dokumenter',
		//),
		WPDKAObject::TYPE_IMAGE => array(
			'class' => 'icon-picture',
			'title' => 'Billeder',
		),
	);

	/**
	 * Determine type of a CHAOS object based
	 * on the included file formats
	 * @param  WPChaosObject $object 
	 * @return string
	 */
	public static function determine_type($object) {

		$format_types = array();
		
		foreach($object->Files as $file) {
			//FormatID = 10 is thumbnai format. We do not want that here.
			if($file->FormatID != 10) {
				$format_types[$file->FormatType] = 1;
			}
			
		}

		//Video format
		if(isset($format_types['Video']))
			return self::TYPE_VIDEO;

		if(isset($format_types['Audio'])) {
			//Image audio format
			if(isset($format_types['Image']))
				return self::TYPE_IMAGE_AUDIO;
			//Audio format
			return self::TYPE_AUDIO;
		}
		
		//Image format
		if(isset($format_types['Image']))
			return self::TYPE_IMAGE;

		//Fallback
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
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dkac', 'http://www.danskkulturarv.dk/DKA.Crowd.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('xhtml', 'http://www.w3.org/1999/xhtml');

		//object->title
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, $object) {
			return $value . $object->metadata(
				array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
				array('/dka2:DKA/dka2:Title/text()', '/DKA/Title/text()')
			);
		}, 10, 2);

		//object->organization
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization_raw', function($value, $object) {
			$organization = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Organization/text()', '/DKA/Organization/text()')
			);
			return $value . $organization;
		}, 10, 2);

		//object->organization
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, $object) {
			$organizations = WPDKASearch::get_organizations();
			$organization = $object->organization_raw;

			if(isset($organizations[$organization]))
				$organization = $organizations[$organization]['title'];

			return $value . $organization;
		}, 10, 2);

		//object->description
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'description', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Description/text()', '/DKA/Description/text()')
			);
		}, 10, 2);

		//object->published
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, $object) {
			$time = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:FirstPublishedDate/text()', '/DKA/FirstPublishedDate/text()')
			);
			//Format date according to WordPress
			$time = date_i18n(get_option('date_format'),strtotime($time));
			return $value . $time;
		}, 10, 2);

		//object->type
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, $object) {
			return $value . WPDKAObject::determine_type($object);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_class', function($value, $object) {
			$type = $object->type;
			return $value . (isset(WPDKAObject::$format_types[$type]) ? WPDKAObject::$format_types[$type]['class'] : $type);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_title', function($value, $object) {
			$type = $object->type;
			return $value . (isset(WPDKAObject::$format_types[$type]) ? WPDKAObject::$format_types[$type]['title'] : $type);
		}, 10, 2);

		//object->thumbnail
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'thumbnail', function($value, $object) {
			foreach($object->Files as $file) {
				// FormatID = 10 is thumbnail format. This is what we want here
				if($file->FormatID == 10) {
					return $value . $file->URL;
				}
			}
			// Fallback
			// TODO: Consider making this fallback type-specific.
			return $value . 'http://placekitten.com/202/145';
		}, 10, 2);

		//object->slug
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'slug', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Slug/text()');
		}, 10, 2);

		//object->url
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'url', function($value, $object) {
			$result = site_url() . '/';
			$slug = $object->slug;
			if($slug) {
				// Redirect to a webpage with the slug instead of the GUID.
				$organizations = WPDKASearch::get_organizations();
				if(array_key_exists($object->organization_raw, $organizations)) {
					$result .= $organizations[$object->organization_raw]['slug'] . '/';
				}
				return $result . $slug . '/' . $value;
			} else {
				return $result . '?guid=' . $object->GUID . $value;
			}
		}, 10, 2);
	}
	
	public function define_single_object_page() {
		// Ensure the DKA Crowd metadata schema is present, and redirect to the slug URL if needed.
		/*
		add_action(WPChaosClient::GET_OBJECT_PAGE_BEFORE_TEMPLATE_ACTION, function(\WPChaosObject $object) {
			if(WP_DEBUG && array_key_exists('reset-crowd-metadata', $_GET)) {
				echo "<h1>Crowd metadata is resetting ...</h1>";
				// TODO: Check that this isn't failing.
				WPDKAObject::reset_crowd_metadata($object);
				exit;
			} else {
				WPDKAObject::ensure_crowd_metadata($object);
			}
			if(isset($_GET['guid'])) {
				$redirection = $object->url;
				wp_redirect($redirection);
				exit;
			}
		});
		*/
		
		// Make sure objects are identified if they are there.
		add_filter(WPChaosClient::GENERATE_SINGLE_OBJECT_SOLR_QUERY, function($query) {
			if(is_string($query)) {
				$query = array($query);
			} else {
				$query = array();
			}
			
			global $wp_query;
			// The slug will register as a post name or post attachement name.
			$slug = $wp_query->query_vars['name'];
			if($slug) {
				$query[] = WPDKAObject::DKA_CROWD_SLUG_SOLR_FIELD. ':"'. $wp_query->query_vars['name'] .'"';
			}
			
			return implode("+OR+", $query);
		});
	}
	
	public function define_object_construction_filters() {
		add_action(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_ACTION, function(WPChaosObject $object) {
			WPDKAObject::ensure_crowd_metadata($object);
		}, 10, 1);
	}
	
	public static function ensure_crowd_metadata(\WPChaosObject $object) {
		if(!$object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID)) {
			// TODO: Remove this when the CHAOS Service indexing works.
			//self::reset_crowd_metadata($object);
		}
	}
	
	public static function reset_crowd_metadata(\WPChaosObject $object) {
		$existingMetadata = $object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID);
		$revisionID = $existingMetadata != false ? $existingMetadata->RevisionID : null;
		
		// The object has not been extended with the crowd matadata schema.
		$objectGUID = $object->GUID;
		$metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkac:DKACrowd xmlns:dkac='http://www.danskkulturarv.dk/DKA-Crowd.xsd'></dkac:DKACrowd>");
		$metadataXML->addChild('Views', '0');
		$metadataXML->addChild('Shares', '0');
		$metadataXML->addChild('Likes', '0');
		$metadataXML->addChild('Ratings', '0');
		$metadataXML->addChild('AccumulatedRate', '0');
		$slug = WPDKAObject::generateSlug($object);
		$metadataXML->addChild('Slug', $slug);
		$metadataXML->addChild('Tags');
		
		$successfulValidation = $object->set_metadata(WPChaosClient::instance(), WPDKAObject::DKA_CROWD_SCHEMA_GUID, $metadataXML, WPDKAObject::DKA_CROWD_LANGUAGE, $revisionID);
		if($successfulValidation === false) {
			wp_die("Error validating the Crowd Schema");
		}
	}
	
	/**
	 * Generate a slug from a chaos object.
	 * @param \CHAOS\Portal\Client\Data\Object $object The object to generate the slug from.
	 * @return string The slug generated - prepended with a nummeric postfix to prevent douplicates.
	 */
	public static function generateSlug(\WPChaosObject $object) {
		$title = $object->title;
		
		$postfix = 0;
		$slug_base = sanitize_title_with_dashes($title);
		$slug_base = urldecode($slug_base);
		
		// Check if this results in dublicates.
		do {
			if($postfix == 0) {
				$slug = $slug_base; // Not needed
			} else {
				$slug = "$slug_base-$postfix";
			}
			$postfix++; // Try the next
		} while(self::getObjectFromSlug($slug) != null && self::getObjectFromSlug($slug)->GUID != $object->GUID); // Until no object is returned.
		
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
		$query = WPDKAObject::DKA_CROWD_SLUG_SOLR_FIELD. ':"' . $slug . '"';
		$response = WPChaosClient::instance()->Object()->Get($query, null, null, 0, 1);
		if(!$response->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->Error()->Message());
		} elseif (!$response->MCM()->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->MCM()->Error()->Message());
		} else {
			$count = $response->MCM()->TotalCount();
			if($count == 0) {
				return null;
			} elseif ($count > 1) {
				if(WP_DEBUG) {
					//throw new \CHAOSException("CHAOS returned more than one ($count) object for this slug: ". htmlentities($slug));
				}
			}
			$result = $response->MCM()->Results();
			return new \CHAOS\Portal\Client\Data\Object($result[0]);
		}
	}

	/**
	 * Register widgets in WordPress
	 * @return  void
	 */
	public function register_widgets() {
		register_widget( 'WPDKAObjectPlayerWidget' );
	}

}
//Instantiate
new WPDKAObject();

//eol