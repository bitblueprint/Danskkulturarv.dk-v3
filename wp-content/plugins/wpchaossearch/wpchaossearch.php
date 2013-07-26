<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
/*
Plugin Name: WordPress Chaos Search
Plugin URI: 
Description: Module enabling search in CHAOS. Depends on WordPress Chaos Client.
Author: Joachim Jensen <joachim@opensourceshift.com>
Version: 1.0
Author URI: 
*/

/**
 * WordPress Chaos Search enables functionality
 * to search for and display CHAOS material
 */
class WPChaosSearch {

	const QUERY_KEY_FREETEXT = 'text';
	const QUERY_KEY_PAGE = 'searchPage';
	
	const QUERY_PREFIX_CHAR = '/';
	
	const FLUSH_REWRITE_RULES_OPTION_KEY = 'wpchaos-flush-rewrite-rules';

	public static $search_results;

	/**
	 * Plugins depending on
	 * @var array
	 */
	public $plugin_dependencies = array(
		'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client',
	);

	/**
	 * Construct
	 */
	public function __construct() {

		if($this->check_chaosclient()) {

			$this->load_dependencies();

			add_action('admin_init', array(&$this, 'check_chaosclient'));
			add_action('widgets_init', array(&$this, 'register_widgets'));
			add_action('template_redirect', array(&$this, 'get_search_page'));

			add_filter('wpchaos-config', array(&$this, 'settings'));

			add_shortcode('chaosresults', array(&$this, 'shortcode_searchresults'));

			WPChaosSearch::register_search_query_variable(1, WPChaosSearch::QUERY_KEY_FREETEXT, '[^/&]+', false, null, ' ');
			WPChaosSearch::register_search_query_variable(10, WPChaosSearch::QUERY_KEY_PAGE, '\d+');
			
			// Rewrite tags and rules should always be added.
			add_action('init', array('WPChaosSearch', 'handle_rewrite_rules'));
			
			// Add some custom rewrite rules.
			// This is implemented as a PHP redirect instead.
			// add_filter('mod_rewrite_rules', array(&$this, 'custom_mod_rewrite_rules'));
			
			// Add rewrite rules when activating and when settings update.
			add_action('chaos-settings-updated', array('WPChaosSearch', 'flush_rewrite_rules_soon'));
			
		}

	}
	
	public static function install() {
		WPChaosSearch::flush_rewrite_rules_soon();
	}
	
	public static function uninstall() {
		flush_rewrite_rules();
	}

	/**
	 * CHAOS settings for this module
	 * 
	 * @param  array $settings Other CHAOS settings
	 * @return array           Merged CHAOS settings
	 */
	public function settings($settings) {

		$pages = array(); 
		foreach(get_pages() as $page) {
			$pages[$page->ID] = $page->post_title;
		}

		$new_settings = array(array(
			/*Sections*/
			'name'		=> 'search',
			'title'		=> 'Search Settings',
			'fields'	=> array(
				/*Section fields*/
				array(
					'name' => 'wpchaos-searchpage',
					'title' => 'Page for search results',
					'type' => 'select',
					'list' => $pages,
					'precond' => array(array(
						'cond' => (get_option('permalink_structure') != ''),
						'message' => 'Permalinks must be enabled for CHAOS search to work properly'
					))
				)
			)
		));
		return array_merge($settings,$new_settings);
	}

	/**
	 * Register widgets for administration
	 * 
	 * @return void 
	 */
	public function register_widgets() {
	    register_widget( 'WPChaos_Search_Widget' );
	}

	/**
	 * Get search parameters
	 * @return array 
	 */
	public static function get_search_vars($urldecode = true) {
		global $wp_query;
		$variables = array();
		foreach(self::$search_query_variables as $variable) {
			if(array_key_exists($variable['key'], $wp_query->query_vars)) {
				$value = $wp_query->query_vars[$variable['key']];
				if(gettype($value) == 'string') {
					if($urldecode) {
						$value = urldecode($value);
					}
					// Wordpress is replacing this for us .. Thanks - but no thanks.
					$value = str_replace("\\\"", "\"", $value); // Replace \" with "
					$value = str_replace("\\'", "\'", $value); // Replace \' with '
					if(isset($variable['multivalue-seperator'])) {
						if($value == '') {
							$value = array();
						} else {
							$value = explode($variable['multivalue-seperator'], $value);
						}
					}
				}
				//echo $variable['key']. ": '$value'\n";
				$variables[$variable['key']] = $value;
			}
		}
		return $variables;
	}
	
	/**
	 * Get a search parameter for a specific key
	 * @param  string  $query_key 
	 * @param  boolean $escape    
	 * @return string             
	 */
	public static function get_search_var($query_key, $escape = false, $urldecode = true) {
		$query_vars = self::get_search_vars($urldecode);
		if(array_key_exists($query_key, $query_vars)) {
			if($escape !== false) {
				if(function_exists($escape)) {
					return $escape($query_vars[$query_key]);
				} else {
					throw new InvalidArgumentException('The $escape argument must be false or a 1-argument function.');
				}
			} else {
				return $query_vars[$query_key];
			}
		} else {
			return '';
		}
	}

