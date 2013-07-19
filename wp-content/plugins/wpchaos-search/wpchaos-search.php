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

	const QUERY_KEY_FREETEXT = 'text';
	const QUERY_KEY_PAGEINDEX = 'pageIndex';
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
		
		// Add rewrite rules when activating and when settings update.
		register_activation_hook(__FILE__, array(&$this, 'add_rewrite_rules'));
		add_action('chaos-settings-updated', array(&$this, 'add_rewrite_rules'));
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
	
	public static function get_search_vars() {
		global $wp_query;
		return array_merge(array(), $_GET, $wp_query->query_vars);
	}
	
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

	public function get_material_page() {
	//index.php?&org=1&slug=2 => /org/slug/
	//org&guid
		if(isset($_GET['guid'])) {

			//do some chaos here

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
			if(locate_template('chaos-searchresults.php', true) != "") {
			
			//Include from plugin
			} else {
				include(plugin_dir_path(__FILE__)."/templates/search-results.php");
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

		?>

		<article class="container search-results">
	    <div class="row">
		    <div class="span6">
		    <p>Søgningen <?php if(isset($_GET[self::QUERY_KEY_FREETEXT])):?>på <strong class="blue"><?php echo esc_html($_GET[self::QUERY_KEY_FREETEXT]); ?></strong><?php endif;?> gav <?php echo $serviceResult->MCM()->TotalCount(); ?> resultater</p>
		    </div>
		    <div class="span1 pull-right">
	        <a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $args['pageindex']+1); ?>">Næste ></a>
	      </div>
	      <div class="span1 pull-right">
	        <a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $args['pageindex']-1); ?>">< Forrige</a>
	      </div>
	    </div>
	    <ul class="row thumbnails">

		<?php

		/*
		
		 <img src="img/turell.jpg" alt="">
          
          <span class="series"></span><span class="views">19</span><span class="likes">3</span>

		 */

		foreach($objects as $object) :

			//include template for each object

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
	
	public function add_rewrite_rules() {
		if(get_option('wpchaos-searchpage')) {
			$searchPageID = intval(get_option('wpchaos-searchpage'));
			$searchPageName = get_page_uri($searchPageID);
			
			add_rewrite_tag('%'.self::QUERY_KEY_FREETEXT.'%', '([^/]+)');
			add_rewrite_tag('%'.self::QUERY_KEY_PAGEINDEX.'%', '(\d+)');
			
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
