<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
/*
Plugin Name: WordPress Chaos Client
Plugin URI: 
Description: Easily connect to CHAOS Portal
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
	 * @var WPPortalClient
	 */
	public static $instance;

	/**
	 * Construct
	 */
	public function __construct() {

		$this->load_dependencies();

		add_action('admin_menu', array(&$this,'create_submenu'));
		add_action('admin_init', array(&$this,'register_settings'));
	}

	/**
	 * Create and register setting fields for administration
	 * @return void 
	 */
	public function register_settings() {

		$this->settings = apply_filters('wpchaos-config', include('config.php'));

		foreach($this->settings as $section) {

			//Validate
			if(!isset($section['name'],$section['title'],$section['fields'])) 
				continue;

			add_settings_section(
				$section['name'],
				$section['title'],
				null,
				$this->menu_page
			);

			foreach($section['fields'] as $setting) {
				//Validate
		 		if(!isset($setting['title'],$setting['name'],$setting['type']))
		 			continue;

		 		add_settings_field($setting['name'],
					$setting['title'],
					array(&$this,'create_setting_field'),
					$this->menu_page,
					$section['name'],
					$setting);
		 	
		 		register_setting($this->menu_page,$setting['name']);
			}

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

		try {
			WPChaosClient::instance()->SessionGUID();
			echo '<div class="updated"><p>Connection established to CHAOS.</p></div>';
		} catch(Exception $e) {
			echo '<div class="error"><p>Could not connect to CHAOS. Please check the details below.</p></div>';
		} 

		echo '<form method="POST" action="options.php">'."\n";
		settings_fields($this->menu_page);
		do_settings_sections($this->menu_page);
		submit_button();
		echo '</form></div>'."\n";
		

	}

	/**
	 * Render field according to its type
	 * @param  array $args Setting array
	 * @return void
	 */
	public function create_setting_field($args) {
		switch($args['type']) {
			case 'select':
				echo '<select name="'.$args['name'].'">';
				foreach($args['list'] as $key => $value) {
					echo '<option value="'.$key.'" '.selected( get_option($args['name']), $key, false).'>'.$value.'</option>';
				}
				echo '</select>';
				break;
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
		if(self::$instance == null) {
			//Instantiate CHAOS Portal
			self::$instance = new WPPortalClient(get_option('wpchaos-servicepath'),get_option('wpchaos-clientguid'));
		}
		return self::$instance;
	}

	/**
	 * Load files and libraries
	 * @return void 
	 */
	private function load_dependencies() {
		set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/lib/chaos-client/src/");

		require_once("CaseSensitiveAutoload.php");

		spl_autoload_extensions(".php");
		spl_autoload_register("CaseSensitiveAutoload");
	}

}
//Instantiate
new WPChaosClient();

use CHAOS\Portal\Client\PortalClient;
class WPPortalClient extends PortalClient {

	public function CallService($path, $method, array $parameters = null, $requiresSession = true) {
		if(!isset($parameters['accessPointGUID']) || $parameters['accessPointGUID'] == null) {
			$parameters['accessPointGUID'] = get_option('wpchaos-apguid');
		}
		return parent::CallService($path, $method, $parameters, $requiresSession);
	}

}

//eol