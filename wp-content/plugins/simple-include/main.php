<?php
/*
Plugin Name: Include HTML and PHP
Plugin URI: http://keithics.com
Description: Very easy way Include HTML and PHP from Wordpress Editor
Version: 1.0
Author: Keithics
Author URI: http://keithics.com
*/
if (!function_exists('add_action'))  {// for security reasons
	exit('You do not have sufficient permissions to access this page.');
}

add_action('admin_menu', 'wp_keithics_include_menu');


function wp_keithics_include_menu() {
    global $wpdb;

	add_menu_page('Include Docs', 'Include Docs', 'manage_options', 'wp_keithics_include', 'wp_keithics_include_options','');    
   
}

function wp_keithics_include_options(){
	echo '<div class="wrap">';
	include('docs.php');
	echo '</div>';
	
}

function sc_include( $atts,$inc ){
	return get_include_contents($inc);
		
}

add_shortcode( 'include', 'sc_include' );

function get_include_contents($filename) {
        ob_start();
        include get_theme_root().'/'.get_template().'/'.trim($filename);
        return ob_get_clean();
}