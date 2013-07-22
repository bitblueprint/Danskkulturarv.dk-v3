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

			add_action('admin_init',array(&$this,'check_chaosclient'));
			add_action('widgets_init', array(&$this,'register_widgets'));
			add_action('template_redirect', array(&$this,'get_search_page'));

			add_filter('wpchaos-config',array(&$this,'settings'));

			add_shortcode('chaosresults', array(&$this,'shortcode_searchresults'));
			
			// Add rewrite rules when activating and when settings update.
			register_activation_hook(__FILE__, array(&$this, 'add_rewrite_rules'));
			add_action('chaos-settings-updated', array(&$this, 'add_rewrite_rules'));
			
			// Rewrite tags should always be added.
			add_action('init', array(&$this, 'add_rewrite_tags'));

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
		return array_merge(array(), $_GET, $wp_query->query_vars);
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
	 * Get template for a search page
	 * @return void 
	 */
	public function get_search_page() {
		//Include template for search results
		if(get_option('wpchaos-searchpage') && is_page(get_option('wpchaos-search-page'))) {
			//Look in theme dir and include if found
			if(locate_template('chaos-full-width.php', true) != "") {
			
			//Include from plugin
			} else {
				include(plugin_dir_path(__FILE__)."/templates/full-width.php");
			}
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

		return $this->get_searchresults($args);
	}

	/**
	 * Get data and include template for search results
	 * @param  array $args 
	 * @return void       
	 */
	public function get_searchresults($args) {
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
		//Look in theme dir and include if found
		if(locate_template('chaos-search-results.php', true) != "") {		
			//Include from plugin
		} else {
			include(plugin_dir_path(__FILE__)."/templates/search-results.php");
		}

	}

	/**
	 * Render HTML for search form
	 * @param  string $placeholder 
	 * @return void              
	 */
	public static function create_search_form($placeholder = "") {
		if(get_option('wpchaos-searchpage')) {
			$page = get_permalink(get_option('wpchaos-searchpage'));
		} else {
			$page = "";
		}
		
		$freetext = WPChaosSearch::get_search_var(self::QUERY_KEY_FREETEXT, 'esc_attr');
		
		echo '<form method="GET" action="'.$page.'">'."\n";

		echo '<div class="input-append">'."\n";
		echo '<input class="span7" id="appendedInputButton" type="text" name="'.self::QUERY_KEY_FREETEXT.'" value="'.$freetext.'" placeholder="'.$placeholder.'" /><button type="submit" class="btn btn-large btn-search">Søg</button>'."\n";
		echo '</div>'."\n";

		echo '<div class="btn-group pull-right span4">'."\n";
		echo '<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning</button>'."\n";
		echo '</div>'."\n";

		echo '</form>'."\n";
	}

	/**
	 * Add rewrite tags to WordPress installation
	 */
	public function add_rewrite_tags() {
		add_rewrite_tag('%'.self::QUERY_KEY_FREETEXT.'%', '([^/]+)');
		add_rewrite_tag('%'.self::QUERY_KEY_PAGEINDEX.'%', '(\d+)');
	}

	/**
	 * Add rewrite rules to WordPress installation
	 */
	public function add_rewrite_rules() {
		if(get_option('wpchaos-searchpage')) {
			$searchPageID = intval(get_option('wpchaos-searchpage'));
			$searchPageName = get_page_uri($searchPageID);
			
			$regex = sprintf('%s/([^/]+)/?$', $searchPageName);
			$redirect = sprintf('index.php?pagename=%s&%s=$matches[1]', $searchPageName, self::QUERY_KEY_FREETEXT);
			add_rewrite_rule($regex, $redirect, 'top');
			
			$regex = sprintf('%s/([^/]+)/(\d+)/?$', $searchPageName);
			$redirect = sprintf('index.php?pagename=%s&%s=$matches[1]&%s=$matches[2]', $searchPageName, self::QUERY_KEY_FREETEXT, self::QUERY_KEY_PAGEINDEX);
			add_rewrite_rule($regex, $redirect, 'top');
			
			flush_rewrite_rules(true);
		}
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
					echo '<div class="error"><p><strong>WordPress DKA Object</strong> needs <strong>'.implode('</strong>, </strong>',$dep).'</strong> to be activated.</p></div>';
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
		require_once('widget-search.php');
	}

}
//Instantiate
new WPChaosSearch();

//eol