	/**
	 * Get (and print) template for a search page
	 * @return void 
	 */
	public function get_search_page() {
		$this->search_query_prettify();
		//Include template for search results
		if(get_option('wpchaos-searchpage') && is_page(get_option('wpchaos-searchpage'))) {

			//Look in theme dir and include if found
			$include = locate_template('templates/chaos-full-width.php', false);
			if($include == "") {
				//Include from plugin template	
				$include = plugin_dir_path(__FILE__)."/templates/full-width.php";
			}
			require($include);
			exit();
		}
	}
	
	/**
	 * Wrap shortcode around search results
	 * @param  string $args 
	 * @return void       
	 */
	public function shortcode_searchresults( $args ) {
		$args = shortcode_atts( array(
			'query' => "",
			'pageindex' => 0,
			'pagesize' => 20,
			'sort' => null,
			'accesspoint' => null
		), $args );

		return $this->generate_searchresults($args);
	}

	/**
	 * Generate data and include template for search results
	 * @param  array $args 
	 * @return string The markup generated.
	 */
	public function generate_searchresults($args) {	

		$args['pageindex'] = WPChaosSearch::get_search_var(self::QUERY_KEY_PAGE, 'intval')-1;
		$args['pageindex'] = ($args['pageindex'] >= 0?$args['pageindex']:0);
		
		$query = apply_filters('wpchaos-solr-query', $args['query'], WPChaosSearch::get_search_vars());
		
		self::set_search_results(WPChaosClient::instance()->Object()->Get(
			$query,	// Search query
			$args['sort'],	// Sort
			$args['accesspoint'],	// AccessPoint given by settings.
			$args['pageindex'],		// pageIndex
			$args['pagesize'],		// pageSize
			true,	// includeMetadata
			true,	// includeFiles
			true	// includeObjectRelations
		));
		
		$objects = self::get_search_results()->MCM()->Results();

		// Buffering the output as this method is returning markup - not printing it.
		ob_start();
		//Look in theme dir and include if found
		$include = locate_template('templates/chaos-search-results.php', false);
		if($include == "") {
			//Include from plugin template	
			$include = plugin_dir_path(__FILE__)."/templates/search-results.php";
		}
		require($include);
		// Return the markup generated in the template and clean the output buffer.
		return ob_get_clean();
	}

	/**
	 * Render HTML for search form
	 * @param  string $placeholder 
	 * @return void              
	 */
	public static function create_search_form($freetext_placeholder = "") {
		if(get_option('wpchaos-searchpage')) {
			$page = get_permalink(get_option('wpchaos-searchpage'));
		} else {
			$page = "";
		}	
		
		// echo "<pre>";
		// print_r(WPChaosSearch::get_search_vars());
		// echo "</pre>";	
		
		$include = locate_template('templates/chaos-search-form.php', false);
		if($include == "") {
			//Include from plugin template		
			$include = plugin_dir_path(__FILE__)."/templates/search-form.php";
		}
		require($include);

		
	}
	
	public static $search_query_variables = array();
	
	public static function register_search_query_variable($position, $key, $regexp, $prefix_key = false, $multivalue_seperator = null, $default_value = null) {
		self::$search_query_variables[$position] = array(
			'key' => $key,
			'regexp' => $regexp,
			'prefix-key' => $prefix_key,
			'multivalue-seperator' => $multivalue_seperator,
			'default_value' => $default_value
		);
		ksort(self::$search_query_variables);
	}
	
	/**
	 * Add rewrite tags to WordPress installation
	 */
	public static function add_rewrite_tags() {
		foreach(self::$search_query_variables as $variable) {
			// If prefix-key is set - the 
			if(isset($variable['prefix-key'])) {
				add_rewrite_tag('%'.$variable['key'].'%', $variable['key'].self::QUERY_PREFIX_CHAR.'('.$variable['regexp'].')');
			} else {
				add_rewrite_tag('%'.$variable['key'].'%', '('.$variable['regexp'].')');
			}
		}
	}

