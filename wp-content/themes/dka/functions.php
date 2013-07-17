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
}
add_action( 'after_setup_theme', 'dka_setup' );

function dka_scripts_styles() {

	wp_enqueue_style( 'dka-style', get_template_directory_uri() . '/css/styles.css' );

	wp_enqueue_script( 'custom-functions', get_template_directory_uri() . '/js/custom-functions.js', array(), '1', true );

	wp_dequeue_script('jquery');
	wp_enqueue_script( 'jquery', get_template_directory_uri() . '/js/jquery-1.10.1.min.js', array(), '1.10.1', true );

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
		'name' => 'Sidebar',
		'before_widget' => '<aside id="%1$s" class="widget %2$s">',
		'after_widget' => '</aside>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

	register_sidebar( array(
		'name' => 'Top',
		'before_widget' => '<div id="%1$s" class="widget %2$s">',
		'after_widget' => '</div>',
		'before_title' => '<h3 class="widget-title">',
		'after_title' => '</h3>',
	) );

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

//eol