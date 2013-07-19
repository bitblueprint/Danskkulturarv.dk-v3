<article class="container search-results">
	<div class="row">
		<div class="span6">
			<p>Søgningen på <strong class="blue"><?php echo esc_html($_GET[self::QUERY_KEY_FREETEXT]); ?></strong> gav <?php echo $serviceResult->MCM()->TotalCount(); ?> resultater</p>
		</div>
		<div class="span1 pull-right">
			<a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $args['pageindex']+1); ?>">Næste ></a>
		</div>
		<div class="span1 pull-right">
			<a href="<?php echo add_query_arg(self::QUERY_KEY_PAGEINDEX, $args['pageindex']-1); ?>">< Forrige</a>
		</div>
	</div>
	<ul class="row thumbnails">

<?php

		/*	
		 <img src="img/turell.jpg" alt="">        
          <span class="series"></span><span class="views">19</span><span class="likes">3</span>
		 */

foreach($objects as $object) :
	$wp_object = new WPChaosObject($object);

	$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");

?>
		<li class="search-object span3">
			<a class="thumbnail" href="<?php echo $link; ?>">
				<h2 class="title"><strong><?php echo $wp_object->title; ?></strong></h2>
				<div class="organization"><strong class="strong orange"><?php echo $wp_object->organization; ?></strong></div>
				<p class="date"><?php echo $wp_object->published; ?></p>
				<hr>
				<span class="<?php echo $wp_object->type; ?>"></span>
			</a>
		</li>
 <?php endforeach; ?>
	</ul>
</article>