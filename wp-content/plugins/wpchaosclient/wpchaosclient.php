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

	protected $menu_page = 'wpchaos-settings';
	protected $settings;

	public function __construct() {

		$this->settings = array(
			array(
				'name' => 'wpchaos-servicepath',
				'title' => 'Service Path',
				'callback_field' => 'create_text_field'
			),
			array(
				'name' => 'wpchaos-clientguid',
				'title' => 'Client GUID',
				'callback_field' => 'create_text_field'
			),
			array(
				'name' => 'wpchaos-apguid',
				'title' => 'Access Point GUID',
				'callback_field' => 'create_text_field'
			)
		);

		add_action('admin_menu', array(&$this,'create_submenu'));
		add_action('admin_init', array(&$this,'register_settings'));
	}

	public function register_settings() {

	 	add_settings_section('default',
			'General settings',
			null,
			$this->menu_page);

	 	foreach($this->settings as $setting) {
	 		add_settings_field($setting['name'],
				$setting['title'],
				$setting['callback_field'],
				$this->menu_page,
				'default',
				$setting);
	 	
	 		register_setting($this->menu_page,$setting['name']);
	 	}
	 	
	 	
	 }

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

	public function create_submenu_page() {
		echo '<div class="wrap"><h2>'.get_admin_page_title().'</h2>'."\n";
		echo '<form method="POST" action="options.php">'."\n";
		settings_fields($this->menu_page);
		do_settings_sections($this->menu_page);
		submit_button();
		echo '</form></div>'."\n";
	}

}
new WPChaosClient();
 
 // ------------------------------------------------------------------
 // Callback function for our example setting
 // ------------------------------------------------------------------
 //
 // creates a checkbox true/false option. Other types are surely possible
 //
 
 function create_text_field($args) {
 	echo '<input name="'.$args['name'].'" type="text" value="'.get_option($args['name']).'" />';
 }

?>

