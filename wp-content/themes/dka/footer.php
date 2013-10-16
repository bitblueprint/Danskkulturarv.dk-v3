<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
</article>
<div id="push"><!--//--></div>

</div><!-- end #wrap -->

<!-- sticky footer -->
<footer>
	<div class="container text-center">
		<!--<div class="bloginfo-footer"><?php bloginfo( 'name' ); ?> <span>- <?php bloginfo( 'description' ); ?></span></div>-->
<?php 
    wp_nav_menu( array(
        'theme_location' => 'secondary',
        'depth'      => 1,
        'container'  => false,
        'menu_class' => 'nav-footer',
        'fallback_cb' => false,
        'items_wrap' => '<ul id="%1$s" class="%2$s">%3$s<li><a target="_blank" href="https://www.facebook.com/danskkulturarv" class="social" title="'.sprintf(esc_attr__('%s on Facebook','dka'),get_bloginfo( 'name' )).'"><i class="icon-facebook-sign"></i></a><a target="_blank" href="https://twitter.com/danskkulturarv" class="social" title="'.sprintf(esc_attr__('%s on Twitter','dka'),get_bloginfo( 'name' )).'"><i class="icon-twitter"></i></a></li></ul>'
        )
    );
?>

		<div class="copyright">
            <p>Copyright &#169; 2012-<?php echo date('Y'); ?></p>
        <a href="<?php echo esc_url( home_url( '/' ) ); ?>"><img src="<?php echo get_template_directory_uri() . '/img/fiatiftawhitelogofooter.png' ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"></a></div>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>