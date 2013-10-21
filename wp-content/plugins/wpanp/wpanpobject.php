<?php
/**
 * @package WP ANP
 * @version 1.0
 */

/**
 * Class that manages CHAOS data specific to
 * Archive Network Pilot and registers attributes
 * for WPChaosObject
 */
class WPANPObject {

	static $OBJECT_TYPE_IDS = array(1);
	
	const ANP_SCHEMA_GUID = '22c70550-90ce-43f9-9176-973c09760138';

	/** XML for GUID: 22c70550-90ce-43f9-9176-973c09760138
	 * 
		<xs:schema attributeFormDefault="unqualified" elementFormDefault="qualified" xmlns:xs="http://www.w3.org/2001/XMLSchema">
			<xs:element name="FIATIFTA.ANP">
				<xs:complexType>
					<xs:sequence>
						<xs:element name="Title" type="xs:string" />
						<xs:element name="Abstract" type="xs:string" />
						<xs:element name="Description" type="xs:string" />
						<xs:element name="Publisher" type="xs:string" />
						<xs:element name="Identifier" type="xs:string" />
						<xs:element name="FirstPublicationDate" type="xs:dateTime" />
						<xs:element name="Coverage" type="xs:string" />
						<xs:element name="Subject" type="xs:string" />
						<xs:element name="Creator" type="xs:string" />
						<xs:element name="Contributor" type="xs:string" />
					</xs:sequence>
				</xs:complexType>
			</xs:element>
		</xs:schema>
	 */

	const METADATA_LANGUAGE = 'en';
	const SESSION_PREFIX = __CLASS__;
	
	// If more GUIDS or languages is added.
	public static $FREETEXT_SCHEMA_GUIDS = array(self::ANP_SCHEMA_GUID);
	public static $FREETEXT_LANGUAGE = array(self::METADATA_LANGUAGE, 'nl', 'it', 'da'); // 'de' doesn't work.

	public static $DERIVED_FILES = array(
		'|^(?P<streamer>rtmp://vod-bonanza\.gss\.dr\.dk/bonanza)/mp4:bonanza/(?P<filename>.+\.mp4)$|i' => 'http://om.gss.dr.dk/MediaCache/_definst_/mp4:content/bonanza/{$matches["filename"]}/Playlist.m3u8'
	);
	
	public static $KNOWN_STREAMERS = array(
		'rtmp://vod-bonanza.gss.dr.dk/bonanza/',
		'http://om.gss.dr.dk/MediaCache/_definst_/'
	);

	/**
	 * Construct
	 */
	public function __construct() {

		// add_action('admin_init',array(&$this,'check_chaosclient'));
	
		// Define the free-text search filter.
		WPANPObject::define_attribute_filters();

		
		// Define a filter for object creation.
		WPANPObject::define_object_construction_filters();

		add_action('widgets_init', array(&$this, 'register_widgets'));
		
		// Restrict chaos query to this object type.
		// $objectTypeConstraints = array();
		// foreach(self::$OBJECT_TYPE_IDS as $id) {
		// 	$objectTypeConstraints[] = "ObjectTypeID:$id";
		// }
		// WPChaosClient::instance()->addGlobalConstraint(implode('+OR+', $objectTypeConstraints));
	}

	const TYPE_VIDEO = 'video';
	const TYPE_AUDIO = 'lyd';
	const TYPE_IMAGE = 'billede';
	const TYPE_IMAGE_AUDIO = 'billede-lyd';
	const TYPE_UNKNOWN = 'unknown';
	
	/**
	 * How many seconds should we wait for the CHAOS service to realize the slug has changed?
	 * @var integer
	 */
	const RESET_TIMEOUT_S = 30; // 10 seconds.
	
	/**
	 * How many milliseconds delay between checking the service for the object to become searchable on the slug.
	 * @var integer
	 */
	const RESET_DELAY_MS = 100; // 0.1 seconds.

