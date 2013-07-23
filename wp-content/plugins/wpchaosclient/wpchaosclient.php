<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
/*
Plugin Name: WordPress Chaos Client
Plugin URI: 
Description: Adds connectivity to CHAOS Portal and API to manipulate data from CHAOS objects in WordPress.
Author: Joachim Jensen <joachim@opensourceshift.com>
Version: 1.0
Author URI: 
*/

class WPChaosClient {

	/**
	 * Name for setting page
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
	 * Container for current Chaos object
	 * @var WPChaosObject|null
	 */
	public static $object;

	/**
	 * List of attributes that has a filter
	 * @var array
	 */
	public static $attributes;

	/**
	 * Prefix for filters to be used on WPChaosObject
	 */
	const OBJECT_FILTER_PREFIX = 'wpchaos-object-';

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->load_dependencies();

		add_action('admin_menu', array(&$this,'create_submenu'));
		add_action('admin_init', array(&$this,'register_settings'));
		add_action('admin_init', array(&$this,'settings_updated'));
		add_action('template_redirect', array(&$this,'get_object_page'));
		add_action('widgets_init', array(&$this,'add_widget_areas'),99);

	}

	/**
	 * Create and register setting fields for administration
	 * @return void 
	 */
	public function register_settings() {

		//Populate
		$this->settings = apply_filters('wpchaos-config', include('config.php'));

		foreach($this->settings as $section) {

			//Validate
			if(!isset($section['name'],$section['title'],$section['fields'])) 
				continue;

			//Add section to WordPress
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

		 		//Are there any preconditions for this field to work properly?
		 		if(isset($setting['precond'])) {
		 			foreach($setting['precond'] as $precondition) {
		 				if(!$precondition['cond'])
		 					add_action( 'admin_notices', function() use(&$precondition) { echo '<div class="error"><p>'.$precondition['message'].'</p></div>'; },10);
		 			} 				
		 		}

		 		// Add field to section
		 		add_settings_field($setting['name'],
					$setting['title'],
					array(&$this,'create_setting_field'),
					$this->menu_page,
					$section['name'],
					$setting);

		 		// Register field to be manipulated with
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
	 * Register widget areas and widgets in WordPress
	 * @return  void
	 */
	public function add_widget_areas() {

		register_sidebar( array(
			'id' => 'wpchaos-obj-featured',
			'name' => 'CHAOS Object - Featured',
			'before_widget' => '<div id="%1$s" class="widget %2$s">',
			'after_widget' => '</div>',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'id' => 'wpchaos-obj-main',
			'name' => 'CHAOS Object - Main',
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		 register_widget( 'WPChaosObjectAttrWidget' );
		 register_widget( 'WPChaosObjectMultiWidget' );
	}

	/**
	 * Get list of attributes for the CHAOS objects,
	 * i.e. the list of registered filters for the WPChaosObject
	 * @return array
	 */
	public static function get_chaos_attributes() {
		global $wp_filter;
		if(empty(self::$attributes)) {
			$matches = array();
			foreach($wp_filter as $filter => $arr) {
				if(preg_match('/^'.self::OBJECT_FILTER_PREFIX.'(.*)/',$filter,$matches)) {
					self::$attributes[$matches[1]] = ucfirst($matches[1]);
				}
			}
		}

		return self::$attributes;
	}

	/**
	 * Get data and include template for a single CHAOS object
	 * @return void 
	 */
	public function get_object_page() {
	//index.php?&org=1&slug=2 => /org/slug/
	//org&guid
		if(isset($_GET['guid'])) {

			//do some chaos here
			//
			$serviceResult = self::instance()->Object()->Get(
			WPDKAObject::escapeSolrValue($_GET['guid']),	// Search query
			null,	// Sort
			null,	// AccessPoint given by settings.
			0,		// pageIndex
			1,		// pageSize
			true,	// includeMetadata
			true,	// includeFiles
			true	// includeObjectRelations
		);
			
			//Set 404 if no content is found
			if($serviceResult->MCM()->TotalCount() < 1) {
				  global $wp_query;
				  $wp_query->set_404();
				  status_header( 404 );
				  get_template_part( 404 );
				  exit();

			//Set up object and include template
			} else {
				$objects = $serviceResult->MCM()->Results();
				$object = $objects[0];
				self::set_object($object);
				$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");
			}

			//Look in theme dir and include if found
			if(locate_template('chaos-object-page.php', true) != "") {
			
			//Include from plugin
			} else {
				include(plugin_dir_path(__FILE__)."/templates/object-page.php");
			}
			self::reset_object();
			exit();
		}
	}

	/**
	 * Render field according to its type
	 * @param  array $args Setting array
	 * @return void
	 */
	public function create_setting_field($args) {
		switch($args['type']) {
			case 'textarea':
				echo '<textarea name="'.$args['name'].'" >'.get_option($args['name']).'</textarea>';
				break;
			case 'select':
				if(!is_array($args['list']))
					$args['list'] = array();
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
	 * This is called on admin_init to check if this plugins options was updated.
	 * @return void 
	 */
	public function settings_updated() {
		global $pagenow;
		$on_options_page = ($pagenow == 'options-general.php');
		$on_plugins_page = (isset($_GET['page']) && $_GET['page'] == $this->menu_page);
		$just_updated = (isset($_GET['settings-updated']) && $_GET['settings-updated'] == 'true');
		
		if($on_options_page && $on_plugins_page && $just_updated) {
			do_action('chaos-settings-updated');
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
	 * Get instance of current CHAOS object (if any)
	 * @return WPChaosObject|null
	 */
	public static function get_object() {
		return self::$object;
	}
	/**
	 * Set current CHAOS object
	 * @param WPChaosObject|stdClass|null
	 * @return void
	 */
	public static function set_object($object) {
		if($object instanceof \stdClass) {
			$object = new WPChaosObject($object);
		}
		self::$object = $object;
	}

	/**
	 * Free current object
	 * @return void 
	 */
	public static function reset_object() {
		self::set_object(null); 
	}

	/**
	 * Load files and libraries
	 * @return void 
	 */
	private function load_dependencies() {

		//For CHAOS lib
		set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/lib/chaos-client/src/");
		require_once("CaseSensitiveAutoload.php");
		spl_autoload_extensions(".php");
		spl_autoload_register("CaseSensitiveAutoload");

		require_once("wpportalclient.php");
		require_once("wpchaosobject.php");
		require_once("/widgets/attribute.php");
		require_once("/widgets/multiattribute.php");
	}

}
//Instantiate
new WPChaosClient();

//eol