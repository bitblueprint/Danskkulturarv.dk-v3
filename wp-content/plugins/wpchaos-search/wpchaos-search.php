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

class WPChaosSearch {

	const QUERY_KEY_FREETEXT = 'cq';
	const QUERY_KEY_PAGEINDEX = 'i';
	public $plugin_dependencies = array(
		'WPChaosClient' => 'WordPress Chaos Client',
	);

	/**
	 * Construct
	 */
	public function __construct() {

		$this->load_dependencies();

		add_action('admin_init',array(&$this,'check_chaosclient'));
		add_action('widgets_init', array(&$this,'register_widgets'));
		add_action('template_redirect', array(&$this,'get_material_page'));

		add_filter('wpchaos-config',array(&$this,'settings'));

		add_shortcode( 'chaosresults', array(&$this,'shortcode_searchresults'));

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

	public function get_material_page() {
	//index.php?&org=1&slug=2 => /org/slug/
	//org&guid
		if(isset($_GET['guid'])) {

			//do some chaos here
			//
			$serviceResult = WPChaosClient::instance()->Object()->Get(
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
				$object = $serviceResult->MCM()->Results()[0];
				WPChaosClient::set_object($object);
				$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");
			}

			//Look in theme dir and include if found
			if(locate_template('chaos-object-page.php', true) != "") {
			
			//Include from plugin
			} else {
				include(plugin_dir_path(__FILE__)."/templates/object-page.php");
			}
			exit();
		}

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
	 * @return [type]       
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

	public function get_searchresults($args) {

		if(isset($_GET[self::QUERY_KEY_PAGEINDEX])) {
			$args['pageindex'] = (int)$_GET[self::QUERY_KEY_PAGEINDEX];
			$args['pageindex'] = ($args['pageindex'] >= 0?$args['pageindex']:0);
		}

		$query = apply_filters('wpchaos-solr-query', $args['query'], $_GET);
		//$query = $args['query'];
		
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

	public static function create_search_form($placeholder = "") {
		if(get_option('wpchaos-searchpage')) {
			$page = get_permalink(get_option('wpchaos-searchpage'));
		} else {
			$page = "";
		}

		$text = esc_attr(isset($_GET[self::QUERY_KEY_FREETEXT])?$_GET[self::QUERY_KEY_FREETEXT]:'');
		
		echo '<form method="GET" action="'.$page.'">'."\n";

		echo '<div class="input-append">'."\n";
		echo '<input class="span7" id="appendedInputButton" type="text" name="'.self::QUERY_KEY_FREETEXT.'" value="'.$text.'" placeholder="'.$placeholder.'" /><button type="submit" class="btn btn-large btn-search">Søg</button>'."\n";
		echo '</div>'."\n";

		echo '<div class="btn-group pull-right span4">'."\n";
		echo '<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning</button>'."\n";
		echo '</div>'."\n";

		echo '</form>'."\n";
	}

	/**
	 * Check if dependent plugin is active
	 * 
	 * @return void 
	 */
	public function check_chaosclient() {

		$plugin = plugin_basename( __FILE__ );
		$dep = array();
		if(is_plugin_active($plugin)) {
			foreach($this->plugin_dependencies as $class => $name) {
				if(!class_exists($class)) {
					$dep[] = $name;
				}	
			}
			if(!empty($dep)) {
				deactivate_plugins(array($plugin));
				add_action( 'admin_notices', function() use (&$dep) { $this->deactivate_notice($dep); },10);
			}
		}
	}

	/**
	 * Render admin notice when dependent plugin is inactive
	 * 
	 * @return void 
	 */
	public function deactivate_notice($classes) {
		echo '<div class="error"><p>WordPress Chaos Search needs '.implode(',',(array)$classes).' to be activated.</p></div>';
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
