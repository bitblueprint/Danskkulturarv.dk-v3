<?php
$format_types = array(
	array(
		'type' => WPDKAObject::TYPE_AUDIO,
		'class' => 'icon-volume-up',
		'title' => 'Lyd',
		),
	array(
		'type' => WPDKAObject::TYPE_VIDEO,
		'class' => 'icon-film',
		'title' => 'Video',
	),
	// This is not yet supported by the metadata.
	//array(
	//	'type' => WPDKAObject::TYPE_UNKNOWN,
	//	'class' => 'icon-file-text',
	//	'title' => 'Dokumenter',
	//),
	array(
		'type' => WPDKAObject::TYPE_IMAGE,
		'class' => 'icon-picture',
		'title' => 'Billeder',
	),
);
$types = WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_TYPE);
$organizations = WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_ORGANIZATION);
$advanced_search_expanded = ((!empty($types) || !empty($organizations)) ? " in" : "");
?>
<form method="GET" action="<?php echo $page; ?>" class="col-12">
	<div class="col-8">
		<div class="input-group">
			<input class="form-control" id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_attr'); ?>" placeholder="<?php echo $freetext_placeholder; ?>" />
			<span class="input-group-btn">
				<button type="submit" class="btn btn-search btn-large">Søg</button>
			</span>
	</div>
	</div>
	<div class="col-4 btn-advanced-search-container">
		<button class="btn btn-large btn-block btn-advanced-search collapsed blue visible-lg" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning <i class="icon-angle-down pull-right">&nbsp;&nbsp;</i></button>
		<button class="btn btn-large btn-block btn-advanced-search collapsed blue hidden-lg" type="button" data-toggle="collapse" href="#advanced-search-container"><i class="icon-cogs"></i><i class="icon-angle-down pull-right"></i></button>
	</div>
	<div id="advanced-search-container" class="container row collapse<?php echo $advanced_search_expanded; ?>">

		<div class="col-3 filter-container filter-media-type">
			<label class="btn filter-btn filter-btn-all">Alle Typer<i class="icon-ok"></i></label>
			<hr>
			<!-- Chage the inline CSS property style="opacity:0.5;" to display: none; when done debugging. -->
<?php foreach($format_types as $format) : ?>
			<label for="<?php echo WPDKASearch::QUERY_KEY_TYPE .'-'. $format['type']; ?>" class="btn filter-btn filter-btn-single">
				<input type="checkbox" class="chaos-filter" style="display: none;" name="<?php echo WPDKASearch::QUERY_KEY_TYPE; ?>[]" value="<?php echo $format['type']; ?>" id="<?php echo WPDKASearch::QUERY_KEY_TYPE .'-'. $format['type']; ?>" <?php checked(in_array($format['type'],(array)$types)); ?>>
				<i class="<?php echo $format['class']; ?>"></i><?php echo $format['title']; ?><i class="icon-remove-sign"></i>
			</label> 
<?php endforeach; ?>
		</div>
		<div class="col-6 filter-container filter-media-type filter-organizations">
			<label class="btn filter-btn filter-btn-all">Alle Organisationer<i class="icon-ok"></i></label>
			<hr>
<?php foreach(WPDKASearch::get_organizations() as $title => $organization) : ?>
			<label for="<?php echo WPDKASearch::QUERY_KEY_ORGANIZATION .'-'. $organization['slug']; ?>" class="btn filter-btn filter-btn-single">
				<input type="checkbox" class="chaos-filter" style="display: none;" name="<?php echo WPDKASearch::QUERY_KEY_ORGANIZATION; ?>[]" value="<?php echo $organization['slug']; ?>" id="<?php echo WPDKASearch::QUERY_KEY_ORGANIZATION .'-'. $organization['slug']; ?>" <?php checked(in_array($organization['slug'],(array)$organizations)); ?>>
				<?php echo $organization['title']; ?><i class="icon-remove-sign"></i>
			</label> 
<?php endforeach; ?>
		</div>
	</div>
</form>