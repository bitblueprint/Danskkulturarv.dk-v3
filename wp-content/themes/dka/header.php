<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?><!DOCTYPE html>
<!--[if lt IE 7 ]><html class="ie6" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 7 ]><html class="ie7" <?php language_attributes(); ?>> <![endif]-->
<!--[if IE 8 ]><html class="ie8" <?php language_attributes(); ?>> <![endif]-->
<!--[if (gte IE 9)|!(IE)]><!--><html <?php language_attributes(); ?>><!--<![endif]-->
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
	<meta name="viewport" content="width=device-width, initial-scale=1.0" />
	<meta name="apple-mobile-web-app-capable" content="yes"/>
	<title><?php wp_title( '|', true, 'right' ); ?></title>
	<link rel="profile" href="http://gmpg.org/xfn/11" />
	<link rel="pingback" href="<?php bloginfo( 'pingback_url' ); ?>" />
	<?php wp_head(); ?>
</head>
<body>
	<!-- start wrapper (for page content to push down sticky footer) -->
	<div id="wrap">
		<!-- start navigation -->
		<div class="navbar navbar-inverse navbar-fixed-top">
			<!--<div class="navbar-inner">-->
				<div class="container">
					<!-- .navbar-toggle is used as the toggle for collapsed navbar content -->
					<button type="button" class="navbar-toggle" data-toggle="collapse" data-target=".navbar-responsive-collapse">
				    	<span class="icon-bar"></span>
				    	<span class="icon-bar"></span>
				    	<span class="icon-bar"></span>
				    </button>
					
					<a class="brand" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" rel="home"><img width?="264" height="27" src="<?php echo get_template_directory_uri() . '/img/dka-logo-top.png' ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" alt="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>"></a>
					<div class="nav-collapse collapse navbar-responsive-collapse">
<?php 
    wp_nav_menu( array(
        'theme_location'       => 'primary',
        'depth'      => 3,
        'container'  => false,
        'menu_class' => 'nav',
        'fallback_cb' => false,
        'walker' => new wp_bootstrap_navwalker())
    );
?>
					</div><!--/.nav-collapse -->
				</div>
			<!--</div>-->
		</div><!-- end navigation -->
		<!-- start search -->
		<div class="container search"><div class="row"><?php dynamic_sidebar( 'Top' ); ?></div></div>
		<!-- end search -->

		<article class="container">