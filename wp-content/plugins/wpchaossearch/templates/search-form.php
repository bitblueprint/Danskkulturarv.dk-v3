<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>

<form method="GET" action="<?php echo $page; ?>" class="span12">
	<div class="input-append">
		<input class="span7" id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo $freetext; ?>" placeholder="<?php echo $placeholder; ?>" /><button type="submit" class="btn btn-large btn-search">Søg</button>
	</div>
	<div class="btn-group span4 pull-right btn-advanced-search-container">
		<button class="btn btn-white btn-large btn-block btn-advanced-search" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning</button>
	</div>
	<div id="advanced-search-container" class="container row collapse">
	  
	    <div class="span3 filter-container filter-media-type" data-toggle="buttons-checkbox">
	      <button type="button" class="btn filter-btn filter-btn-all active" value="dr" name="dr-name">Alle Typer<i class="enabled"></i></button>
	      <hr>
	      <button type="button" class="btn filter-btn filter-btn-single" value="dr" name="dr-name"><i class="audio"></i>Lyd<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single"><i class="video"></i>Video<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single"><i class="documents"></i>Dokumenter<i class="enabled"></i></button>
	      <button type="button" class="btn filter-btn filter-btn-single"><i class="images"></i>Billeder<i class="enabled"></i></button>
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
