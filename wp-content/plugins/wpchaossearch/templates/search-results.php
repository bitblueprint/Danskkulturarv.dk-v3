<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>
<article class="container search-results">
	<div class="row search-results-top">
		<div class="span6">
			<p>Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav <?php echo WPChaosSearch::get_search_results()->MCM()->TotalCount(); ?> resultater</p>
		</div>
		<div class="span6">
		<div class="pagination pagination-right">
		  <?php WPDKASearch::paginate(); ?>
		</div>
	</div>
	</div>
	<ul class="row thumbnails mobile-two-up">

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
				<strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong>
				<p class="date"><i class="icon-calendar"></i> <?php echo WPChaosClient::get_object()->published; ?></p>
				<hr>
				<div class="media-type-container">
					<span class="<?php echo WPChaosClient::get_object()->type; ?>"><i class="icon-film"></i><i class="icon-eye-open"> 132</i></span>
				</div>
			</a>
		</li>
 <?php endforeach; WPChaosClient::reset_object(); ?>
	</ul>

		<div class="row search-results-top">
		<div class="span6">
			<p>Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav <?php echo WPChaosSearch::get_search_results()->MCM()->TotalCount(); ?> resultater</p>
		</div>
		<div class="span6">
		<div class="pagination pagination-right">
		  <?php WPDKASearch::paginate(); ?>
		</div>
	</div>
	</div>
</article>