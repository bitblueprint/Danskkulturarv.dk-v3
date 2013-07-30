<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>
<?php get_header(); ?>
<article class="container search-results">
	<div class="row search-results-top">
		<div class="col-6">
			<p><span class="hidden-sm">Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav&nbsp;</span><span><?php echo WPChaosSearch::get_search_results()->MCM()->TotalCount(); ?> resultater</span></p>
		</div>
		<div class="col-6">
		<ul class="pagination pagination-large pull-right">
		  <?php echo $pagination = WPChaosSearch::paginate('echo=0&before=&after='); ?>
		</ul>
		</div>
	</div>
	<ul class="row thumbnails">

<?php
foreach(WPChaosSearch::get_search_results()->MCM()->Results() as $object) :
	WPChaosClient::set_object($object);
?>
		<div class="search-object col-12 col-sm-6 col-lg-3">
			<a class="thumbnail" href="<?php echo WPChaosClient::get_object()->url; ?>" id="<?php echo WPChaosClient::get_object()->GUID; ?>">
				<div class="thumb" style="background-image: url('<?php echo WPChaosClient::get_object()->thumbnail; ?>')">
					<div class="duration">1:30:22</div>
				</div>
				<h2 class="title"><strong><?php echo WPChaosClient::get_object()->title; ?></strong></h2>
				<strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong>
				<p class="date"><i class="icon-calendar"></i> <?php echo WPChaosClient::get_object()->published; ?></p>
				<hr>
				<div class="media-type-container">
					<i title="<?php echo WPChaosClient::get_object()->type_title; ?>" class="<?php echo WPChaosClient::get_object()->type_class; ?>"></i><i class="icon-eye-open"> 132</i>
				</div>
			</a>
		</div>
 <?php endforeach; WPChaosClient::reset_object(); ?>
	</ul>

	<div class="row search-results-top">
		<div class="col-6">
			<p><span class="hidden-sm">Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav&nbsp;</span><span><?php echo WPChaosSearch::get_search_results()->MCM()->TotalCount(); ?> resultater</span></p>
		</div>
		<div class="col-6">
		<ul class="pagination pagination-large pull-right">
		  <?php echo $pagination; ?>
		</ul>
		</div>
	</div>
</article>

</article>
<?php get_footer(); ?>
