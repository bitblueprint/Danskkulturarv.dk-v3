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

					<footer class="entry-meta">
						<?php dka_entry_meta(); ?>
						<?php if ( is_singular() && get_the_author_meta( 'description' ) && is_multi_author() ) : // If a user has filled out their description and this is a multi-author blog, show a bio on their entries. ?>
							<div class="author-info">
								<div class="author-avatar">
									<?php echo get_avatar( get_the_author_meta( 'user_email' ), apply_filters( 'dka_author_bio_avatar_size', 68 ) ); ?>
								</div><!-- .author-avatar -->
								<div class="author-description">
									<h2><?php printf( __( 'About %s', 'dka' ), get_the_author() ); ?></h2>
									<p><?php the_author_meta( 'description' ); ?></p>
									<div class="author-link">
										<a href="<?php echo esc_url( get_author_posts_url( get_the_author_meta( 'ID' ) ) ); ?>" rel="author">
											<?php printf( __( 'View all posts by %s <span class="meta-nav">&rarr;</span>', 'dka' ), get_the_author() ); ?>
										</a>
									</div><!-- .author-link	-->
								</div><!-- .author-description -->
							</div><!-- .author-info -->
						<?php endif; ?>
					</footer><!-- .entry-meta -->
				</article><!-- #post -->


				<nav class="nav-single">
					<h3 class="assistive-text"><?php _e( 'Post navigation', 'twentytwelve' ); ?></h3>
					<span class="nav-previous"><?php previous_post_link( '%link', '<span class="meta-nav">' . _x( '&larr;', 'Previous post link', 'twentytwelve' ) . '</span> %title' ); ?></span>
					<span class="nav-next"><?php next_post_link( '%link', '%title <span class="meta-nav">' . _x( '&rarr;', 'Next post link', 'twentytwelve' ) . '</span>' ); ?></span>
				</nav><!-- .nav-single -->

			<?php endwhile; // end of the loop. ?>

		</div><!-- #content -->
	</div><!-- #primary -->

<?php get_sidebar(); ?>
<?php get_footer(); ?>