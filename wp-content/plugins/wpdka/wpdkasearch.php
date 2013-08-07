<?php
/**
 * @package WP DKA
 * @version 1.0
 */

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPDKASearch {
	
	const QUERY_KEY_TYPE = 'med';
	const QUERY_KEY_ORGANIZATION = 'fra';

	/**
	 * List of organizations from the WordPress site
	 * @var array
	 */
	public static $organizations = array();

	public static $sorts = array(
		null => array(
			'title' => 'Relevans',
			'link' => null,
			'chaos-value' => null
		),
		'titel' => array(
			'title' => 'Titel',
			'link' => 'titel',
			'chaos-value' => 'DKA2-Title_string'
		),
		'visninger' => array(
			'title' => 'Visninger',
			'link' => 'visninger',
			'chaos-value' => 'DKA-Crowd-Views_int+desc'
		),
	);

	/**
	 * Construct
	 */
	public function __construct() {

		add_action('template_redirect',array(&$this,'set_search_title'),0);

		WPChaosSearch::register_search_query_variable(2, WPDKASearch::QUERY_KEY_ORGANIZATION, '[\w-]+?', true, '-');
		WPChaosSearch::register_search_query_variable(3, WPDKASearch::QUERY_KEY_TYPE, '[\w-]+?', true, '-');
		
		// Define the free-text search filter.
		$this->define_search_filters();
		add_filter('wpchaos-solr-sort',array(&$this,'map_chaos_sorting'),10,2);
		
	}

	/**
	 * Set title and meta nodes for search results
	 */
	public function set_search_title() {
		
		if(get_option('wpchaos-searchpage') && is_page(get_option('wpchaos-searchpage'))) {
			global $wp_query;
			$wp_query->queried_object->post_title = get_bloginfo('title')." om ".WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html');

			//Alter some meta nodes to show information about the current search
			add_filter('wpchaos-head-meta',function($metadatas) {

				$extra_description = '';

				// Fetch titles from the organizations searched in
				if(WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_ORGANIZATION)) {
					$organizations = WPDKASearch::get_organizations();
					$temp = array();
					foreach($organizations as $organization) {
						if(in_array($organization['slug'],WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_ORGANIZATION))) {
							$temp[] = $organization['title'];
						}
					}

					if($temp) {
						$extra_description .= ' De fremsøgte materialer er fra '.preg_replace('/(.*),/','$1 og',implode(", ", $temp)).'.';
					}
					
					unset($temp);
				}
				
				//Fetch the titles from the formats searched in
				if(WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_TYPE)) {
					$temp = array();
					foreach(WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_TYPE) as $format) {
						if(isset(WPDKAObject::$format_types[$format])) {
							$temp[] = strtolower(WPDKAObject::$format_types[$format]['title']);
						}
					}
					if($temp) {
						$extra_description .= ' Formatet er '.preg_replace('/(.*),/','$1 og',implode(", ", $temp)).'.';
					}
					
					unset($temp);
					
				}

				$metadatas['og:title']['content'] = get_bloginfo('title')." om ".WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html');
				$metadatas['description']['content'] = $metadatas['og:description']['content'] = get_bloginfo('title').' indeholder '.WPChaosSearch::get_search_results()->MCM()->TotalCount().
				' materialer om "'.WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html').
				'".'.$extra_description;

				return $metadatas;
			});
		}
	}

	/**
	 * Convert search parameters to SOLR query
	 * @return string 
	 */
	public function define_search_filters() {
		
		// Free text search.
		add_filter('wpchaos-solr-query', function($query, $query_vars) {
			if($query) {
				$query = array($query);
			} else {
				$query = array();
			}
				
			if(array_key_exists(WPChaosSearch::QUERY_KEY_FREETEXT, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$freetext = $query_vars[WPChaosSearch::QUERY_KEY_FREETEXT];
				if(empty($freetext)) {
					$freetext = ' ';
				}
				$freetext = WPChaosClient::escapeSolrValue($freetext);
				$searches = array();
				foreach(WPDKAObject::$ALL_SCHEMA_GUIDS as $schemaGUID) {
					$searches[] = sprintf("(m%s_%s_all:(%s))", $schemaGUID, WPDKAObject::METADATA_LANGUAGE, $freetext);
				}
				$query[] = '(' . implode("+OR+", $searches) . ')';
			}
				
			return implode("+AND+", $query);
		}, 10, 2);
		
		// File format types
		add_filter('wpchaos-solr-query', function($query, $query_vars) {
			if($query) {
				$query = array($query);
			} else {
				$query = array();
			}
				
			if(array_key_exists(WPDKASearch::QUERY_KEY_TYPE, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$types = $query_vars[WPDKASearch::QUERY_KEY_TYPE];
				$searches = array();
				foreach($types as $type) {
					if(isset(WPDKAObject::$format_types[$type])) {
						$searches[] = "(FormatTypeName:".WPDKAObject::$format_types[$type]['chaos-value'].")";
					}
				}
				if(count($searches) > 0) {
					$query[] = '(' . implode("+OR+", $searches) . ')';
				}
			}
				
			return implode("+AND+", $query);
		}, 11, 2);
		
		// Organizations
		add_filter('wpchaos-solr-query', function($query, $query_vars) {
			if($query) {
				$query = array($query);
			} else {
				$query = array();
			}
				
			if(array_key_exists(WPDKASearch::QUERY_KEY_ORGANIZATION, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$organizationSlugs = $query_vars[WPDKASearch::QUERY_KEY_ORGANIZATION];
				$organizations = WPDKASearch::get_organizations();
				$searches = array();
				foreach($organizationSlugs as $organizationSlug) {
					foreach($organizations as $title => $organization) {
						if($organization['slug'] == $organizationSlug) {
							$searches[] = "(DKA-Organization:\"$title\")";
						}
					}
				}
				if(count($searches) > 0) {
					$query[] = '(' . implode("+OR+", $searches) . ')';
				}
			}
				
			return implode("+AND+", $query);
		}, 11, 2);
	}

	public function map_chaos_sorting($sort,$query_vars) {
		return (isset(WPDKASearch::$sorts[$sort]) ? WPDKASearch::$sorts[$sort]['chaos-value'] : null);
	}

	/**
	 * Fetch organization title and slug from pages using the "chaos_organization" custom field
	 * That custom field value should correspond to the title given in CHAOS
	 * @return array
	 */
	public static function get_organizations() {
		if(empty(self::$organizations)) {
			$posts = new WP_Query(array(
				'meta_key' => 'chaos_organization',
				'post_type' => 'page',
				'post_status' => 'publish,private,future',
				'orderby' => 'title',
				'order' => 'ASC'
			));
			foreach($posts->posts as $post) {
				self::$organizations[$post->chaos_organization] = array(
					'title' => $post->post_title,
					'slug' => $post->post_name,
					'id' => $post->ID
				);
			} 
		}	
		return self::$organizations;
	}

}

//Instantiate
new WPDKASearch();

//eol