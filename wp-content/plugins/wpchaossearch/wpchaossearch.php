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
	const QUERY_KEY_PAGEINDEX = 'pageIndex';
	const QUERY_KEY_TYPE = 'type';
	
	const QUERY_PREFIX_CHAR = '/';

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
			
			// Rewrite tags and rules should always be added.
			if(count(self::$search_query_variables) == 0) {
				self::register_search_query_variable(self::QUERY_KEY_FREETEXT,	'[\w%+-]+');
				self::register_search_query_variable(self::QUERY_KEY_TYPE, '[\w+]+', true, ' ');
				self::register_search_query_variable(self::QUERY_KEY_PAGEINDEX, '\d+');
			}
			add_action('init', array(&$this, 'add_rewrite_tags'));
			add_action('init', array(&$this, 'add_rewrite_rules'));
			
			// Add some custom rewrite rules.
			add_filter('mod_rewrite_rules', array(&$this, 'custom_mod_rewrite_rules'));
			
			// Add rewrite rules when activating and when settings update.
			register_activation_hook(__FILE__, array(&$this, 'flush_rewrite_rules'));
			add_action('chaos-settings-updated', array(&$this, 'flush_rewrite_rules'));
			if(WP_DEBUG) {
				add_action('admin_init', array(&$this, 'maybe_flush_rewrite_rules'));
			}

			add_action('wpchaos-search-fields',array(&$this,'render_search_fields'),10);
			
		}

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
	public static function get_search_vars() {
		global $wp_query;
		// Clone the query vars
		$variables = array_merge(array(), $wp_query->query_vars);
		foreach($variables as $k => &$v) {
			if(gettype($v) == 'string') {
				$v = urldecode($v);
				foreach(self::$search_query_variables as $variable) {
					if($variable['key'] == $k && isset($variable['multivalue-seperator'])) {
						if($v == '') {
							$v = array();
						} else {
							$v = explode($variable['multivalue-seperator'], $v);
						}
					}
				}
			}
		}
		return array_merge(array(), $_GET, $variables);
	}
	
	/**
	 * Get a search parameter for a specific key
	 * @param  string  $query_key 
	 * @param  boolean $escape    
	 * @return string             
	 */
	public static function get_search_var($query_key, $escape = false) {
		$query_vars = self::get_search_vars();
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
		$args['pageindex'] = WPChaosSearch::get_search_var(self::QUERY_KEY_PAGEINDEX, 'intval');
		$args['pageindex'] = ($args['pageindex'] >= 0?$args['pageindex']:0);
		
		$query = apply_filters('wpchaos-solr-query', $args['query'], WPChaosSearch::get_search_vars());
		
		$serviceResult = WPChaosClient::instance()->Object()->Get(
			$query,	// Search query
			$args['sort'],	// Sort
			$args['accesspoint'],	// AccessPoint given by settings.
			$args['pageindex'],		// pageIndex
			$args['pagesize'],		// pageSize
			true,	// includeMetadata
			true,	// includeFiles
			true	// includeObjectRelations
		);
		
		$objects = $serviceResult->MCM()->Results();
		
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
	
	public static function register_search_query_variable($key, $regexp, $prefix_key = false, $multivalue_seperator = null) {
		self::$search_query_variables[] = array(
			'key' => $key,
			'regexp' => $regexp,
			'prefix-key' => $prefix_key,
			'multivalue-seperator' => $multivalue_seperator
		);
	}

	/**
	 * Add rewrite tags to WordPress installation
	 */
	public function add_rewrite_tags() {
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
	public function add_rewrite_rules() {
		if(get_option('wpchaos-searchpage')) {
			$searchPageID = intval(get_option('wpchaos-searchpage'));
			$searchPageName = get_page_uri($searchPageID);
			
			/*
			$regex = sprintf('%s(?:/(%s))/?$', $searchPageName, '[\w%+-]+');
			$redirect = sprintf('index.php?pagename=%s&%s=$matches[1]', $searchPageName, self::QUERY_KEY_FREETEXT);
			add_rewrite_rule($regex, $redirect, 'top');
			*/
			
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
			for($v = 0; $v < count(self::$search_query_variables); $v++) {
				// An optional non-capturing group wrapped around the $regexp.
				$redirect .= sprintf('&%s=$matches[%u]', self::$search_query_variables[$v]['key'], $v+1);
			}
			
			add_rewrite_rule($regex, $redirect, 'top');
		}
	}
	
	public function custom_mod_rewrite_rules($rules) {
		// Calculating $home_root - just as in wp-includes/rewrite.php:1640
		$home_root = parse_url(home_url());
		if ( isset( $home_root['path'] ) )
			$home_root = trailingslashit($home_root['path']);
		else
			$home_root = '/';
		
		$custom_rules = array();
		$custom_rules[] = "# Custom redirections for search.";
		$custom_rules[] = "<IfModule mod_rewrite.c>";
		$custom_rules[] = "RewriteEngine On";
		$custom_rules[] = "RewriteBase $home_root";
		
		$custom_rules[] = ''; // Space is nice ..
		// Redirecting the ?text={?} to search/{?}
		$custom_rules[] = 'RewriteCond %{QUERY_STRING} (.*)text=([^&]*)&?(.*)';
		$custom_rules[] = 'RewriteRule ^search/? search/%2/?%1%3 [L,NE,R=302]';

		$custom_rules[] = ''; // Space is nice ..
		// Redirecting the ?pageIndex={?} to search/.../{?}
		$custom_rules[] = 'RewriteCond %{QUERY_STRING} (.*)pageIndex=([^&]*)&?(.*)';
		$custom_rules[] = 'RewriteRule ^search/([^/]*)/? search/$1/%2/?%1%3 [L,NE,R=302]';
		
		// TODO: Check if ([^/]*) is okay when searching on something with "/" in the freetext.
		
		$custom_rules[] = "</IfModule>";
		
		$rules = implode("\n", $custom_rules) . "\n\n" . $rules;
		return $rules;
	}
	
	/**
	 * A method that flushes the rewrite rules when this file is changed or
	 * if 48hrs has passed. Set WP_DEBUG true to make this work.
	 * @see http://codex.wordpress.org/Function_Reference/flush_rewrite_rules
	 */
	public function maybe_flush_rewrite_rules() {
		$ver = filemtime( __FILE__ ); // Get the file time for this file as the version number
		$defaults = array( 'version' => 0, 'time' => time() );
		$r = wp_parse_args( get_option( __CLASS__ . '_flush', array() ), $defaults );
		
		if ( $r['version'] != $ver || $r['time'] + 172800 < time() ) { // Flush if ver changes or if 48hrs has passed.
			$this->flush_rewrite_rules();
			if(WP_DEBUG) {
				add_action( 'admin_notices', function() { 
					echo '<div class="updated"><p><strong>WordPress CHAOS Search</strong> Rewrite rules flushed ..</p></div>';
				}, 10);
			}
			$args = array( 'version' => $ver, 'time' => time() );
			if ( ! update_option( __CLASS__ . '_flush', $args ) )
				add_option( __CLASS__ . '_flush', $args );
		}
	}
	
	public function flush_rewrite_rules() {
		flush_rewrite_rules(true);
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
//Instantiate
new WPChaosSearch();

//eol
