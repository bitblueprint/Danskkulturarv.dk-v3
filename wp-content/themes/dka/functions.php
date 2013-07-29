<?php
/**
 * @package WordPress
 * @subpackage DKA
 */

require_once('wp-bootstrap-navwalker/wp_bootstrap_navwalker.php');

function dka_setup() {

	add_editor_style();

	add_theme_support( 'automatic-feed-links' );

	//add_theme_support( 'post-formats', array( 'aside', 'image', 'link', 'quote', 'status' ) );

	register_nav_menu( 'primary','Primary');
	register_nav_menu( 'secondary','Secondary');

	//add_theme_support( 'post-thumbnails' );
	//set_post_thumbnail_size( 624, 9999 ); // Unlimited height, soft crop
	add_filter('wp_mail_from', function($old) { return get_bloginfo('admin_email'); });
	add_filter('wp_mail_from_name', function($old) { return get_bloginfo('name'); });
}
add_action( 'after_setup_theme', 'dka_setup' );

function dka_scripts_styles() {

	wp_enqueue_style( 'dka-style', get_template_directory_uri() . '/css/styles.css' );

	wp_dequeue_script('jquery');
	wp_enqueue_script( 'jquery', get_template_directory_uri() . '/js/jquery-1.10.1.min.js', array(), '1.10.1', true );

	wp_enqueue_script( 'custom-functions', get_template_directory_uri() . '/js/custom-functions.js', array('jquery'), '1', true );

	$bootstrap_scripts = array(
		'transition',
		'alert',
		'button',
		'carousel',
		'collapse',
		'dropdown',
		'modal',
		'scrollspy',
		'tab',
		'tooltip',
		'popover',
		'typeahead',
		'affix'
	);
	foreach($bootstrap_scripts as $bootscript) {
		wp_enqueue_script('bootstrap-'. $bootscript, get_template_directory_uri() . '/js/bootstrap-'.$bootscript.'.js', array(), '2.3.2', true );
	}


}
add_action( 'wp_enqueue_scripts', 'dka_scripts_styles' );

function dka_widgets_init() {

	register_sidebar( array(
		'id' => 'sidebar-1',
		'name' => 'Sidebar',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'id' => 'sidebar-2',
		'name' => 'Top',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	//Remove some widgets not needed that clutter the screen
	unregister_widget('WP_Widget_Pages');
	unregister_widget('WP_Widget_Calendar');
	unregister_widget('WP_Widget_Archives');
	unregister_widget('WP_Widget_Links');
	unregister_widget('WP_Widget_Meta');
	//unregister_widget('WP_Widget_Search');
	//unregister_widget('WP_Widget_Text');
	unregister_widget('WP_Widget_Categories');
	//unregister_widget('WP_Widget_Recent_Posts');
	unregister_widget('WP_Widget_Recent_Comments');
	unregister_widget('WP_Widget_RSS');
	unregister_widget('WP_Widget_Tag_Cloud');
	//unregister_widget('WP_Nav_Menu_Widget');
}

add_action( 'widgets_init', 'dka_widgets_init' );

function dka_content_nav( $html_id ) {
	global $wp_query;

	$html_id = esc_attr( $html_id );

	if ( $wp_query->max_num_pages > 1 ) : ?>
		<nav id="<?php echo $html_id; ?>" class="navigation" role="navigation">
			<h3 class="assistive-text"><?php _e( 'Post navigation', 'dka' ); ?></h3>
			<div class="nav-previous alignleft"><?php next_posts_link( __( '<span class="meta-nav">&larr;</span> Older posts', 'dka' ) ); ?></div>
			<div class="nav-next alignright"><?php previous_posts_link( __( 'Newer posts <span class="meta-nav">&rarr;</span>', 'dka' ) ); ?></div>
		</nav><!-- #<?php echo $html_id; ?> .navigation -->
	<?php endif;
}

if ( ! function_exists( 'dka_entry_meta' ) ) :
/**
 * Prints HTML with meta information for current post: categories, tags, permalink, author, and date.
 *
 * Create your own dka_entry_meta() to override in a child theme.
 *
 * @since Twenty Twelve 1.0
 */
function dka_entry_meta() {
	// Translators: used between list items, there is a space after the comma.
	$categories_list = get_the_category_list( __( ', ', 'dka' ) );

	// Translators: used between list items, there is a space after the comma.
	$tag_list = get_the_tag_list( '', __( ', ', 'dka' ) );

	$date = sprintf( '<a href="%1$s" title="%2$s" rel="bookmark"><time class="entry-date" datetime="%3$s">%4$s</time></a>',
		esc_url( get_permalink() ),
		esc_attr( get_the_time() ),
		esc_attr( get_the_date( 'c' ) ),
		esc_html( get_the_date() )
	);

	$author = sprintf( '<span class="author vcard"><a class="url fn n" href="%1$s" title="%2$s" rel="author">%3$s</a></span>',
		esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ),
		esc_attr( sprintf( __( 'View all posts by %s', 'dka' ), get_the_author() ) ),
		get_the_author()
	);

	// Translators: 1 is category, 2 is tag, 3 is the date and 4 is the author's name.
	if ( $tag_list ) {
		$utility_text = __( 'This entry was posted in %1$s and tagged %2$s on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	} elseif ( $categories_list ) {
		$utility_text = __( 'This entry was posted in %1$s on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	} else {
		$utility_text = __( 'This entry was posted on %3$s<span class="by-author"> by %4$s</span>.', 'dka' );
	}

	printf(
		$utility_text,
		$categories_list,
		$tag_list,
		$date,
		$author
	);
}
endif;

function dka_wp_title( $title, $sep ) {
	global $paged, $page;

	if ( is_feed() )
		return $title;

	// Add the site name.
	$title .= get_bloginfo( 'name' );

	// Add the site description for the home/front page.
	$site_description = get_bloginfo( 'description', 'display' );
	if ( $site_description && ( is_home() || is_front_page() ) )
		$title = "$title $sep $site_description";

	// Add a page number if necessary.
	if ( $paged >= 2 || $page >= 2 )
		$title = "$title $sep " . sprintf( __( 'Page %s', 'dka' ), max( $paged, $page ) );

	return $title;
}
add_filter( 'wp_title', 'dka_wp_title', 10, 2 );

function dka_sanitize_title($title, $raw_title, $context) {

	$replacements = array(
		'æ' => 'cab9d5d0-f843-11e2-b778-0800200c9a66',
		'Æ' => 'cab9d5d1-f843-11e2-b778-0800200c9a66',
		'Ø' => 'cab9d5d2-f843-11e2-b778-0800200c9a66',
		'ø' => 'cab9d5d3-f843-11e2-b778-0800200c9a66',
		'Å' => 'cab9d5d4-f843-11e2-b778-0800200c9a66',
		'å' => 'cab9d5d5-f843-11e2-b778-0800200c9a66'

	);

    if ( 'save' == $context ) {
    	$title = $raw_title;
    	$title = str_replace(array_keys($replacements), $replacements, $title);
    	$title = remove_accents($title);
    	$title = str_replace($replacements, array_keys($replacements), $title);
    }
    return $title;
}
add_filter('sanitize_title', 'dka_sanitize_title', 10, 3);

//eol