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
		// $this->define_object_construction_filters();
		
		$this->define_single_object_page();

		add_filter('widgets_init',array(&$this,'register_widgets'));
		
		// Restrict chaos query to this object type.
		WPChaosClient::instance()->addGlobalConstraint('ObjectTypeID:' . self::OBJECT_TYPE_ID);
	}

	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'audio';
	const TYPE_IMAGE = 'image';
	const TYPE_IMAGE_AUDIO = 'image-audio';
	const TYPE_UNKNOWN = 'unknown';
	
	/**
	 * How many seconds should we wait for the CHAOS service to realize the slug has changed?
	 * @var integer
	 */
	const RESET_TIMEOUT_S = 10; // 10 seconds.
	
	/**
	 * How many milliseconds delay between checking the service for the object to become searchable on the slug.
	 * @var integer
	 */
	const RESET_DELAY_MS = 100; // 0.1 seconds.

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
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, \WPCHAOSObject $object) {
			return $value . $object->metadata(
				array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
				array('/dka2:DKA/dka2:Title/text()', '/DKA/Title/text()')
			);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, \WPCHAOSObject $object) {
			// If we have no title at all.
			if($value == "") {
				$typeTitle = $object->type_title;
				if($typeTitle == WPDKAObject::TYPE_UNKNOWN) {
					$typeTitle = __('Materiale');
				}
				return $typeTitle . __(' uden title');
			} else {
				return $value;
			}
		}, 20, 2);

		//object->tags
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'tags', function($value, \WPCHAOSObject $object) {
			$tags = $object->metadata(
				array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
				array('/dka2:DKA/dka2:Tags/dka2:Tag','/DKA/Tags/Tag'),
				null
			);
			return $tags;
		}, 10, 2);

		//object->organization_raw
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization_raw', function($value, \WPCHAOSObject $object) {
			$organization = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Organization/text()', '/DKA/Organization/text()')
			);
			return $value . $organization;
		}, 10, 2);

		//object->organization
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, \WPCHAOSObject $object) {
			$organizations = WPDKASearch::get_organizations();
			$organization = $object->organization_raw;

			if(isset($organizations[$organization]))
				$organization = $organizations[$organization]['title'];

			return $value . $organization;
		}, 10, 2);

		//object->organization_link
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization_link', function($value, \WPCHAOSObject $object) {
			$organizations = WPDKASearch::get_organizations();
			$organization = $object->organization_raw;

			if(isset($organizations[$organization]))
				$organization = get_permalink($organizations[$organization]['id']);

			return $value . $organization;
		}, 10, 2);

		//object->description
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'description', function($value, \WPCHAOSObject $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:Description', '/DKA/Description/text()')
			);
		}, 10, 2);

		//object->published
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, \WPCHAOSObject $object) {
			$time = $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:FirstPublishedDate/text()', '/DKA/FirstPublishedDate/text()')
			);
			//Format date according to WordPress
			$time = date_i18n(get_option('date_format'),strtotime($time));
			return $value . $time;
		}, 10, 2);

		//object->rights
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'rights', function($value, $object) {
			return $value . $object->metadata(
					array(WPDKAObject::DKA2_SCHEMA_GUID, WPDKAObject::DKA_SCHEMA_GUID),
					array('/dka2:DKA/dka2:RightsDescription/text()', '/DKA/RightsDescription/text()')
			);
		}, 10, 2);

		//object->type
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, \WPCHAOSObject $object) {
			return $value . WPDKAObject::determine_type($object);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_class', function($value, \WPCHAOSObject $object) {
			$type = $object->type;
			return $value . (isset(WPDKAObject::$format_types[$type]) ? WPDKAObject::$format_types[$type]['class'] : $type);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_title', function($value, \WPCHAOSObject $object) {
			$type = $object->type;
			return $value . (isset(WPDKAObject::$format_types[$type]) ? WPDKAObject::$format_types[$type]['title'] : $type);
		}, 10, 2);

		//object->thumbnail
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'thumbnail', function($value, \WPCHAOSObject $object) {
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
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'slug', function($value, \WPCHAOSObject $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Slug/text()');
		}, 10, 2);

		//object->url
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'url', function($value, \WPCHAOSObject $object) {
			$result = site_url() . '/';
			$slug = $object->slug;
			if($slug) {
				$organizations = WPDKASearch::get_organizations();
				if(array_key_exists($object->organization_raw, $organizations)) {
					$result .= $organizations[$object->organization_raw]['slug'] . '/';
				}
				return $result . $slug . '/' . $value;
			} else {
				return $result . '?guid=' . $object->GUID . $value;
			}
		}, 10, 2);

		//object->views
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'views', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Views/text()');
		}, 10, 2);

		//object->shares
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'shares', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Shares/text()');
		}, 10, 2);

		//object->likes
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'likes', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Likes/text()');
		}, 10, 2);

		//object->ratings
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'ratings', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Ratings/text()');
		}, 10, 2);

		//object->accumulatedrate
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'accumulatedrate', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:AccumulatedRate/text()');
		}, 10, 2);

		//object->tags
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'usertags', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID, '/dkac:DKACrowd/dkac:Tags/text()');
		}, 10, 2);

	}
	
	public function define_single_object_page() {
		// Ensure the DKA Crowd metadata schema is present, and redirect to the slug URL if needed.
		add_action(WPChaosClient::GET_OBJECT_PAGE_BEFORE_TEMPLATE_ACTION, function(\WPChaosObject $object) {
			// If a guid was used to retreive the object, this might not have the crowd metadata connected to it.
			if(array_key_exists('guid', $_GET)) {
				try {
					$object = WPDKAObject::ensure_crowd_metadata($object, true);
					$redirection = $object->url;
					status_header(301);
					header("Location: $redirection");
				} catch(\CHAOSException $e) {
					error_log($e->getMessage());
					wp_die($e->getMessage());
				}
				exit;
			}
		});
		
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
	
	/*
	public function define_object_construction_filters() {
		add_action(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_ACTION, function(WPChaosObject $object) {
			WPDKAObject::ensure_crowd_metadata($object);
		}, 10, 1);
	}
	*/
	
	public static function ensure_crowd_metadata(\WPChaosObject $object, $ensureObjectIsReachableFromSlug = false) {
		// Is this the admin force resetting from URL?
		$forceReset = WP_DEBUG && array_key_exists('reset-crowd-metadata', $_GET) && current_user_can('edit_posts');
		
		if($forceReset || !$object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID)) {
			$slug = self::reset_crowd_metadata($object);
		} else {
			// If the metadata is present, we can extract the slug from there.
			$slug = $object->slug;
		}
		
		// If needed, a loop is performed until the object is reachable or a timeout is reached and an exception is thrown.
		if($ensureObjectIsReachableFromSlug) {
			// Make sure the object is reachable on the slug, by performing multiple requests for the object until its returned.
			$start = time(); // Time in milliseconds
			while(($object = self::getObjectFromSlug($slug)) == null) {
				$now = time();
				if($now > $start + self::RESET_TIMEOUT_S) {
					// Timeout was reached.
					throw new \CHAOSException("reset_crowd_metadata loop failed to find the CHAOS object within the timeout (".self::RESET_TIMEOUT_S."s)");
				}
				usleep(self::RESET_DELAY_MS * 1000);
			};
		}
		
		if($forceReset) {
			$link = $_SERVER["HTTP_HOST"] . $_SERVER["REQUEST_URI"];
			$link = str_replace('reset-crowd-metadata', '', $link);
			$link = "<a href='$link'>Click to get back</a>";
			wp_die("Crowd Metadata was reset: $link");
		}
		
		return $object;
	}
	
	public static function reset_crowd_metadata(\WPChaosObject $object) {
		$existingMetadata = $object->has_metadata(WPDKAObject::DKA_CROWD_SCHEMA_GUID);
		$revisionID = $existingMetadata != false ? $existingMetadata->RevisionID : null;
		
		// The object has not been extended with the crowd matadata schema.
		$objectGUID = $object->GUID;
		$metadataXML = new SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' standalone='yes'?><dkac:DKACrowd xmlns:dkac='http://www.danskkulturarv.dk/DKA-Crowd.xsd'></dkac:DKACrowd>");
		$metadataXML->addChild('Views', WPDKAObject::restore_views($object->GUID));
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
		return $slug;
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
		
		$try_next_slug = true;
		// Check if this results in dublicates.
		do {
			if($postfix == 0) {
				$slug = $slug_base; // Not needed
			} else {
				$slug = "$slug_base-$postfix";
			}
			
			// Objects
			$objects = self::getObjectFromSlug($slug, true);
			// If there is one object on this slug, and this is not itself.
			// Or if there are more than one object on the slug.
			$try_next_slug = (count($objects) == 1 && $objects[0]->GUID != $object->GUID) || (count($objects) > 1);
			
			$postfix++; // Try the next
		} while($try_next_slug); // Until no object is returned.
		
		return $slug;
	}
	
	/**
	 * Gets an object from the CHAOS Service from an alphanummeric, lowercase slug.
	 * @param string $slug The slug to search for.
	 * @param boolean $returnMultiple Makes the function return an array of objects, a single element array if only one object is found.
	 * @throws \RuntimeException If an error occurs in the service.
	 * @return NULL|\WPChaosObject|\WPChaosObject[] The object(s) matching the slug - multiple if 
	 */
	public static function getObjectFromSlug($slug, $returnMultiple = false) {
		// TODO: Use this instead, when DKA-Slug is added to the index.
		// $response = WPChaosClient::instance()->Object()->Get("DKA-Slug:'$slug'");
		$query = WPDKAObject::DKA_CROWD_SLUG_SOLR_FIELD. ':"' . $slug . '"';
		$response = WPChaosClient::instance()->Object()->Get($query, null, null, 0, 1, true);
		if(!$response->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->Error()->Message());
		} elseif (!$response->MCM()->WasSuccess()) {
			throw new \RuntimeException("Couldn't get object from slug: ".$response->MCM()->Error()->Message());
		} else {
			$count = $response->MCM()->TotalCount();
			if($count == 0) {
				return null;
			} elseif ($count > 1) {
				//if(WP_DEBUG) {
				//	throw new \CHAOSException("CHAOS returned more than one ($count) object for this slug: ". htmlentities($slug));
				//}
			}
			if($returnMultiple) {
				return WPChaosObject::parseResponse($response);
			} else {
				$results = $response->MCM()->Results();
				return new \WPChaosObject($results[0]);
			}
		}
	}
	
	protected static $legacy_views = array();
	protected static $legacy_views_file = 'views.csv';
	
	public static function restore_views($guid) {
		if(count(self::$legacy_views) == 0) {
			$legacy_views_file = realpath(__DIR__ . DIRECTORY_SEPARATOR . self::$legacy_views_file);
			if($legacy_views_file) {
				$legacy_views = file_get_contents($legacy_views_file);
				$legacy_views = explode("\n", $legacy_views);
				foreach($legacy_views as $row) {
					$row = explode(',', $row);
					if($row[0]) {
						self::$legacy_views[$row[0]] = intval($row[1]);
					}
				}
			}
		}
		if(array_key_exists($guid, self::$legacy_views)) {
			return self::$legacy_views[$guid];
		} else {
			return 0;
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