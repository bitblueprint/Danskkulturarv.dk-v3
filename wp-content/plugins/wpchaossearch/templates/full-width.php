<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
get_header();

while ( have_posts() ) : the_post(); 
	the_content(); 
endwhile;

get_footer(); ?>