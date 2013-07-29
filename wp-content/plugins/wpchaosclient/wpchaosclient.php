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
	
	const GET_OBJECT_PAGE_BEFORE_TEMPLATE_ACTION = 'wpchaos-before-get-object-page-template';
	
	const GENERATE_SINGLE_OBJECT_SOLR_QUERY = 'wpchaos-generate-single-object-solr-query';
	
	public static $debug_calls = array();

	/**
	 * Constructor
	 */
	public function __construct() {

		$this->load_dependencies();

		add_action('admin_menu', array(&$this,'create_submenu'));
		add_action('admin_init', array(&$this,'register_settings'));
		add_action('admin_init', array(&$this,'settings_updated'));
		add_action('template_redirect', array(&$this,'get_object_page'));
		add_action('widgets_init', array(&$this,'add_widget_areas'), 99);
		
		add_action('chaos-settings-updated', function() {
			WPChaosClient::instance()->resetSession();
		});
		
		$thiz = $this;
		$prev_handler = set_exception_handler(function($e) use ($thiz) {
			if($e instanceof \CHAOSException) {
				$thiz->handle_chaos_exception($e);
			}
		});
		
		if(WP_DEBUG && array_key_exists('debug-chaos', $_GET)) {
			add_action('wpportalclient-service-call-returned', function($call) {
				WPChaosClient::$debug_calls[] = $call;
			});
			
			add_action('wp_footer', function() {
				echo "<div class='debugging-chaos-requests' style='background:#EEEEEE;position:absolute;top:0px;left:0px;right:0px;opacity:0.9;z-index:1050;padding:1em;'>";
				$c = 1;
				foreach(WPChaosClient::$debug_calls as $call) {
					echo "<div class='debugging-chaos-call' style='border-bottom:1px solid black;'>";
					echo "<h1>$c of ". count(WPChaosClient::$debug_calls) ." call(s) to the CHAOS service.</h1>";
					echo "<pre style='margin:1em;color:#000000;'>";
					echo htmlentities(print_r($call, true));
					echo "</pre></div>";
					$c++;
				}
				echo "</div>";
			});
		}
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
			$sessionGUID = WPChaosClient::instance()->SessionGUID();
			$lastSessionUpdate = get_option(WPPortalClient::WP_CHAOS_CLIENT_SESSION_UPDATED_KEY);
			printf('<div class="updated"><p><strong>&#x2713; Connection to CHAOS is established</strong> (session is %s last updated %s)</p></div>', $sessionGUID, date('r', $lastSessionUpdate));
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
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h2 class="widget-title">',
			'after_title' => '</h2>',
		) );

		register_sidebar( array(
			'id' => 'wpchaos-obj-main',
			'name' => 'CHAOS Object - Main',
			'before_widget' => '',
			'after_widget' => '',
			'before_title' => '<h3 class="widget-title">',
			'after_title' => '</h3>',
		) );

		register_sidebar( array(
			'id' => 'wpchaos-obj-sidebar',
			'name' => 'CHAOS Object - Sidebar',
			'before_widget' => '<li id="%1$s" class="widget %2$s">',
			'after_widget' => '</li>',
			'before_title' => '<h4 class="widget-title">',
			'after_title' => '</h4>',
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
		$searchQuery = apply_filters(self::GENERATE_SINGLE_OBJECT_SOLR_QUERY, isset($_GET['guid'])?self::escapeSolrValue($_GET['guid']):null);
		
		if($searchQuery) {
			$serviceResult = self::instance()->Object()->Get(
				$searchQuery,	// Search query
				null,	// Sort
				null,	// AccessPoint given by settings.
				0,		// pageIndex
				1,		// pageSize
				true,	// includeMetadata
				true,	// includeFiles
				true	// includeObjectRelations
			);
			
			// No need for a 404 page - as the template is just not applied if the 
			if($serviceResult->MCM()->TotalCount() >= 1) {
				// TODO: Test if this works.
				if($serviceResult->MCM()->TotalCount() > 1) {
					error_log('CHAOS returned more than 1 (actually '.$serviceResult->MCM()->TotalCount().') results for the single object page (query was '. $searchQuery .').');
				}
				$objects = $serviceResult->MCM()->Results();
				$object = new WPChaosObject($objects[0]);
				self::set_object($object);
			
				do_action(self::GET_OBJECT_PAGE_BEFORE_TEMPLATE_ACTION, self::get_object());
	
				//Look in theme dir and include if found
				$include = locate_template('templates/chaos-object-page.php', false);
				if($include == "") {
					//Include from plugin template	
					$include = plugin_dir_path(__FILE__)."/templates/object-page.php";
				}
				require($include);
				self::reset_object();
				exit();
			}
		}
	}

	/**
	 * Render field according to its type
	 * @param  array $args Setting array
	 * @return void
	 */
	public function create_setting_field($args) {
		$class = isset($args['class'])?$args['class']:'regular-text';
		$current_value = get_option($args['name'])?get_option($args['name']):'';
		switch($args['type']) {
			case 'textarea':
				echo '<textarea class="'.$class.'" name="'.$args['name'].'" >'.$current_value.'</textarea>';
				break;
			case 'select':
				if(!is_array($args['list']))
					$args['list'] = array();
				echo '<select class="'.$class.'" name="'.$args['name'].'">';
				foreach($args['list'] as $key => $value) {
					echo '<option value="'.$key.'" '.selected( $current_value, $key, false).'>'.$value.'</option>';
				}
				echo '</select>';
				break;
			case 'password':
				echo '<input name="'.$args['name'].'" type="password" value="'.$current_value.'" />';
				break;
			case 'text':
			default:
				echo '<input class="'.$class.'" name="'.$args['name'].'" type="text" value="'.$current_value.'" />';
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
			self::$instance = new WPPortalClient(get_option('wpchaos-servicepath'), get_option('wpchaos-clientguid'));
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
	
	public function handle_chaos_exception(\CHAOSException $exception) {
		$trace = $exception->getTrace();
		
		// Generate a filename for the trace dump file.
		$traceDumpFile = tempnam(sys_get_temp_dir(), 'chaos-tracedump-');
		
		// Log this exception.
		if($traceDumpFile != false) {
			file_put_contents($traceDumpFile, json_encode($trace));
			error_log('CHAOS Error: "' . $exception->getMessage() . '" (tracedump stored in '. $traceDumpFile .')', 0);
		} else {
			error_log('CHAOS Error: "' . $exception->getMessage() . '" (unable to store tracedump)', 0);
		}
		
		if(in_array_r(get_option('wpchaos-email'), $exception->getTrace(), true) || in_array_r(get_option('wpchaos-password'), $exception->getTrace(), true)) {
			$trace = null;
			$exception = null;
		}
		
		if(locate_template('chaos-exception.php', true) == "") {
			require(plugin_dir_path(__FILE__)."/templates/chaos-exception.php");
		}
		locate_template('footer.php', true);
	}

	/**
	 * Escape characters to be used in SOLR
	 * @param  string $string 
	 * @return string         
	 */
	public static function escapeSolrValue($string)
	{
		$match = array('#', '&', '\\', '+', '-', '&', '|', '!', '(', ')', '{', '}', '[', ']', '^', '~', '*', '?', ':', '"', ';', ' '); // The # and & is apparently CHAOS specific.
		$replace = array('', '', '\\\\', '\\+', '\\-', '\\&', '\\|', '\\!', '\\(', '\\)', '\\{', '\\}', '\\[', '\\]', '\\^', '\\~', '\\*', '\\?', '\\:', '\\"', '\\;', '\\ ');
		$string = str_replace($match, $replace, $string);
	
		return $string;
	}

	/**
	 * Load files and libraries
	 * @return void 
	 */
	private function load_dependencies() {

		//For CHAOS lib
		set_include_path(get_include_path() . PATH_SEPARATOR . __DIR__ ."/lib/chaos-client/src/");
		require("CaseSensitiveAutoload.php");
		spl_autoload_extensions(".php");
		spl_autoload_register("CaseSensitiveAutoload");

		require("wpportalclient.php");
		require("wpchaosobject.php");
		require("widgets/attribute.php");
		require("widgets/multiattribute.php");
	}

}
//Instantiate
new WPChaosClient();

/**
 * http://stackoverflow.com/questions/4128323/in-array-and-multidimensional-array
 * @param unknown $needle
 * @param unknown $haystack
 * @param string $strict
 * @return boolean
 */
function in_array_r($needle, $haystack, $strict = false) {
	foreach ($haystack as $item) {
		if (($strict ? $item === $needle : $item == $needle) || (is_array($item) && in_array_r($needle, $item, $strict))) {
			return true;
		}
	}

	return false;
}

//eol