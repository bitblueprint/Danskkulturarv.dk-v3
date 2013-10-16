<?php
$types = WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_TYPE);
$organizations = WPChaosSearch::get_search_var(WPANPSearch::QUERY_KEY_ORGANIZATION);

global $facets;
$facets = array(
	WPANPSearch::QUERY_KEY_TYPE => WPChaosSearch::generate_facet("FormatTypeName", WPANPSearch::QUERY_KEY_TYPE),
	WPANPSearch::QUERY_KEY_ORGANIZATION => WPChaosSearch::generate_facet("DKA-Organization", WPANPSearch::QUERY_KEY_ORGANIZATION),
);
// function get_facet_count($field, $values) {
	// if(is_string($values)) {
		// $values = array($values);
	// }
	// global $facets;
	// $sum = 0;
	// if(array_key_exists($field, $facets)) {
		// foreach($values as $value) {
			// if(array_key_exists($value, $facets[$field])) {
				// $sum += intval($facets[$field][$value]);
			// }
		// }
	// }
	// return $sum;
// }
$advanced_search_expanded = ((!empty($types) || !empty($organizations)) ? " in" : "");
?>
<form method="GET" action="<?php echo $page; ?>" class="col-12">
	<div class="col-lg-8 col-10">
		<div class="input-group">
			<input type="hidden" name="<?php echo WPChaosSearch::QUERY_KEY_VIEW; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_VIEW, 'esc_attr'); ?>">
			<input type="hidden" name="<?php echo WPChaosSearch::QUERY_KEY_SORT; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_SORT, 'esc_attr'); ?>">
			<input class="form-control" id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_attr,trim'); ?>" placeholder="<?php echo $freetext_placeholder; ?>" />
			<span class="input-group-btn">
				<button type="submit" class="btn btn-search btn-large" id="searchsubmit"><?php _ex('Search','verb','dka'); ?></button>
			</span>
	</div>
	</div>
	<div class="col-lg-4 col-2 btn-advanced-search-container">
		<button class="btn btn-large btn-block btn-advanced-search collapsed blue dropdown-toggle" type="button" data-toggle="collapse" data-target="#advanced-search-container">
			<span class="visible-lg"><i class="icon-cog"></i> <?php _e('Refine search','dka'); ?> <i class="icon-caret-down"></i></span>
			<span class="hidden-lg"><i class="icon-cog"></i> <i class="icon-caret-down"></i></span>
		</button>
	</div>
	<div id="advanced-search-container" class="container row collapse<?php echo $advanced_search_expanded; ?>">

		<div class="col-sm-3 col-12 filter-container filter-media-type">
			<label class="btn filter-btn filter-btn-all"><?php _e('All Formats','dka'); ?><i class="icon-ok"></i></label>
			<hr class="hidden-sm">
<?php foreach(WPANPObject::$format_types as $format_type => $args) : if($format_type == WPANPObject::TYPE_IMAGE_AUDIO || $format_type == WPANPObject::TYPE_UNKNOWN) continue; ?>
			<label title="<?php echo $args['title']; ?>" for="<?php echo WPANPSearch::QUERY_KEY_TYPE .'-'. $format_type; ?>" class="btn filter-btn filter-btn-single">
				<input type="checkbox" class="chaos-filter" style="display: none;" name="<?php echo WPANPSearch::QUERY_KEY_TYPE; ?>[]" value="<?php echo $format_type; ?>" id="<?php echo WPANPSearch::QUERY_KEY_TYPE .'-'. $format_type; ?>" <?php checked(in_array($format_type,(array)$types)); ?>>
				<i class="<?php echo $args['class']; ?>"></i><?php echo $args['title']; ?> ()<i class="icon-remove-sign"></i>
			</label>
<?php endforeach; ?>
		</div>

		<div class="col-sm-6 col-12 filter-container filter-media-type filter-organizations">
			<label class="btn filter-btn filter-btn-all"><?php _e('All Organizations','dka'); ?><i class="icon-ok"></i></label>
			<hr class="hidden-sm">

		</div>
	</div>
</form>