	public static $format_types = array(
		WPANPObject::TYPE_AUDIO => array(
			'class' => 'icon-volume-up',
			'title' => 'Lyd',
			'chaos-value' => 'audio'
			),
		WPANPObject::TYPE_IMAGE_AUDIO => array(
			'class' => 'icon-picture-sound',
			'title' => 'Billeder og lyd',
			'chaos-value' => 'unknown'
		),
		WPANPObject::TYPE_VIDEO => array(
			'class' => 'icon-film',
			'title' => 'Video',
			'chaos-value' => 'video'
		),
		// This is not yet supported by the metadata.
		//WPANPObject::TYPE_UNKNOWN => array(
		//	'class' => 'icon-file-text',
		//	'title' => 'Dokumenter',
		//),
		WPANPObject::TYPE_IMAGE => array(
			'class' => 'icon-picture',
			'title' => 'Billeder',
			'chaos-value' => 'image'
		),
		WPANPObject::TYPE_UNKNOWN => array(
			'class' => 'icon-circle-blank',
			'title' => 'Materiale',
			'chaos-value' => ''
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
			//FormatID = 57 is thumbnai format. We do not want that here.
			if($file->FormatCategory !== 'Thumbnail') {
			//if($file->FormatID != 57) {
				$format_types[$file->FormatType] = 1;
			}
			
		}

		//Video format
		if(isset($format_types['Video']))
			return self::TYPE_VIDEO;
		
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
		//object->title
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, \WPCHAOSObject $object) {
			return $value . $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Title'
			);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, \WPCHAOSObject $object) {
			// If we have no title at all.
			if($value == "") {
				$typeTitle = $object->type_title;
				// if($typeTitle == WPANPObject::TYPE_UNKNOWN) {
				// 	$typeTitle = __('Material','wpanp');
				// }
				return $typeTitle . __(' without title','wpanp');
			} else {
				return $value;
			}
		}, 20, 2);

		//object->tags_array
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'tags_raw', function($value, \WPCHAOSObject $object) {
			$tags = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Subject',
				null
			);

			$coverages = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Coverage',
				null
			);
			$arr = array_merge($tags, $coverages);

			//If there are no tags, null is returned above, we need an array
			return ($arr?:array());
		}, 10, 2);

		//object->tags
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'tags', function($value, \WPCHAOSObject $object) {
			$tags = $object->tags_raw;
			$ajaxurl = admin_url( 'admin-ajax.php' );
			echo <<<EOTEXT
<script type="text/javascript"><!--
function flagTag(e, tag) {
	e.preventDefault();
	var c = confirm('Are you sure you want to flag ' + tag);
	if (c) {

		// TODO AJAX call - Flag.
		/*$.ajax({
            url: ajaxurl,
            data:{
                action: 'wpdkatags_change_tag_state',
                state: 'WPDKATags::TAG_STATE_FLAGGED'
            },
            dataType: 'JSON',
            type: 'POST',
            success:function(data){
            },
            error: function(errorThrown){
                alert("Could not flag tag.");
            }
        });*/
	}
};
//--></script>
EOTEXT;
			foreach($tags as $key => &$tag) {
				//Remove tag if empty
				if(!$tag) {	
					unset($tags[$key]);
					continue;
				}
				// If more tags are in the same element.
				if (strpos($tag, ',') !== false) {
					$tags_split = explode(',', $tag);
					$tags_split = array_unique($tags_split); // Remove dublicates, if Subject and Coverage have the same tags.

					foreach ($tags_split as $tag) {
						$tag = trim($tag);
						if (empty($tag))
							continue;
						$link = WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag));
						$value .= '<a class="tag" href="'.$link.'" title="'.esc_attr($tag).'">'.$tag.' <i onClick="flagTag(event, \'' . $tag . '\');" class="icon-remove flag-tag"></i></a>'."\n";
					}
					continue;
				}
				$tag = trim($tag);
				$link = WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag));
				$value .= '<a class="tag" href="'.$link.'" title="'.esc_attr($tag).'">'.$tag.' <i onClick="flagTag(event, \'' . $tag . '\');" class="icon-remove flag-tag"></i></a>'."\n";
			}
			if(empty($tags)) {
				$value .= '<span class="no-tag">'.__('No tags','wpanp').'</span>'."\n";
			}
			return $value;
		}, 10, 2);

		//object->creator
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'creator', function($value, \WPCHAOSObject $object) {
			$creators = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Creator',
				null
			);
			return $value . WPANPObject::get_creator_attributes($creators);
		}, 10, 2);

		//object->contributor
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'contributor', function($value, \WPCHAOSObject $object) {
			$contributors = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Contributor',
				null
			);
			return $value . WPANPObject::get_creator_attributes($contributors);
		}, 10, 2);

		//object->organization_raw
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization_raw', function($value, \WPCHAOSObject $object) {
			$organization = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Publisher'
			);
			return $value . $organization;
		}, 10, 2);

		//object->organization
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, \WPCHAOSObject $object) {
			$organizations = WPANPSearch::get_organizations();
			$organization = $object->organization_raw;

			if(isset($organizations[$organization]))
				$organization = $organizations[$organization]['title'];

			return $value . $organization;
		}, 10, 2);

		//object->organization_link
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization_link', function($value, \WPCHAOSObject $object) {
			$organizations = WPANPSearch::get_organizations();
			$organization = $object->organization_raw;

			if(isset($organizations[$organization])) {
				$value .= get_permalink($organizations[$organization]['id']);
			} else {
				$value .= get_permalink(get_option('wpanp-default-organization-page'));
			}

			return $value;
		}, 10, 2);

		//object->description
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'description', function($value, \WPCHAOSObject $object) {
			return $value . $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/Description'
			);
		}, 10, 2);

		//object->published
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, \WPCHAOSObject $object) {
			$time = $object->metadata(
				WPANPObject::ANP_SCHEMA_GUID,
				'/FIATIFTA.ANP/FirstPublicationDate'
			);
			
			if($time) {
				$time = strtotime($time);
				//If january 1st, only print year, else get format from WordPress
				if(date("d-m",$time) == "01-01") {
					$time = __('Year ','wpanp').date_i18n('Y',$time);
				} else {
					$time = date_i18n(get_option('date_format'),$time);
				}
			}
			
			return $value . $time;
		}, 10, 2);

		//object->rights
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'rights', function($value, $object) {
			return null;
			// return $value . $object->metadata(
			// 	WPANPObject::ANP_SCHEMA_GUID,
			// 	'/FIATIFTA.ANP/<rights>'
			// );
		}, 10, 2);

		//object->views
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'views', function($value, $object) {
			return null;
			// return $value . $object->metadata(
			// 	WPANPObject::ANP_SCHEMA_GUID,
			// 	'/FIATIFTA.ANP/<views>'
			// );
		}, 10, 2);

		//object->type
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, \WPCHAOSObject $object) {
			return $value . WPANPObject::determine_type($object);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_class', function($value, \WPCHAOSObject $object) {
			$type = $object->type;
			return $value . (isset(WPANPObject::$format_types[$type]) ? WPANPObject::$format_types[$type]['class'] : $type);
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type_title', function($value, \WPCHAOSObject $object) {
			$type = $object->type;
			return $value . (isset(WPANPObject::$format_types[$type]) ? WPANPObject::$format_types[$type]['title'] : $type);
		}, 10, 2);

		//object->thumbnail
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'thumbnail', function($value, \WPCHAOSObject $object) {
			foreach($object->Files as $file) {
				// FormatID = 10 is thumbnail format. This is what we want here
				if($file->FormatCategory == 'Thumbnail' && $file->FormatType == 'Image') {
					return $value . htmlspecialchars($file->URL);
				}
			}
			// Try another image - any image will do.
			// TODO: Consider using a serverside cache and downscaling service.
			/*
			foreach($object->Files as $file) {
				// FormatID = 10 is thumbnail format. This is what we want here
				if($file->FormatType == "Image") {
					return $value . htmlspecialchars($file->URL);
				}
			}
			*/
			// Fallback to nothing
			return null;
		}, 10, 2);

		//object->url
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'url', function($value, \WPCHAOSObject $object) {
				return $object->organization_link . $value . '?guid=' . $object->GUID;
		}, 10, 2);

		//object->externalurl
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'externalurl', function($value, \WPCHAOSObject $object) {
			return null;
			// return $value . $object->metadata(
			// 	WPANPObject::ANP_SCHEMA_GUID,
			// 	'/FIATIFTA.ANP/<external_url>'
			// );
		}, 10, 2);

		//object->caption
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'caption', function($value, $object) {
			if($object->type == WPANPObject::TYPE_IMAGE || $object->type == WPANPObject::TYPE_IMAGE_AUDIO) {
				$realImages = 0;
				foreach(WPChaosClient::get_object()->Files as $file) {
					if($file->FormatType == 'Image' && $file->FormatCategory == 'Image Source') {
						$realImages++;
					}
				}
				return $value . sprintf(_n('%s image', '%s images', $realImages,'wpanp'),$realImages);
			} else {
				return $value;
			}
		}, 10, 2);
		
		//object->rights - Turn URLs into links.
		// add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'rights', function($value, $object) {
		// 	return WPANPObject::replace_url_with_link($value);
		// }, 11, 2);

	}

	public static function get_creator_attributes($creators) {
		$value = "";	
		if($creators) {
			//Some roles are in English, gettext cannot translate variables, thus this whitelist
			$role_i18n = array(
				'actors' => __('Actors','wpanp'),
				'cinematography' => __('Cinematography','wpanp'),
				'creator' => __('Creator','wpanp'),
				'direction' => __('Direction','wpanp'),
				'directors' => __('Directors','wpanp'),
				'production' => __('Production','wpanp'),
				'script' => __('Script','wpanp'),

			);

			$value .= "<dl>\n";
			foreach($creators as $creator) {
				// If more creators are in the same element.
				if (strpos($creator, ',') !== false) {
					$creators_split = explode(',', $creator);
					foreach ($creators_split as $creator) {
						$creator = trim($creator);
						if (empty($creator))
							continue;
						$role = strtolower(strval($creator['Role']));
						if (isset($role_i18n[$role])) {
							$role = $role_i18n[$role];
						} else {
							$creator_role = self::search_for_role($creator);
							if (empty($creator_role)) {
								$role = ucfirst($creator['Role']);
							} else {
								$role = ucfirst($creator_role[1]);
								$creator = $creator_role[0];
							}
						}
						$value .= "<dt>".$role."</dt>\n";
						$value .= "<dd>".$creator."</dd>\n";
					}
					continue;
				}
				$role = strtolower(strval($creator['Role']));
				$role = (isset($role_i18n[$role]) ? $role_i18n[$role] : ucfirst($creator['Role']));
				$value .= "<dt>".$role."</dt>\n";
				$value .= "<dd>".$creator."</dd>\n";
			}
			$value .= "</dl>\n";
		} else {
			$value .= "<p>".__('Not provided','wpanp')."</p>\n";
		}
		return $value;
	}

	/**
	 * Looking for words in a string with role and creator, e.g. 'producer' and returns creator and role
	 * @param  string $creator string with creator and role
	 * @return array          array with two elements (1. creator, 2. role)
	 */
	private function search_for_role($creator) {
		$roles = array('producer', 'producent');
		foreach ($roles as $r) {
			if (strpos($creator, $r) !== FALSE) {
				$c = preg_replace('/\s*\(' . $r . '\)/', '', $creator);
				return array($c, $r);
			}
		}
		return false;
	}
	
	public function define_object_construction_filters() {
		/*
		add_action(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_ACTION, function(WPChaosObject $object) {
			WPANPObject::ensure_crowd_metadata($object);
		}, 10, 1);
		*/
		// Hack: Adding a HLS version of videos from DR
		// Make this change on the metadata instead.
		add_action(WPChaosObject::CHAOS_OBJECT_CONSTRUCTION_ACTION, function(WPChaosObject $object) {
			$originalObject = $object->getObject();
			foreach($originalObject->Files as $file) {
				foreach(WPANPObject::$DERIVED_FILES as $regexp => $transformation) {
					$matches = null;
					if($file->Token == "RTMP Streaming" && preg_match($regexp, $file->URL, $matches)) {
						// Perform the transformation.
						eval('$url = "'.$transformation.'";');
						$newFile = (object) array_merge((array) $file, array(
							'URL' => $url,
							'Token' => 'HLS Streaming',
							'Streamer' => $matches['streamer']
						));
						// $originalObject->Files[] = $newFile;
						array_unshift($originalObject->Files, $newFile);
					}
				}
			}
			foreach($originalObject->Files as &$file) {
				// We're not interested in anything but a video.
				if($file->FormatType != 'Video') continue;
				
				$file->Streamer = null;
				foreach(WPANPObject::$KNOWN_STREAMERS as $streamer) {
					if(strstr($file->URL, $streamer) !== false) {
						$file->Streamer = $streamer;
						
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
					}
				}
			}
			return $object;
		}, 10, 1);
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
		register_widget( 'WPANPObjectPlayerWidget' );
	}

}

//Instantiate
new WPANPObject();

//eol
