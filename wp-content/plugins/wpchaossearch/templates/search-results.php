<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>
<article class="container search-results">
	<div class="row">
		<div class="span6">
			<p>Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav <?php echo $serviceResult->MCM()->TotalCount(); ?> resultater</p>
		</div>
		<div class="span1 pull-right">
			<a href="<?php echo add_query_arg(WPChaosSearch::QUERY_KEY_PAGEINDEX, $args['pageindex']+1); ?>">Næste ></a>
		</div>
		<div class="span1 pull-right">
			<a href="<?php echo add_query_arg(WPChaosSearch::QUERY_KEY_PAGEINDEX, $args['pageindex']-1); ?>">< Forrige</a>
		</div>
	</div>
	<ul class="row thumbnails">

<?php
foreach($objects as $object) :
	WPChaosClient::set_object($object);

	$link = add_query_arg( 'guid', $object->GUID, get_site_url()."/");

?>
		<li class="search-object span3">
			<a class="thumbnail" href="<?php echo $link; ?>">
				<div class="thumb" style="background-image: url('<?php echo WPChaosClient::get_object()->thumbnail; ?>')">
					<div class="duration">1:30:22</div>
				</div>
				<h2 class="title"><strong><?php echo WPChaosClient::get_object()->title; ?></strong></h2>
				<div class="organization"><strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong></div>
				<p class="date"><?php echo WPChaosClient::get_object()->published; ?></p>
				<hr>
				<div class="media-type-container">
					<span class="<?php echo WPChaosClient::get_object()->type; ?>"></span>
				</div>
			</a>
		</li>
 <?php endforeach; WPChaosClient::reset_object(); ?>
	</ul>
</article>