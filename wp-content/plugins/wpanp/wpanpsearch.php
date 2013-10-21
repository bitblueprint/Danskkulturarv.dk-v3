<?php
/**
 * @package WP ANP
 * @version 1.0
 */

/**
 * Class that manages CHAOS data specific to
 * Dansk Kulturarv and registers attributes
 * for WPChaosObject
 */
class WPANPSearch {
	
	const QUERY_KEY_TYPE = 'with';
	const QUERY_KEY_ORGANIZATION = 'from';

	/**
	 * List of organizations from the WordPress site
	 * @var array
	 */
	public static $organizations = array();

	public static $sorts = array(
		null => array(
			'title' => 'Default',
			'link' => null,
			'chaos-value' => null
		),
		'titel' => array(
			'title' => 'Title',
			'link' => 'title',
			'chaos-value' => 'FIATIFTA-ANP-Title+asc'
		),
		'udgivelse' => array(
			'title' => 'Published',
			'link' => 'published',
			'chaos-value' => 'FIATIFTA-ANP-FirstPublicationDate+asc'
		),
	);

	/**
	 * Construct
	 */
	public function __construct() {
		
		add_filter('wpchaos-head-meta',array(&$this,'set_search_meta'),99);

		WPChaosSearch::register_search_query_variable(2, WPANPSearch::QUERY_KEY_ORGANIZATION, '[\w-]+?', true, '-');
		WPChaosSearch::register_search_query_variable(3, WPANPSearch::QUERY_KEY_TYPE, '[\w-]+?', true, '-');
		
		// Define the free-text search filter.
		$this->define_search_filters();
		add_filter('wpchaos-solr-sort',array(&$this,'map_chaos_sorting'),10,2);
		
	}

	/**
	 * Set title and meta nodes for search results
	 */
	public function set_search_meta($metadatas) {
		
		if(get_option('wpchaos-searchpage') && is_page(get_option('wpchaos-searchpage'))) {

			$extra_description = '';

			// Fetch titles from the organizations searched in
			if(WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_ORGANIZATION)) {
				$organizations = WPANPSearch::get_organizations();
				$temp = array();
				foreach($organizations as $organization) {
					if(in_array($organization['slug'],WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_ORGANIZATION))) {
						$temp[] = $organization['title'];
					}
				}
				if($temp) {
					$extra_description .= sprintf(__(' The material is from %s.','wpanp'),preg_replace('/(.*),/','$1 '.__('and','wpanp'),implode(", ", $temp)));
				}

				unset($temp);
			}

			//Fetch the titles from the formats searched in
			if(WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_TYPE)) {
				$temp = array();
				foreach(WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_TYPE) as $format) {
					if(isset(WPANPObject::$format_types[$format])) {
						$temp[] = strtolower(WPANPObject::$format_types[$format]['title']);
					}
				}
				if($temp) {
					$extra_description .= sprintf(__(' The format is %s.','wpanp'),preg_replace('/(.*),/','$1 '.__('and','wpanp'),implode(", ", $temp)));
				}

				unset($temp);

			}

			$metadatas['description']['content'] = 
			$metadatas['og:description']['content'] = sprintf(__('%s contains %s materials about %s.','wpanp'),get_bloginfo('title'),WPChaosSearch::get_search_results()->MCM()->TotalCount(),WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html')).$extra_description;	
			
		}
		return $metadatas;
	}

	/**
	 * Convert search parameters to SOLR query
	 * @return string 
	 */
	public function define_search_filters() {

		add_filter('wpchaos-solr-query', function($query, $query_vars) {
			if($query) {
				$query = array($query);
			} else {
				$query = array();
			}
				
			$objectTypeConstraints = array();
			foreach(WPANPObject::$OBJECT_TYPE_IDS as $id) {
				$objectTypeConstraints[] = "ObjectTypeID:$id";
			}
			$query[] = '(' . implode("+OR+", $objectTypeConstraints) . ')';
				
			return implode("+AND+", $query);
		}, 9, 2);
		
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
				foreach(WPANPObject::$FREETEXT_SCHEMA_GUIDS as $schemaGUID) {
					foreach(WPANPObject::$FREETEXT_LANGUAGE as $language) {
						$searches[] = sprintf("(m%s_%s_all:(%s))", $schemaGUID, $language, $freetext);
					}
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
				
			if(array_key_exists(WPANPSearch::QUERY_KEY_TYPE, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$types = $query_vars[WPANPSearch::QUERY_KEY_TYPE];
				$searches = array();
				foreach($types as $type) {
					if(isset(WPANPObject::$format_types[$type])) {
						$searches[] = "(FormatTypeName:".WPANPObject::$format_types[$type]['chaos-value'].")";
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
				
			if(array_key_exists(WPANPSearch::QUERY_KEY_ORGANIZATION, $query_vars)) {
				// For each known metadata schema, loop and add freetext search on this.
				$organizationSlugs = $query_vars[WPANPSearch::QUERY_KEY_ORGANIZATION];
				$organizations = WPANPSearch::get_organizations();
				$searches = array();
				foreach($organizationSlugs as $organizationSlug) {
					foreach($organizations as $title => $organization) {
						if($organization['slug'] == $organizationSlug) {
							$searches[] = "(FIATIFTA-ANP-Publisher:\"$title\")";
						}
					}
				}
				if(count($searches) > 0) {
					$query[] = '(' . implode("+OR+", $searches) . ')';
				}
			}
			return implode("+AND+", $query);
		}, 12, 2);
	}

	public function map_chaos_sorting($sort,$query_vars) {
		return (isset(WPANPSearch::$sorts[$sort]) ? WPANPSearch::$sorts[$sort]['chaos-value'] : null);
	}

	/**
	 * Fetch organization title and slug from pages using the "chaos_organization" custom field
	 * That custom field value should correspond to the title given in CHAOS
	 * @return array
	 */
	public static function get_organizations() {
		if(empty(self::$organizations)) {
			$key = 'chaos_organization';
			$posts = new WP_Query(array(
				'meta_key' => $key ,
				'post_type' => 'page',
				'post_status' => 'publish,private,future',
				'orderby' => 'title',
				'order' => 'ASC'
			));
			foreach($posts->posts as $post) {
				$post_organizations = get_post_custom_values($key, $post->ID);
				foreach($post_organizations as $organization) {
					self::$organizations[$organization] = array(
						'title' => $post->post_title,
						'slug' => $post->post_name,
						'id' => $post->ID
					);
				}
			}
		}
		return self::$organizations;
	}
	
	public static function get_organizations_merged() {
		$result = array();
		$organizations = self::get_organizations();
		foreach($organizations as $title => $organization) {
			if(!array_key_exists($organization['id'], $result)) {
				$result[$organization['id']] = $organization;
				$result[$organization['id']]['chaos_titles'] = array($title);
			} else {
				$result[$organization['id']]['chaos_titles'][] = $title;
			}
		}
		return $result;
	}

}

//Instantiate
new WPANPSearch();

//eol
