<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
/*
Plugin Name: WordPress Chaos Search
Plugin URI: 
Description: Module enabling search in CHAOS. Depends on WordPress Chaos Client.
Author: 
Version: 1.0
Author URI: 
*/

class WPChaosSearch {

	/**
	 * Construct
	 */
	public function __construct() {

		$this->load_dependencies();

		add_action('admin_init',array(&$this,'check_chaosclient'));
		add_action('widgets_init', array(&$this,'register_widgets'));

		add_filter('wpchaos-config',array(&$this,'settings'));

	}

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

	public function register_widgets() {
	    register_widget( 'WPChaos_Search_Widget' );
	}

	// public function my_page_template_redirect() {
	// index.php?&org=1&slug=2 => /org/slug/
	// /org&guid
	// 	if(get chaos) {
	// 	
	// 		include (TEMPLATEPATH . '/post-with-permalink-hello-world.php');
	// 		get_template_part
	// 		wp_redirect( home_url( '/search/' ) );
	// 		exit();
	// 	}
	// }


	public static function create_search_form($placeholder = "") {
		if(get_option('wpchaos-searchpage')) {
			$page = get_permalink(get_option('wpchaos-searchpage'));
		} else {
			$page = "";
		}
		
		echo '<form method="GET" action="'.$page.'">';
		echo '<input type="text" name="cq" value="'.$_GET['cq'].'" placeholder="'.$placeholder.'" /> <input type="submit" value="Search" />';
		echo '</form>';
	}

	/**
	 * Check if dependent plugin is active
	 * 
	 * @return void 
	 */
	public function check_chaosclient() {
		$plugin = plugin_basename( __FILE__ );
		if(is_plugin_active($plugin) && !class_exists("WPChaosClient")) {
			deactivate_plugins(array($plugin));
			add_action( 'admin_notices', array(&$this,'deactivate_notice'));
		}
	}

	/**
	 * Render admin notice when dependent plugin is inactive
	 * 
	 * @return void 
	 */
	public function deactivate_notice() {
		echo '<div class="error"><p>WordPress Chaos Search needs WordPress Chaos Client to be activated.</p></div>';
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