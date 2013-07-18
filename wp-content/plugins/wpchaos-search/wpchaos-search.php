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
			get_header();
			 echo"material page";
			 get_footer();
			//include (TEMPLATEPATH . '/post-with-permalink-hello-world.php');
			exit();
		}

		//Include template for search results
		if(get_option('wpchaos-searchpage') && is_page(get_option('wpchaos-searchpage'))) {
			//Look in theme dir and include if found
			if(locate_template('chaos-searchresults.php', true) != "") {
			
			//Include from plugin
			} else {
				include(plugin_dir_path(__FILE__)."/templates/searchresults.php");
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
			'query' => ""
		), $args );

		return $this->get_searchresults($args);
	}

	public function get_searchresults($args) {
		
		// C4C2B8DA-A980-11E1-814B-02CEA2621172
		$accessPointGUID = get_option("wpchaos-accesspoint-guid");

		if(isset($_GET[self::QUERY_KEY_PAGEINDEX])) {
			$pageindex = (int)$_GET[self::QUERY_KEY_PAGEINDEX];
			$pageindex = ($pageindex >= 0?$pageindex:0);
		} else {
			$pageindex = 0;
		}

		$query = apply_filters('wpchaos-solr-query', $args['query'], $_GET);
		
		$serviceResult = WPChaosClient::instance()->Object()->Get(
			$query,	// Search query
			null,	// Sort
			null,	// AccessPoint given by settings.
			$pageindex,		// pageIndex
			20,		// pageSize
			true,	// includeMetadata
			true,	// includeFiles
			true	// includeObjectRelations
		);
		
		$objects = $serviceResult->MCM()->Results();

		?>

		<article class="container search-results">
	    <div class="row">
		    <div class="span6">
		    <p>Søgningen på <strong class="blue"><?php echo esc_html($_GET[self::QUERY_KEY_FREETEXT]); ?></strong> gav <?php echo $serviceResult->MCM()->TotalCount(); ?> resultater</p>
		    </div>
		    <div class="span1 pull-right">
	        <a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $pageindex+1); ?>">Næste ></a>
	      </div>
	      <div class="span1 pull-right">
	        <a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $pageindex-1); ?>">< Forrige</a>
	      </div>
	    </div>
	    <ul class="row thumbnails">

		<?php

		/*
		
		 <img src="img/turell.jpg" alt="">
          
          <span class="series"></span><span class="views">19</span><span class="likes">3</span>

		 */

		foreach($objects as $object) :
			$test_object = new WPChaosObject($object);
			
			$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");

			?>

			<li class="search-object span3">
				<a class="thumbnail" href="<?php echo $link; ?>">
					<h2 class="title"><strong><?php echo $test_object->title; ?></strong></h2>
					<div class="organization"><strong class="strong orange"><?php echo $test_object->organization; ?></strong></div>
					<p class="date"><?php echo $test_object->published; ?></p>
					<hr>
					<span class="<?php echo $test_object->type; ?>"></span>
				</a>
			</li>

			<?php

		endforeach;

		?>

		</ul>
		</article>

		<?php

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
