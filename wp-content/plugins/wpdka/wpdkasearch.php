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

	/**
	 * List of organizations from the WordPress site
	 * @var array
	 */
	public static $organizations = array();

	/**
	 * Construct
	 */
	public function __construct() {

		// Define the free-text search filter.
		$this->define_search_filters();
		
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
				$freetext = WPChaosClient::escapeSolrValue($freetext);
				$searches = array();
				foreach(WPDKAObject::$ALL_SCHEMA_GUIDS as $schemaGUID) {
					$searches[] = sprintf("(m%s_%s_all:(%s))", $schemaGUID, WPDKAObject::FREETEXT_LANGUAGE, $freetext);
				}
				$query[] = '(' . implode("+OR+", $searches) . ')';
			}
				
			return implode("+AND+", $query);
		}, 10, 2);
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
					'slug' => $post->post_name
				);
			} 
		}	
		return self::$organizations;
	}

	public static function paginate($args = array()) {
		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'before' => '<ul>',
			'after' => '</ul>',
			'before_link' => '<li>',
			'after_link' => '</li>',
			'count' => 5,
			'next' => 'Next',
			'previous' => 'Previous',
			'echo' => true
		));
		extract($args, EXTR_SKIP);

		$pageindex = WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_PAGE);

		$result = $before;

		$start = $pageindex-(ceil($count/2))+1;
		$end = $start+$count;

		if($previous)
			$result .= $before_link.'<a href="#'.($start-1).'">'.$previous.'</a>'.$after_link;


		for($i = $start; $i < $end; $i++) {
			$result .= $before_link.'<a href="#">'.$i.'</a>'.$after_link;
		}

		if($next)
			$result .= $before_link.'<a href="#'.($end).'">'.$next.'</a>'.$after_link;

		$result .= $after;

		if($echo) {
			echo $result;
		}
		
		return $result;
	}

}
//Instantiate
new WPDKASearch();

//eol