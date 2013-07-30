<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
get_header(); ?>

<article class="container single-material">
	<div>
		<div class="col-lg-9 col-12">
			<div>
				<?php dynamic_sidebar( 'wpchaos-obj-featured' ); ?>
			</div>
			<div>
				<?php dynamic_sidebar( 'wpchaos-obj-main' ); ?>
			</div>
		</div>
		<div class="col-lg-3 col-12">
			<div>
				<ul class="nav info">
					<li><i class="icon-eye-open"></i> Visninger<strong class="pull-right">342</strong></li>
					<li><i class="icon-link"></i> <a href="#" class="blue">kltrv.dk/gt7tG</a></li>
					<li>&copy; Copyright DR. Materialet må ikke gengives uden tilladelse.</li>
					<hr>
					<li class="social"><a href="#" class="pull-left"><i class="icon-facebook-sign"></i></a><a href="#" class="pull-left"><i class="icon-twitter"></i></a><a href="#" class="pull-left"><i class="icon-google-plus-sign"></i></a><a href="#" class="pull-left"><i class="icon-envelope"></i></a></li>
				</ul>
			</div>
		</div>
	</div>
</article>

<?php get_footer(); ?>