	/**
	 * Add rewrite rules to WordPress installation
	 */
	public static function add_rewrite_rules() {
		if(get_option('wpchaos-searchpage')) {
			$searchPageID = intval(get_option('wpchaos-searchpage'));
			$searchPageName = get_page_uri($searchPageID);
			
			$regex = $searchPageName;
			foreach(self::$search_query_variables as $variable) {
				// An optional non-capturing group wrapped around the $regexp.
				if($variable['prefix-key'] == true) {
					$regex .= sprintf('(?:/%s(%s))?', $variable['key'].self::QUERY_PREFIX_CHAR, $variable['regexp']);
				} else {
					$regex .= sprintf('(?:/(%s))?', $variable['regexp']);
				}
			}
			$regex .= '/?$';
			
			$redirect = "index.php?pagename=$searchPageName";
			$v = 1;
			foreach(self::$search_query_variables as $variable) {
				// An optional non-capturing group wrapped around the $regexp.
				$redirect .= sprintf('&%s=$matches[%u]', $variable['key'], $v);
				$v++;
			}
			
			add_rewrite_rule($regex, $redirect, 'top');
		}
	}
	
	public static function search_query_prettify() {
		foreach(self::$search_query_variables as $variable) {
			if(array_key_exists($variable['key'], $_GET)) {
				$redirection = self::generate_pretty_search_url(self::get_search_vars(false));
				wp_redirect($redirection);
				exit();
			}
		}
	}
	
	public static function generate_pretty_search_url($variables = array()) {
		$variables = array_merge(self::get_search_vars(), $variables);
		// Start with the search page uri.
		$result = '/' . get_page_uri(get_option('wpchaos-searchpage')) . '/';
		foreach(self::$search_query_variables as $variable) {
			$value = $variables[$variable['key']];
			if(empty($value) && $variable['default_value'] != null) {
				$value = $variable['default_value'];
			}
			if(!empty($value)) {
				if(is_array($value)) {
					$value = implode($variable['multivalue-seperator'], $value);
				}
				$value = urlencode($value);
				if($variable['prefix-key']) {
					$result .= $variable['key'] . self::QUERY_PREFIX_CHAR . $value . '/';
				} else {
					$result .= $value . '/';
				}
			}
		}
		return $result;
	}
	
	/**
	 * A method that flushes the rewrite rules when this file is changed or
	 * if 48hrs has passed. Set WP_DEBUG true to make this work.
	 * @see http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */
	/*
	public static function maybe_flush_rewrite_rules() {
		$ver = filemtime( __FILE__ ); // Get the file time for this file as the version number
		$defaults = array( 'version' => 0, 'time' => time() );
		$r = wp_parse_args( get_option( __CLASS__ . '_flush', array() ), $defaults );
		
		if ( $r['version'] != $ver || $r['time'] + 172800 < time() ) { // Flush if ver changes or if 48hrs has passed.
			//self::flush_rewrite_rules();
			$args = array( 'version' => $ver, 'time' => time() );
			if ( ! update_option( __CLASS__ . '_flush', $args ) )
				add_option( __CLASS__ . '_flush', $args );
		}
	}
	*/
	
	public static function flush_rewrite_rules_soon() {
		update_option(self::FLUSH_REWRITE_RULES_OPTION_KEY, true);
	}

	/**
	 * Flush rewrite rules hard
	 * @return void 
	 */
	public static function handle_rewrite_rules() {
		self::add_rewrite_tags();
		self::add_rewrite_rules();
		if(get_option(self::FLUSH_REWRITE_RULES_OPTION_KEY)) {
			delete_option(self::FLUSH_REWRITE_RULES_OPTION_KEY);
			if(WP_DEBUG) {
				add_action( 'admin_notices', function() {
					echo '<div class="updated"><p><strong>WordPress CHAOS Search</strong> Rewrite rules flushed ..</p></div>';
				}, 10);
			}
			flush_rewrite_rules();
		}
	}

	/**
	 * Get object holding search results
	 * @return [type] 
	 */
	public static function get_search_results() {
		return self::$search_results;
	}

	/**
	 * Set object for search results
	 * @param [type] $search_results
	 * @return void
	 */
	public static function set_search_results($search_results) {
		self::$search_results = $search_results;
	}

	/**
	 * Check if dependent plugins are active
	 * 
	 * @return void 
	 */
	public function check_chaosclient() {
		//$plugin = plugin_basename( __FILE__ );
		$dep = array();
		//if(is_plugin_active($plugin)) {
			foreach($this->plugin_dependencies as $class => $name) {
				if(!in_array($class,get_option('active_plugins'))) {
					$dep[] = $name;
				}
			}
			if(!empty($dep)) {
				//deactivate_plugins(array($plugin));
				add_action( 'admin_notices', function() use (&$dep) { 
					echo '<div class="error"><p><strong>WordPress CHAOS Search</strong> needs <strong>'.implode('</strong>, </strong>',$dep).'</strong> to be activated.</p></div>';
				},10);
				return false;
			}
		//}
		return true;
	}

	/**
	 * Load dependent files and libraries
	 * 
	 * @return void 
	 */
	private function load_dependencies() {
		require('widgets/search.php');
	}

}

register_activation_hook(__FILE__, array('WPChaosSearch', 'install'));
register_deactivation_hook(__FILE__, array('WPChaosSearch', 'uninstall'));

//Instantiate
new WPChaosSearch();

//eol
