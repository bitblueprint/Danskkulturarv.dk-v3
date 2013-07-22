<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */

/**
 *
 * Class for CHAOS material
 * 
 * @property-read string $title 		Get title
 * @property-read string $organisation 	Get name of organisation
 * @property-read string $thumbnail_url Get url to thumbnail
 * @property-read string $thumbnail_caption Get caption to thumbnail
 * @property-read string $type 			Get type
 * @property-read int 	 $views 		Get number of views
 * @property-read int 	 $likes 		Get number of likes
 * @property-read string $created_date  Get date of creation (XMLDateTime)
 * @property-read array  $tags 			Get list of tags
 * @property-read mixed  $var
 */
class WPChaosObject {
	
	const CHAOS_OBJECT_CONSTRUCTION_FILTER = 'chaos-object-constrution';

	/**
	 * Object retrieved from CHAOS
	 * 
	 * @var stdClass
	 */
	protected $chaos_object;

	/**
	 * Constructor
	 * 
	 * @param stdClass $chaos_object
	 */
	public function __construct(\stdClass $chaos_object) {
		$this->chaos_object = apply_filters(self::CHAOS_OBJECT_CONSTRUCTION_FILTER, new \CHAOS\Portal\Client\Data\Object($chaos_object));
	}

	/**
	 * Magic getter for various metadata in CHAOS object
	 * Use like $class->$name
	 * Add filters like add_filter('wpchaos-object-'.$name,callback,priority,2)
	 * 
	 * @param  string $name Variable to get
	 * @return mixed 		Filtered data (from $chaos_object)
	 */
	public function __get($name) {

		// $method = 'get_'.$name;
		// if(method_exists($this, $method)) {
		// 	return $this->$method();
		// }

		//If no filters exist for this variable, it should probably not be used
		if(!array_key_exists(WPChaosClient::OBJECT_FILTER_PREFIX.$name, $GLOBALS['wp_filter'])) {
			throw new RuntimeException("There are no filters for this variable: $".$name);
		}
		return apply_filters(WPChaosClient::OBJECT_FILTER_PREFIX.$name, "", $this->chaos_object);
	}

	// public function get_type() {
	// 	var_dump($this->chaos_object->getObject()->Files);
	// }

}

//eol