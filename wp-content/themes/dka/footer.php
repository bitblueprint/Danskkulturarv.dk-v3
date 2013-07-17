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
		<p><?php bloginfo( 'name' ); ?> - <?php bloginfo( 'description' ); ?></p>
		
<?php 
    wp_nav_menu( array(
        'theme_location' => 'secondary',
        'depth'      => 1,
        'container'  => false,
        'menu_class' => '',
        'fallback_cb' => false,
        )
    );
?>

		<p>Copyright &#169; 2012-<?php echo date('Y'); ?> <a href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"><?php bloginfo( 'name' ); ?></a></p>
	</div>
</footer>

<?php wp_footer(); ?>
</body>
</html>