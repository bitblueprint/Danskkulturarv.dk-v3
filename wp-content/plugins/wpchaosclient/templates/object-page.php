<?php
/**
 * @package WP Chaos Client
 * @version 1.0
 */
get_header(); ?>

<article class="container single-material">
	<div class="row">
		<div class="span9">
			<div class="row">
				<?php dynamic_sidebar( 'wpchaos-obj-featured' ); ?>
			</div>
			<div class="row">
				<?php dynamic_sidebar( 'wpchaos-obj-main' ); ?>
			</div>
		</div>
		<div class="span3">
			<div class="">
				<ul class="nav info">
					<li class="views">Views<strong class="blue pull-right">342</strong></li>
					<li class="share">Del<strong class="blue pull-right">22</strong></li>
					<li class="short-url">kltrv.dk/gt7tG</li>
				</ul>
			</div>
		</div>
	</div>
</article>

<?php get_footer(); ?>