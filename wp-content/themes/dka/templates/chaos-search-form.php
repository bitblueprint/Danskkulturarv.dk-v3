<?php

$types = WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_TYPE);
$organizations = WPChaosSearch::get_search_var(WPDKASearch::QUERY_KEY_ORGANIZATION);
$advanced_search_expanded = ((!empty($types) || !empty($organizations)) ? " in" : "");
?>
<form method="GET" action="<?php echo $page; ?>" class="span12">
	<div class="input-append span7">
		<input id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_attr'); ?>" placeholder="<?php echo $freetext_placeholder; ?>" /><button type="submit" id="searchsubmit" class="btn btn-large btn-search">Søg</button>
	</div>
	<div class="btn-group span4 pull-right btn-advanced-search-container">
		<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed blue" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning <i class="icon-angle-down pull-right">&nbsp;&nbsp;&nbsp;</i></button>
	</div>
	<div id="advanced-search-container" class="container row collapse<?php echo $advanced_search_expanded; ?>">

		<div class="span3 filter-container filter-media-type">
			<label class="btn filter-btn filter-btn-all">Alle Typer<i class="icon-ok"></i></label>
			<hr>
			<!-- Chage the inline CSS property style="opacity:0.5;" to display: none; when done debugging. -->
<?php foreach(WPDKAObject::$format_types as $format_type => $args) : if($format_type == WPDKAObject::TYPE_IMAGE_AUDIO) continue; ?>
			<label title="<?php echo $args['title']; ?>" for="<?php echo WPDKASearch::QUERY_KEY_TYPE .'-'. $format_type; ?>" class="btn filter-btn filter-btn-single">
				<input type="checkbox" class="chaos-filter" style="display: none;" name="<?php echo WPDKASearch::QUERY_KEY_TYPE; ?>[]" value="<?php echo $format_type; ?>" id="<?php echo WPDKASearch::QUERY_KEY_TYPE .'-'. $format_type; ?>" <?php checked(in_array($format_type,(array)$types)); ?>>
				<i class="<?php echo $args['class']; ?>"></i><?php echo $args['title']; ?><i class="icon-remove-sign"></i>
			</label> 
<?php endforeach; ?>
		</div>
		<div class="span6 filter-container filter-media-type filter-organizations">
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