

<?php
$format_types = array(
	array(
		'type' => 'sound',
		'class' => 'icon-volume-up',
		'title' => 'Lyd',
	),
	array(
		'type' => 'video',
		'class' => 'icon-film',
		'title' => 'Video',
	),
	array(
		'type' => 'document',
		'class' => 'icon-file-text',
		'title' => 'Dokumenter',
	),
	array(
		'type' => 'image',
		'class' => 'icon-picture',
		'title' => 'Billeder',
	),
);

$types = WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_TYPE);
$advanced_search_expanded = (!empty($types) ? " in" : "");
?>
<form method="GET" action="<?php echo $page; ?>" class="span12">
	<div class="input-append">
		<input class="span7" id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo WPChaosSearch::get_search_var(WPChaosSearch::QUERY_KEY_FREETEXT, 'esc_attr'); ?>" placeholder="<?php echo $freetext_placeholder; ?>" /><button type="submit" class="btn btn-large btn-search">Søg</button>
	</div>
	<div class="btn-group span4 pull-right btn-advanced-search-container">
		<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed blue" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning <i class="icon-angle-down pull-right">&nbsp;&nbsp;&nbsp;</i></button>
	</div>
	<div id="advanced-search-container" class="container row collapse<?php echo $advanced_search_expanded; ?>">
	  
	    <div class="span3 filter-container filter-media-type">
	      <label type="button" class="btn filter-btn filter-btn-all" value="dr" name="dr-name">Alle Typer<i class="icon-ok"></i></label>
	      <hr>
	      <!-- Chage the inline CSS property style="opacity:0.5;" to display: none; when done debugging. -->
	    <?php foreach($format_types as $format) : ?>
	      <label for="type-<?php echo $format['type']; ?>" class="btn filter-btn filter-btn-single"><input type="checkbox" style="display: none;" name="<?php echo WPChaosSearch::QUERY_KEY_TYPE; ?>[]" value="<?php echo $format['type']; ?>" id="type-<?php echo $format['type']; ?>" <?php checked(in_array($format['type'],(array)$types)); ?>><i class="<?php echo $format['class']; ?>"></i><?php echo $format['title']; ?><i class="icon-remove-sign"></i></label> 
	  	<?php endforeach; ?>

	    </div>

	    <div class="span6 filter-container filter-media-type filter-organizations" data-toggle="buttons-checkbox">
	      <button type="button" class="btn filter-btn filter-btn-all active" value="dr" name="dr-name">Alle Organisationer<i class="enabled"></i></button>
	      <hr>
	      <button type="button" class="btn filter-btn filter-btn-single" value="dr" name="dr-name">DR<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Det Danske Filminstitut<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Det Kongelige Bibliotek<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">National Museum<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Det kongelige Bibliotek<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Statens Museum for Kunst<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Nationalt Museum<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single">Kulturstyrelsen<i class="enabled"></i></button>
	    </div>
	</div>
</form>