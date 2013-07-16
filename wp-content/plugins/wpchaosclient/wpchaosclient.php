<?php
/**
 * @package Hello_Dolly
 * @version 1.0
 */
/*
Plugin Name: WordPress Chaos Client
Plugin URI: 
Description: 
Author: 
Version: 1.0
Author URI: 
*/

class WPChaosClient {

	/**
	 * Name for setting section
	 * @var string
	 */
	protected $menu_page = 'wpchaos-settings';

	/**
	 * Settings
	 * @var array
	 */
	protected $settings;

	/**
	 * Singleton instance of Chaos Portal
	 * @var [type]
	 */
	public static $instance;

	/**
	 * Construct
	 */
	public function __construct() {

		$this->settings = array(
			array(
				'name' => 'wpchaos-servicepath',
				'title' => 'Service Path',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-clientguid',
				'title' => 'Client GUID',
				'type' => 'text'
			),
			array(
				'name' => 'wpchaos-apguid',
				'title' => 'Access Point GUID',
				'type' => 'text'
			)
		);

		add_action('admin_menu', array(&$this,'create_submenu'));
		add_action('admin_init', array(&$this,'register_settings'));
	}

	/**
	 * Create and register setting fields for administration
	 * @return void 
	 */
	public function register_settings() {

	 	add_settings_section('default',
			'General settings',
			null,
			$this->menu_page);

	 	// Loop through each setting
	 	foreach($this->settings as $setting) {

	 		//Validate
	 		if(!isset($setting['title'],$setting['name'],$setting['type']))
	 			continue;

	 		add_settings_field($setting['name'],
				$setting['title'],
				array(&$this,'create_setting_field'),
				$this->menu_page,
				'default',
				$setting);
	 	
	 		register_setting($this->menu_page,$setting['name']);
	 	}
	 	
	 	
	 }

	/**
	 * Create submenu and call page for settings
	 * @return void 
	 */
	public function create_submenu() {
		add_submenu_page(
			'options-general.php',
			'CHAOS Client',
			'CHAOS',
			'manage_options',
			$this->menu_page,
			array(&$this,'create_submenu_page')
		); 
	}

	/**
	 * Create page for settings
	 * @return void
	 */
	public function create_submenu_page() {
		echo '<div class="wrap"><h2>'.get_admin_page_title().'</h2>'."\n";
		echo '<form method="POST" action="options.php">'."\n";
		settings_fields($this->menu_page);
		do_settings_sections($this->menu_page);
		submit_button();
		echo '</form></div>'."\n";
		//echo "SessionGUID: " . WPChaosClient::instance()->SessionGUID() . "<br>";

	}

	/**
	 * Render field according to its type
	 * @param  array $args Setting array
	 * @return void
	 */
	public function create_setting_field($args) {
		switch($args['type']) {
			case 'text':
			default:
				echo '<input name="'.$args['name'].'" type="text" value="'.get_option($args['name']).'" />';
		}
	}

	/**
	 * Get instance of CHAOS Portal
	 * @return WPPortalClient 
	 */
	public static function instance() {
		if(WPChaosClient::$instance == null) {
			//Instantiate CHAOS Portal
			WPChaosClient::$instance = new WPPortalClient(get_option('wpchaos-servicepath'),get_option('wpchaos-clientguid'));
		}
		return $this->instance;
	}

}
new WPChaosClient();


set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/lib/chaos-client/src/"); // <-- Relative path to Portal Client

require_once("CaseSensitiveAutoload.php");

spl_autoload_extensions(".php");
spl_autoload_register("CaseSensitiveAutoload");

use CHAOS\Portal\Client\PortalClient;
class WPPortalClient extends PortalClient {

	public function CallService($path, $method, array $parameters = null, $requiresSession = true) {
		if(!isset($parameters['accessPointGUID']) || $parameters['accessPointGUID'] == null) {
			$parameters['accessPointGUID'] = get_option('wpchaos-apguid');
		}
		parent::CallService($path, $method, $parameters, $requiresSession);
	}

}
 
 //WPChaosClient::instance()->;

//eol