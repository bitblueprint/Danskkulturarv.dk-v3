<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
get_header(); ?>

			<?php while ( have_posts() ) : the_post(); ?>
			
				<article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
					<header class="entry-header">
						<?php the_post_thumbnail(); ?>
						<h1 class="entry-title">
							<a href="<?php the_permalink(); ?>" title="<?php echo esc_attr( sprintf( __( 'Permalink to %s', 'dka' ), the_title_attribute( 'echo=0' ) ) ); ?>" rel="bookmark"><?php the_title(); ?></a>
						</h1>
					</header><!-- .entry-header -->

					<div class="entry-content">
						<?php the_content( __( 'Continue reading <span class="meta-nav">&rarr;</span>', 'dka' ) ); ?>
						<?php wp_link_pages( array( 'before' => '<div class="page-links">' . __( 'Pages:', 'dka' ), 'after' => '</div>' ) ); ?>
					</div><!-- .entry-content -->

				</article><!-- #post -->

			<?php endwhile; // end of the loop. ?>

<?php get_sidebar(); ?>
<?php get_footer(); ?>