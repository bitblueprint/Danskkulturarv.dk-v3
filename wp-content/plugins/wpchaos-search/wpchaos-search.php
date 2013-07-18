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
					'list' => $pages
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
			get_header();
			 echo"material page";
			 get_footer();
			//include (TEMPLATEPATH . '/post-with-permalink-hello-world.php');
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
			'foo' => 'something',
			'bar' => 'something else',
			'query' => $_GET
		), $args );

		return $this->get_searchresults($args);
	}

	public function get_searchresults($args) {
		$fields = array(
		  "5906a41b-feae-48db-bfb7-714b3e105396",
		  "00000000-0000-0000-0000-000063c30000",
		  "00000000-0000-0000-0000-000065c30000"
		);

		//$query = apply_filters('solr-query',$args['query'] ...);

		$serviceResult = WPChaosClient::instance()->Object()->GetSearchSchemas(
		  $args['query'][self::QUERY_KEY_FREETEXT],       // search string
		  $fields,      // fields to search
		  "da",         // language code
		  $accessPointGUID,
		  0,            // pageIndex
		  20,           // pageSize
		  true,         // includeMetadata
		  true,         // includeFiles
		  true          // includeObjectRelations
		);
		echo "Got " . $serviceResult->MCM()->Count() . "/" . $serviceResult->MCM()->TotalCount();

		$objects = $serviceResult->MCM()->Results();

		foreach($objects as $object) {
			$test_object = new WPChaosObject($object);
			echo $test_object->variousshit;
			//var_dump($test_object);
			$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");
			echo '<p><a href="'.$link.'">'.$object->GUID.'</a></p><br />';
		}

	}

	public static function create_search_form($placeholder = "") {
		if(get_option('wpchaos-searchpage')) {
			$page = get_permalink(get_option('wpchaos-searchpage'));
		} else {
			$page = "";
		}
		
		echo '<form method="GET" action="'.$page.'">'."\n";

		echo '<div class="input-append">'."\n";
		echo '<input class="span7" id="appendedInputButton" type="text" name="'.self::QUERY_KEY_FREETEXT.'" value="'.$_GET[self::QUERY_KEY_FREETEXT].'" placeholder="'.$placeholder.'" /> <button type="submit" class="btn btn-large btn-search">Søg</button>'."\n";
		echo '</div>'."\n";

		echo '<div class="btn-group pull-right span4">'."\n";
		echo '<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed" type="btn" data-toggle="collapse" href="#advanced-search-container">Præciser søgning<i></i></button>'."\n";
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
