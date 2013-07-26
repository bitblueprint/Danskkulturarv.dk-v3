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
	
	const QUERY_KEY_TYPE = 'type';
	const QUERY_KEY_ORGANIZATION = 'organisation';

	/**
	 * List of organizations from the WordPress site
	 * @var array
	 */
	public static $organizations = array();

	/**
	 * Construct
	 */
	public function __construct() {

		WPChaosSearch::register_search_query_variable(2, WPDKASearch::QUERY_KEY_TYPE, '[\w+]+', true, ' ');
		WPChaosSearch::register_search_query_variable(3, WPDKASearch::QUERY_KEY_ORGANIZATION, '[\w+]+', true, ' ');
		
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

	/**
	 * Pagination for search results
	 * @param  array  $args Arguments can be passed for specific behaviour
	 * @return string       
	 */
	public static function paginate($args = array()) {
		// Grab args or defaults
		$args = wp_parse_args($args, array(
			'before' => '<ul>',
			'after' => '</ul>',
			'before_link' => '<li>',
			'after_link' => '</li>',
			'count' => 5,
			'next' => '&raquo;',
			'previous' => '&laquo;',
			'echo' => true
		));
		extract($args, EXTR_SKIP);
		
		//Get current page number
		$page = WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_PAGE)?:1;
		$objects = 20;
		//Get max page number
		$max_page = ceil(WPChaosSearch::get_search_results()->MCM()->TotalCount()/$objects);
		
		$result = $before;

		//Start should be in the center
		$start = $page-(ceil($count/2))+1;
		//When reaching the end, push start to the left
		$start = min($start,($max_page+1)-$count);
		//Start can minimum be 1
		$start = max(1,$start);
		//Set end according to start
		$end = $start+$count;

		//Is prevous wanted
		if($previous) {
			$result .= self::paginate_page($before_link,$after_link,$page-1,$start,$max_page,$page,$previous);
		}

		//Set enumeration
		for($i = $start; $i < $end; $i++) {
			$result .= self::paginate_page($before_link,$after_link,$i,$start,$max_page,$page);
		}

		//Is next wanted
		if($next) {
			$result .= self::paginate_page($before_link,$after_link,$page+1,$start,$max_page,$page,$next);
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
	 * @param  string $title       
	 * @return string              
	 */
	public static function paginate_page($before_link,$after_link,$page,$min,$max,$current,$title = "") {
		if($page > $max || $page < $min) {
			$result = str_replace('>',' class="disabled">',$before_link).'<span>'.($title?:$page).'</span>'.$after_link;
		} else if(!$title && $page == $current) {
			$result = str_replace('>',' class="active">',$before_link).'<span>'.$page.'</span>'.$after_link;
		} else {
			$result = $before_link.'<a href="'. WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_PAGE => $page)) .'">'.($title?:$page).'</a>'.$after_link;
		}
		return $result;
	}

}

//Instantiate
new WPDKASearch();

//eol