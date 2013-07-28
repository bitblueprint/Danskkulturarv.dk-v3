<?php
/**
 * @package WP DKA
 * @version 1.0
 */
/*
Plugin Name: WordPress DKA
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
class WPDKA {

	//List of plugins depending on
	private static $plugin_dependencies = array(
		'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client',
		'wpchaossearch/wpchaossearch.php' => 'WordPress Chaos Search'
	);

	/**
	 * Construct
	 */
	public function __construct() {
		
		if(self::check_chaosclient()) {

			$this->load_dependencies();

		}

	}
	
	public static function install() {
		if(self::check_chaosclient()) {
			WPChaosSearch::flush_rewrite_rules_soon();
		}
	}
	
	public static function uninstall() {
		if(self::check_chaosclient()) {
			WPChaosSearch::flush_rewrite_rules_soon();
		}
	}
	
	/**
	 * Check if dependent plugins are active
	 * 
	 * @return void 
	 */
	public static function check_chaosclient() {
		//$plugin = plugin_basename( __FILE__ );
		$dep = array();
		//if(is_plugin_active($plugin)) {
			foreach(self::$plugin_dependencies as $class => $name) {
				if(!in_array($class, get_option('active_plugins'))) {
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
	 * Load files and libraries
	 * @return void 
	 */
	private function load_dependencies() {
		require('wpdkaobject.php');
		require('wpdkasearch.php');
		require('widgets/player.php');
	}

}

register_activation_hook(__FILE__, array('WPDKA', 'install'));
register_deactivation_hook(__FILE__, array('WPDKA', 'uninstall'));

//Instantiate
new WPDKA();

//eol