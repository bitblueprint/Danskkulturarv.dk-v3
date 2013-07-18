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

class WPDKAObject {

	public $plugin_dependencies = array(
		'WPChaosClient' => 'WordPress Chaos Client',
	);

	const DKA_SCHEMA_GUID = '00000000-0000-0000-0000-000063c30000';
	const DKA2_SCHEMA_GUID = '5906a41b-feae-48db-bfb7-714b3e105396';

	/**
	 * Construct
	 */
	public function __construct() {

		add_action('admin_init',array(&$this,'check_chaosclient'));
		
		// Registering namespaces.
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka', 'http://www.danskkulturarv.dk/DKA.xsd');
		\CHAOS\Portal\Client\Data\Object::registerXMLNamespace('dka2', 'http://www.danskkulturarv.dk/DKA2.xsd');
		
		// Defining the filters - used to present the object.
		
		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'title', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA2_SCHEMA_GUID, '/dka2:DKA/dka2:Title/text()');
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'organization', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA2_SCHEMA_GUID, '/dka2:DKA/dka2:Organization/text()');
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'published', function($value, $object) {
			return $value . $object->metadata(WPDKAObject::DKA2_SCHEMA_GUID, '/dka2:DKA/dka2:FirstPublishedDate/text()');
		}, 10, 2);

		add_filter(WPChaosClient::OBJECT_FILTER_PREFIX.'type', function($value, $object) {

			return $value = WPDKAObject::determine_type($object);

		}, 10, 2);

	}

	const TYPE_VIDEO = 'series';
	const TYPE_AUDIO = 'audio';
	const TYPE_UNKNOWN = 'unknown';

	public static function determine_type($object) {
		
		foreach($object->getObject()->Files as $file) {
			if($file->FormatType == 'Video')
				return self::TYPE_VIDEO;
		}
		return self::TYPE_UNKNOWN;

	}

	/**
	 * Check if dependent plugin is active
	 * 
	 * @return void 
	 */
	public function check_chaosclient() {
		$plugin = plugin_basename( __FILE__ );
		$dep = array();
		if(is_plugin_active($plugin)) {
			foreach($this->plugin_dependencies as $class => $name) {
				if(!class_exists($class)) {
					$dep[] = $name;
				}	
			}
			if(!empty($dep)) {
				deactivate_plugins(array($plugin));
				add_action( 'admin_notices', function() use (&$dep) { $this->deactivate_notice($dep); },10);
			}
		}
	}

	/**
	 * Render admin notice when dependent plugin is inactive
	 * 
	 * @return void 
	 */
	public function deactivate_notice($classes) {
		echo '<div class="error"><p>WordPress Chaos Search needs '.implode(',',(array)$classes).' to be activated.</p></div>';
	}

}
//Instantiate
new WPDKAObject();

//eol
