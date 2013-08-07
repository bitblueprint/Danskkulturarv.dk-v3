<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>
<?php get_header();

$current_view = (WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_VIEW) ? 'listview' : 'thumbnails');
$current_sort = isset(WPDKASearch::$sorts[WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_SORT)]) ? WPDKASearch::$sorts[WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_SORT)]['title'] : WPDKASearch::$sorts[null]['title'];

$views = array(
	array(
		'title' => 'Vis som liste',
		'view' => 'listview',
		'class' => 'icon-th-list',
		'link' => 'liste'
	),
	array(
		'title' => 'Vis som galleri',
		'view' => 'thumbnails',
		'class' => 'icon-th',
		'link' => null
	),
);

?>
<article class="container search-results">
	<div class="row search-results-top">
		<div class="col-12 col-sm-4">
			<p><span class="hidden-sm">Søgningen på <strong class="blue"><?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_html'); ?></strong> gav&nbsp;</span><span><?php echo WPChaosSearch::get_search_results()->MCM()->TotalCount(); ?> resultater</span></p>
		</div>
		<div class="col-4 col-sm-2">	
			<div class="dropdown sortby-dropdown pull-right">
				  <a class="sortby-link" id="dLabel" role="button" data-toggle="dropdown" data-target="#" href="#">Sorter: <strong class="blue"><?php echo $current_sort; ?></strong>&nbsp;<i class="icon-caret-down"></i></a>
				  <ul class="dropdown-menu" role="menu" aria-labelledby="dropdownMenu">
<?php foreach(WPDKASearch::$sorts as $sort) : ?>
					<li><a tabindex="-1" href="<?php echo WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_SORT => $sort['link'])); ?>" title="<?php echo $sort['title']; ?>"><?php echo $sort['title']; ?></a></li>
<?php endforeach; ?>
				  </ul>
			</div>
		</div>
		<div class="col-3 col-sm-2">
			<div class="search-result-listing btn-group">
<?php foreach($views as $view) :
		echo '<a href="'.WPChaosSearch::generate_pretty_search_url(array(WPChaosSearch::QUERY_KEY_VIEW => $view['link'])).'" title="'.$view['title'].'"><button type="button" class="btn btn-default'.($view['view'] == $current_view ? ' active' : '').'"><i class="'.$view['class'].'"></i></button></a>';
endforeach; ?>
			</div>
		</div>
		<div class="col-5 col-sm-4">
			<ul class="pagination pagination-large pull-right">
			  <?php echo $pagination = WPChaosSearch::paginate('echo=0&before=&after='); ?>
			</ul>
		</div>
	</div>
	<ul class="row <?php echo $current_view; ?>">

<?php
foreach(WPChaosSearch::get_search_results()->MCM()->Results() as $object) :
	WPChaosClient::set_object($object);
?>
		<li class="search-object col-12 col-sm-6 col-lg-3">
			<a class="thumbnail" href="<?php echo WPChaosClient::get_object()->url; ?>" id="<?php echo WPChaosClient::get_object()->GUID; ?>">
				<div class="thumb" style="background-image: url('<?php echo WPChaosClient::get_object()->thumbnail; ?>')">
					<!--<div class="duration">1:30:22</div>-->
				</div>
				<h2 class="title"><strong><?php echo WPChaosClient::get_object()->title; ?></strong></h2>
				<strong class="strong orange organization"><?php echo WPChaosClient::get_object()->organization; ?></strong>
				
				<p class="date">
				<?php if(WPChaosClient::get_object()->published) : ?>
				<i class="icon-calendar"></i> <?php echo WPChaosClient::get_object()->published; ?>
				<?php endif; ?></p>
				<hr>
				<div class="media-type-container">
					<i title="<?php echo WPChaosClient::get_object()->type_title; ?>" class="<?php echo WPChaosClient::get_object()->type_class; ?>"></i><i class="icon-eye-open"> <?php echo WPChaosClient::get_object()->views; ?></i>
				</div>
			</a>
		</li>
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

<?php get_footer(); ?>
