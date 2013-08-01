<?php
/**
 * @package WP DKA
 * @version 1.0
 */

/**
 * Class that generates sitemaps with links for every object in the chaos service.
 */
class WPDKASitemap {
	
	const DEFAULT_PAGE_SIZE = 500;
	
	public function __construct() {
		
	}
	
	public static function install() {
		self::add_rewrite_tags();
		self::add_rewrite_rules();
	}
	
	public static function add_rewrite_tags() {
		add_rewrite_tag('%sitemapPageSize%', '(\d+)');
		add_rewrite_tag('%sitemapPageIndex%', '(\d+)');
		add_rewrite_tag('%sitemapIndex%', '');
	}
	
	public static function add_rewrite_rules() {
		add_rewrite_rule('sitemap\.xml/(\d+)/(\d+)$', 'index.php?sitemapPageSize=$matches[1]&sitemapPageIndex=$matches[2]', 'top');
		add_rewrite_rule('sitemap\.xml$', 'index.php?sitemapIndex', 'top');
	}
	
	public static function output_sitemap() {
		global $wp_query;
		error_reporting( E_ALL );
		if(array_key_exists('sitemapPageSize', $wp_query->query_vars) && array_key_exists('sitemapPageIndex', $wp_query->query_vars)) {
			$pageSize = intval($wp_query->query_vars['sitemapPageSize']);
			$pageIndex = intval($wp_query->query_vars['sitemapPageIndex']);

			$objects = WPChaosObject::parseResponse(WPChaosClient::instance()->Object()->Get("", "DateCreated+asc", null, $pageIndex, $pageSize, true));
			
			$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></urlset>');
			foreach($objects as $object) {
				$url = $xml->addChild('url');
				//var_dump($object);
				$url->addChild('loc', $object->url);
			}
			
			$schema = 'http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd';
		} elseif(array_key_exists('sitemapIndex', $wp_query->query_vars)) {
			$xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"></sitemapindex>');
			
			$totalCount = WPChaosClient::instance()->Object()->Get("", null, null, 0, 0)->MCM()->TotalCount();
			for($pageIndex = 0; $pageIndex * self::DEFAULT_PAGE_SIZE < $totalCount; $pageIndex++) {
				$location = site_url('sitemap.xml/' . self::DEFAULT_PAGE_SIZE .'/'. $pageIndex );
				$sitemap = $xml->addChild('sitemap');
				$sitemap->addChild('loc', $location);
			}
			$schema = 'http://www.sitemaps.org/schemas/sitemap/0.9/siteindex.xsd';
		} else {
			// This request is not relevant.
			return;
		}
			
		$dom = dom_import_simplexml($xml)->ownerDocument;
		if($schema && $dom->schemaValidate($schema)) {
			header("Content-Type: text/xml");
			echo $xml->asXML();
			exit;
		} else {
			error_log('Error generating sitemap! Schema validation failed.');
			exit;
		}
	}
}
add_action('init', array('WPDKASitemap', 'add_rewrite_tags'), 9);
add_action('init', array('WPDKASitemap', 'add_rewrite_rules'), 9);
add_action('template_redirect', array('WPDKASitemap', 'output_sitemap'));
