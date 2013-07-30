<?php
/**
 * @package WP DKA
 * @version 1.0
 */
/*
Plugin Name: WordPress DKA
Plugin URI: 
Description: Module applying functionality to manipulate CHAOS material specific for DKA. Depends on WordPress Chaos Client.
Author: Joachim Jensen <joachim@opensourceshift.com>
Version: 1.0
Author URI: 
*/

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPDKA {

	/**
	 * Name for setting page
	 * @var string
	 */
	protected $menu_page = 'wpdka-administration';
	
	const CLEAN_UP_SLUGS_BTN = 'Clean up slugs';
	const CLEAN_OBJECT_SLUGS_AJAX = 'wp_dka_clean_object_slugs';

	//List of plugins depending on
	private static $plugin_dependencies = array(
		'wpchaosclient/wpchaosclient.php' => 'WordPress Chaos Client',
		'wpchaossearch/wpchaossearch.php' => 'WordPress Chaos Search'
	);

	/**
	 * Construct
	 */
	public function __construct() {
		if(self::check_chaosclient()) {

			$this->load_dependencies();

			add_action('admin_menu', array(&$this, 'create_menu'));
			
			add_action('wp_ajax_' . self::CLEAN_OBJECT_SLUGS_AJAX, array(&$this, 'ajax_clean_object_slugs'));

		}

	}
	
	public static function install() {
		if(self::check_chaosclient()) {
			WPChaosSearch::flush_rewrite_rules_soon();
		}
	}
	
	public static function uninstall() {
		if(self::check_chaosclient()) {
			WPChaosSearch::flush_rewrite_rules_soon();
		}
	}

	/**
	 * Create submenu and call page for settings
	 * @return void 
	 */
	public function create_menu() {
		add_menu_page(
			'Dansk Kulturarv',
			'DKA',
			'manage_options',
			$this->menu_page,
			array(&$this, 'create_menu_page'),
			'none',
			81
		); 
	}

	/**
	 * Create page for settings
	 * @return void
	 */
	public function create_menu_page() {
		echo '<div class="wrap"><h2>'.get_admin_page_title().'</h2>'."\n";
		/*
		if(array_key_exists('action', $_GET) && $_GET['action'] == self::CLEAN_UP_SLUGS_BTN) {
			echo '<div class="updated">';
			$this->clean_up_slugs();
			echo '</div>';
		}
		*/
		//Add section to WordPress
		echo '<h3>Dansk Kulturarv CHAOS Object slugs</h3>';
		echo '<p>These are the nice URLs every object gets when clicked in a search url. If the CHAOS indexing for some reason fails, multiple objects might be given the same slug, this is why it might be nessesary to clean these up.</p>';
		//echo '<form method="get">';
		//echo '<input type="hidden" name="page" value="'. esc_attr($_GET['page']) .'"/>';
		//echo '<input type="submit" name="action" id="submit" class="button button-primary" value="'. self::CLEAN_UP_SLUGS_BTN .'" />';
		//echo '</form>';
		?>
		<style type='text/css'>
		#clean-slugs-button, #progress-objects { float:left; }
		.media-item .progress { position:relative; float:left; margin: 0px 10px; }
		.media-item .progress .state { display:block; position:absolute; top:0px; right:7px; color: rgba(0, 0, 0, 0.6); padding: 0 8px; text-shadow: 0 1px 0 rgba(255, 255, 255, 0.4); z-index: 10; }
		#ajax-messages {clear:both;}
		</style>
		<button class="button button-primary" id="clean-slugs-button"><?php echo self::CLEAN_UP_SLUGS_BTN ?></button>
		<div class='media-item' id='progress-objects' style='display:none;'>
			<div class='progress'><div class='percent'>0%</div><div class='state'><span class='d'>0</span> of <span class='t'>?</span> objects</div><div class='bar'></div></div>
		</div>
		<pre id="ajax-messages"></pre>
		<script>
		jQuery(document).ready(function($) {
			function clean_object_metadata(data) {
				data['action'] = "<?php echo WPDKA::CLEAN_OBJECT_SLUGS_AJAX ?>";
				// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php
				$.post(ajaxurl, data, function(response) {
					var objectsProcessed = (response.pageIndex+1)*response.pageSize;
					var progressProcentage = Math.round(objectsProcessed * 100.0 / response.totalCount);
					$("#progress-objects")
						.find(".percent").text(progressProcentage+"%").end()
						.find(".state .d").text(objectsProcessed).end()
						.find(".state .t").text(response.totalCount).end()
						.find(".bar").css("width", progressProcentage+"%").end()
					.fadeIn();
					// Get the next.
					data['pageIndex'] = response.nextPageIndex;
					for(var m = 0; m < response.messages.length; m++) {
						$("#ajax-messages").append(response.messages[m]+"\n");
					}
					clean_object_metadata(data);
				}, 'json');
			}
			
			$("#clean-slugs-button").click(function() {
				var data = { pageSize: 49 };
				clean_object_metadata(data);
			});
		});
		</script>
		<?php
	}
	
	/*
	public function clean_up_slugs() {
		// We are cleaning up slugs ..
		echo "<h3>Cleaning up slugs</h3>";
		// Ask the index for all unique slugs.
		$response = WPChaosClient::instance()->Index()->Search('field:DKA-Crowd-Slug_string');
		foreach($response->Index()->Results() as $facetResults) {
			foreach($facetResults->FacetFieldsResult as $facetResult) {
				foreach($facetResult->Facets as $facet) {
					if($facet->Count > 1) {
						echo '<hr />';
						echo '<p><strong>' . $facet->Value . '</strong> has '.$facet->Count.' objects associated with it.</p>';
						$objectsResponse = WPChaosClient::instance()->Object()->Get('DKA-Crowd-Slug_string:' . $facet->Value, null, null, 0, $facet->Count, true);
						$objects = WPChaosObject::parseResponse($objectsResponse);
						echo '<ul>';
						foreach($objects as $object) {
							$metadataXML = WPDKAObject::reset_crowd_metadata($object);
							var_dump($metadataXML);
							echo '<li>' . $object->GUID . '</li>';
						}
						echo '<p>';
					}
				}
			}
		}
	}
	*/
	
	public function ajax_clean_object_slugs () {
		$result = array();
		$result['messages'] = array();
		
		if(!array_key_exists('pageSize', $_POST)) {
			status_header(500);
			echo "pageSize must be specified.";
			die();
		} else {
			$result['pageSize'] = intval($_POST['pageSize']);
		}
		
		// Calculate the pageIndex, defaults to 0.
		$result['pageIndex'] = array_key_exists('pageIndex', $_POST) ? intval($_POST['pageIndex']) : 0;
		
		// Ask chaos for all interesting objects.
		$query = apply_filters('wpchaos-solr-query', '', array());
		$response = WPChaosClient::instance()->Object()->Get($query, "GUID+asc", null, $result['pageIndex'], $result['pageSize'], true);
		$result['totalCount'] = $response->MCM()->TotalCount();
		
		// Process the objects
		foreach($response->MCM()->Results() as $object) {
			// Ensure its crowd metadata.
			// Make sure the object is reachable on its slug - if not, reset its metadata.
			// $result['messages'][] = "";
		}
		
		$result['nextPageIndex'] = $result['pageIndex'] + 1;
		echo json_encode($result);
		die();
	}
	
	/**
	 * Check if dependent plugins are active
	 * 
	 * @return void 
	 */
	public static function check_chaosclient() {
		//$plugin = plugin_basename( __FILE__ );
		$dep = array();
		//if(is_plugin_active($plugin)) {
			foreach(self::$plugin_dependencies as $class => $name) {
				if(!in_array($class, get_option('active_plugins'))) {
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
	 * Load files and libraries
	 * @return void 
	 */
	private function load_dependencies() {
		require('wpdkaobject.php');
		require('wpdkasearch.php');
		require('widgets/player.php');
	}

}

register_activation_hook(__FILE__, array('WPDKA', 'install'));
register_deactivation_hook(__FILE__, array('WPDKA', 'uninstall'));

//Instantiate
new WPDKA();

//eol