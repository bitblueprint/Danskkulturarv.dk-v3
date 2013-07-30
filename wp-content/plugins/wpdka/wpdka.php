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

	/**
	 * Name for setting page
	 * @var string
	 */
	protected $menu_page = 'wpdka-administration';
	
	const CLEAN_UP_SLUGS_BTN = 'Clean up slugs';

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

			add_action('admin_menu', array(&$this, 'create_menu'));

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
	 * Create submenu and call page for settings
	 * @return void 
	 */
	public function create_menu() {
		add_menu_page(
			'Dansk Kulturarv',
			'DKA',
			'manage_options',
			$this->menu_page,
			array(&$this, 'create_menu_page'),
			'none',
			81
		); 
	}

	/**
	 * Create page for settings
	 * @return void
	 */
	public function create_menu_page() {
		echo '<div class="wrap"><h2>'.get_admin_page_title().'</h2>'."\n";
		if(array_key_exists('action', $_GET) && $_GET['action'] == self::CLEAN_UP_SLUGS_BTN) {
			echo '<div class="updated">';
			$this->clean_up_slugs();
			echo '</div>';
		}
		//Add section to WordPress
		echo '<h3>Dansk Kulturarv CHAOS Object slugs</h3>';
		echo '<p>These are the nice URLs every object gets when clicked in a search url. If the CHAOS indexing for some reason fails, multiple objects might be given the same slug, this is why it might be nessesary to clean these up.</p>';
		echo '<form method="get">';
		echo '<input type="hidden" name="page" value="'. esc_attr($_GET['page']) .'"/>';
		echo '<input type="submit" name="action" id="submit" class="button button-primary" value="'. self::CLEAN_UP_SLUGS_BTN .'" />';
		echo '</form>';
	}
	
	public function clean_up_slugs() {
		// We are cleaning up slugs ..
		echo "<h3>Cleaning up slugs</h3>";
		// Ask the index for all unique slugs.
		$response = WPChaosClient::instance()->Index()->Search('field:DKA-Crowd-Slug_string');
		foreach($response->Index()->Results() as $facetResults) {
			foreach($facetResults->FacetFieldsResult as $facetResult) {
				foreach($facetResult->Facets as $facet) {
					if($facet->Count > 1) {
						echo '<hr />';
						echo '<p><strong>' . $facet->Value . '</strong> has '.$facet->Count.' objects associated with it.</p>';
						$objectsResponse = WPChaosClient::instance()->Object()->Get('DKA-Crowd-Slug_string:' . $facet->Value, null, null, 0, $facet->Count, true);
						$objects = WPChaosObject::parseResponse($objectsResponse);
						echo '<ul>';
						foreach($objects as $object) {
							$metadataXML = WPDKAObject::reset_crowd_metadata($object);
							var_dump($metadataXML);
							echo '<li>' . $object->GUID . '</li>';
						}
						echo '<p>';
					}
				}
			}
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