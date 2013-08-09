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
	const QUERY_KEY_PAGE = 'side';
	const QUERY_KEY_VIEW = 'som';
	const QUERY_KEY_SORT = 'sorteret-efter';
	
	const QUERY_PREFIX_CHAR = '-';
	const QUERY_DEFAULT_POST_SEPERATOR = '-';
	
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

			if(is_admin()) {

				add_action('admin_init', array(&$this, 'check_chaosclient'));
				// Add rewrite rules when activating and when settings update.
				add_action('chaos-settings-updated', array('WPChaosSearch', 'flush_rewrite_rules_soon'));

				add_filter('wpchaos-config', array(&$this, 'settings'));


			}

			add_action('plugins_loaded',array(&$this,'load_textdomain'));
			add_action('widgets_init', array(&$this, 'register_widgets'));
			add_action('template_redirect', array(&$this, 'get_search_page'));

			WPChaosSearch::register_search_query_variable(1, WPChaosSearch::QUERY_KEY_FREETEXT, '[^/&]*?', false, null, '', '/');
			WPChaosSearch::register_search_query_variable(4, WPChaosSearch::QUERY_KEY_VIEW, '[^/&]+?', true);
			WPChaosSearch::register_search_query_variable(5, WPChaosSearch::QUERY_KEY_SORT, '[^/&]+?', true);
			WPChaosSearch::register_search_query_variable(6, WPChaosSearch::QUERY_KEY_PAGE, '\d+?', true);
			
			// Rewrite tags and rules should always be added.
			add_action('init', array('WPChaosSearch', 'handle_rewrite_rules'));
			
			add_shortcode( 'chaos-random-tags', array( &$this, 'random_tags_shortcode' ) );
			// Add some custom rewrite rules.
			// This is implemented as a PHP redirect instead.
			// add_filter('mod_rewrite_rules', array(&$this, 'custom_mod_rewrite_rules'));
			
		}

	}

	public function load_textdomain() {
		load_plugin_textdomain( 'wpchaossearch', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/');
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
			'title'		=> __('Search Settings','wpchaossearch'),
			'fields'	=> array(
				/*Section fields*/
				array(
					'name' => 'wpchaos-searchpage',
					'title' => __('Page for search results','wpchaossearch'),
					'type' => 'select',
					'list' => $pages,
					'precond' => array(array(
						'cond' => (get_option('permalink_structure') != ''),
						'message' => __('Permalinks must be enabled for CHAOS search to work properly','wpchaossearch')
					))
				),
				array(
					'name' => 'wpchaos-searchsize',
					'title' => __('Results per page','wpchaossearch'),
					'type' => 'text',
					'val' => 20,
					'class' => 'small-text'
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
			if($variable['default_value'] !== null && empty($variables[$variable['key']])) {
				$variables[$variable['key']] = $variable['default_value'];
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
				$escape = explode(',', $escape);
				$result = $query_vars[$query_key];
				foreach($escape as $e) {
					if(function_exists($e)) {
						$result = $e($result);
					} else {
						throw new InvalidArgumentException('The $escape argument must be false or a 1-argument function.');
					}
				}
				return $result;
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
			//Change GET params to nice url
			$this->search_query_prettify();
			$this->generate_searchresults();

			//Get current page number
			$page = WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_PAGE)?:1;
			//Get objects per page
			$objects = get_option("wpchaos-searchsize")?:20;
			//Get max page number
			$max_page = ceil(WPChaosSearch::get_search_results()->MCM()->TotalCount()/$objects);

			//set title and meta
			global $wp_query;
			$wp_query->queried_object->post_title = sprintf(__('%s about %s','wpchaossearch'),get_bloginfo('title'),WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'));

			add_filter('wpchaos-head-meta',function($metadatas) use($wp_query) {
				$metadatas['og:title']['content'] = $wp_query->queried_object->post_title;
				return $metadatas;
			});

			//Remove meta and add a dynamic ones for better seo
			remove_action('wp_head', 'rel_canonical');
			remove_action('wp_head', 'adjacent_posts_rel_link_wp_head', 10,0);

			add_action('wp_head', function() {
				$link = WPChaosSearch::generate_pretty_search_url(array(
					WPChaosSearch::QUERY_KEY_VIEW => null,
				));
				echo '<link rel="canonical" href="'.$link.'" />'."\n";
			});

			add_action('wp_head', function() use($page) {
				$link = WPChaosSearch::generate_pretty_search_url(array(
					WPChaosSearch::QUERY_KEY_PAGE => null,
				));
				echo '<link rel="start" href="'.$link.'" />'."\n";
			});

			if($page > 1) {
				add_action('wp_head', function() use($page) {
					$link = WPChaosSearch::generate_pretty_search_url(array(
						WPChaosSearch::QUERY_KEY_PAGE => ($page-1 != 1 ? $page-1 : null),
					));
					echo '<link rel="prev" href="'.$link.'" />'."\n";
				});
			}

			if($page < $max_page) {
				add_action('wp_head', function() use($page) {
					$link = WPChaosSearch::generate_pretty_search_url(array(
						WPChaosSearch::QUERY_KEY_PAGE => $page+1,
					));
					echo '<link rel="next" href="'.$link.'" />'."\n";
				});
			}

			//Look in theme dir and include if found
			$include = locate_template('templates/chaos-search-results.php', false);
			if($include == "") {
				//Include from plugin template	
				$include = plugin_dir_path(__FILE__)."/templates/search-results.php";
			}
			require($include);
			exit();
		}
	}

	/**
	 * Generate data and include template for search results
	 * @param  array $args 
	 * @return string The markup generated.
	 */
	public function generate_searchresults($args = array()) {
		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'query' => "",
			'pageindex' => self::get_search_var(self::QUERY_KEY_PAGE, 'intval')-1,
			'pagesize' => get_option("wpchaos-searchsize"),
			'sort' => self::get_search_var(self::QUERY_KEY_SORT),
			'accesspoint' => null
		));
		extract($args, EXTR_SKIP);	

		$pagesize = ($pagesize?:20);
		$pageindex = ($pageindex >= 0?$pageindex:0);

		$sort = apply_filters('wpchaos-solr-sort', $sort, self::get_search_vars());
		$query = apply_filters('wpchaos-solr-query', $query, self::get_search_vars());
		
		self::set_search_results(WPChaosClient::instance()->Object()->Get(
			$query,	// Search query
			$sort,	// Sort
			$accesspoint,	// AccessPoint given by settings.
			$pageindex,		// pageIndex
			$pagesize,		// pageSize
			true,	// includeMetadata
			true,	// includeFiles
			true	// includeObjectRelations
		));
	}
	
	public static function generate_facet($facet_field, $exclude_query_var = null) {
		$variables = self::get_search_vars();
		if($exclude_query_var) {
			unset($variables[$exclude_query_var]);
		}
		$query = apply_filters('wpchaos-solr-query', "", $variables);
		$response = WPChaosClient::instance()->Index()->Search("field:" . $facet_field, $query);
		$results = $response->Index()->Results();
		$facets = $results[0]->FacetFieldsResult[0]->Facets;
		// Process the result from CHAOS.
		$result = array();
		foreach($facets as $facet) {
			$result[$facet->Value] = $facet->Count;
		}
		return $result;
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
		
		$include = locate_template('templates/chaos-search-form.php', false);
		if($include == "") {
			//Include from plugin template		
			$include = plugin_dir_path(__FILE__)."/templates/search-form.php";
		}
		require($include);
	}

	public function get_random_tags_from_results($args) {
		$args = wp_parse_args($args, array(
			'query' => '',
			'number_of_tags' => 10,
			'pageindex' => 0,
			'pagesize' => get_option("wpchaos-searchsize"),
			'sort' => 'visninger',
			'accesspoint' => null,
			'class' => 'tag',
			'seperator' => ', ',
			'last_seperator' => ' and ',
		));
		extract($args, EXTR_SKIP);	

		$this->generate_searchresults($args);
		$tags = array();
		foreach(WPChaosSearch::get_search_results()->MCM()->Results() as $object) {
			WPChaosClient::set_object($object);
			$tags = array_merge($tags,WPChaosClient::get_object()->tags_raw);

		}
		WPChaosClient::reset_object();

		$sep = '';
		$result = '';
		while($number_of_tags > 0 && $tags) {
			$tag = array_splice($tags, rand(0,count($tags)-1), 1);
			$tag = $tag[0];

			$link = WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_FREETEXT => $tag, WPChaosSearch::QUERY_KEY_SORT => $sort));
			$result .= $sep."\n".'<a class="'.$class.'" href="'.$link.'" title="'.esc_attr($tag).'">'.$tag.'</a>';
			$number_of_tags--;
			if($last_seperator && ($number_of_tags == 1 || count($tags) == 1)) {
				$sep = $last_seperator;
			} else {
				$sep = $seperator;
			}
		}
		return $result;

	}

	public function random_tags_shortcode($atts) {
		return $this->get_random_tags_from_results(shortcode_atts( array(
			'query' => '',
			'number_of_tags' => 10,
			'pageindex' => 0,
			'pagesize' => get_option("wpchaos-searchsize"),
			'sort' => 'visninger',
			'accesspoint' => null,
			'class' => 'tag',
			'seperator' => ', ',
			'last_seperator' => ' and ',
		), $atts ));
	}
	
	public static $search_query_variables = array();
	
	public static function register_search_query_variable($position, $key, $regexp, $prefix_key = false, $multivalue_seperator = null, $default_value = null, $post_seperator = self::QUERY_DEFAULT_POST_SEPERATOR) {
		self::$search_query_variables[$position] = array(
			'key' => $key,
			'regexp' => $regexp,
			'prefix-key' => $prefix_key,
			'multivalue-seperator' => $multivalue_seperator,
			'default_value' => $default_value,
			'post-seperator' => $post_seperator
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
			
			$regex = $searchPageName . '/';
			foreach(self::$search_query_variables as $variable) {
				// An optional non-capturing group wrapped around the $regexp.
				if($variable['prefix-key'] == true) {
					$regex .= sprintf('(?:%s(%s)%s?)?', $variable['key'].self::QUERY_PREFIX_CHAR, $variable['regexp'], $variable['post-seperator']);
				} else {
					$regex .= sprintf('(?:(%s)%s?)?', $variable['regexp'], $variable['post-seperator']);
				}
			}
			$regex .= '$';
			
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
		$result = get_page_uri(get_option('wpchaos-searchpage')) . '/';
		$last_post_seperator = '';
		foreach(self::$search_query_variables as $variable) {
			if(!array_key_exists($variable['key'], $variables)) {
				$variables[$variable['key']] = "";
			}
			$value = $variables[$variable['key']];
			if(empty($value) && $variable['default_value'] != null) {
				$value = $variable['default_value'];
			}
			if($value) {
				if(is_array($value)) {
					$value = implode($variable['multivalue-seperator'], $value);
				}
				$value = urlencode($value);
				if($variable['prefix-key']) {
					$result .= $variable['key'] . self::QUERY_PREFIX_CHAR . $value . $variable['post-seperator'];
				} else {
					$result .= $value . $variable['post-seperator'];
				}
			}
			$last_variable = $variable;
		}
		if(substr($result, -1) === $last_variable['post-seperator']) {
			$result = substr($result, 0, strlen($result)-1)."/";
		}
		// Fixing postfix issues, removing the last post-seperator.
		return site_url($result);
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
					echo '<div class="updated"><p><strong>'.__('WordPress CHAOS Search','wpchaossearch').'</strong> '.__('Rewrite rules flushed ..','wpchaossearch').'</p></div>';
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
					echo '<div class="error"><p><strong>'.__('WordPress CHAOS Search','wpchaossearch').'</strong> '.sprintf(__('needs %s to be activated.','wpchaossearch'),'<strong>'.implode('</strong>, </strong>',$dep).'</strong>').'</p></div>';
				},10);
				return false;
			}
		//}
		return true;
	}

		/**
	 * Pagination for search results
	 * @param  array  $args Arguments can be passed for specific behaviour
	 * @return string       
	 */
	public static function paginate($args = array()) {
		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'before' 			=> '<ul>',
			'after' 			=> '</ul>',
			'before_link' 		=> '<li class="%s">',
			'after_link' 		=> '</li>',
			'link_class' 		=> '',
			'class_disabled' 	=> 'disabled',
			'class_active' 		=> 'active',
			'count' 			=> 3,
			'previous' 			=> '&laquo;',
			'next' 				=> '&raquo;',
			'echo' 				=> true
		));
		extract($args, EXTR_SKIP);
		
		//Get current page number
		$page = self::get_search_var(self::QUERY_KEY_PAGE)?:1;
		//Get objects per page
		$objects = get_option("wpchaos-searchsize")?:20;
		//Get max page number
		$max_page = ceil(self::get_search_results()->MCM()->TotalCount()/$objects);
		
		$result = $before;

		//Current page should optimally be in the center
		$start = $page-(ceil($count/2))+1;
		//When reaching the end, push start to the left such that current page is pushed to the right
		$start = min($start,($max_page+1)-$count);
		//Start can minimum be 1
		$start = max(1,$start);
		//Set end according to start
		$end = $start+$count;

		//Is prevous wanted
		if($previous) {
			$result .= self::paginate_page($before_link,$after_link,$page-1,$start,$max_page,$page,$link_class,$class_active,$class_disabled,$previous);
		}

		//Set enumeration
		for($i = $start; $i < $end; $i++) {
			$result .= self::paginate_page($before_link,$after_link,$i,$start,$max_page,$page,$link_class,$class_active,$class_disabled);
		}

		//Is next wanted
		if($next) {
			$result .= self::paginate_page($before_link,$after_link,$page+1,$start,$max_page,$page,$link_class,$class_active,$class_disabled,$next);
		}

		$result .= $after;

		//Is echo wanted automatically
		if($echo) {
			echo $result;
		}
		
		return $result;
	}

	/**
	 * Helper function for pagination.
	 * Sets the class, link and text for each element
	 * 
	 * @param  string $before_link 
	 * @param  string $after_link  
	 * @param  int $page        
	 * @param  int $min         
	 * @param  int $max         
	 * @param  int $current
	 * @param  string $link_class
	 * @param  string $class_active
	 * @param  string $class_disabled
	 * @param  string $title       
	 * @return string              
	 */
	public static function paginate_page($before_link,$after_link,$page,$min,$max,$current,$link_class,$class_active,$class_disabled,$title = "") {
		if(!$title) {
			$link_class = ' class="'.$link_class.'"';
		} else {
			$link_class = '';
		}
		if($page > $max || $page < $min) {
			$class = $class_disabled;
			$result = '<span'.$link_class.'>'.($title?:$page).'</span>';
		} else if(!$title && $page == $current) {
			$class = $class_active;
			$result = '<span>'.$page.'</span>';
		} else {
			$class = "";
			$result = '<a'.$link_class.' href="'. WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_PAGE => $page)) .'">'.($title?:$page).'</a>';
		}
		return sprintf($before_link,$class).$result.$after_link."\n";
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
