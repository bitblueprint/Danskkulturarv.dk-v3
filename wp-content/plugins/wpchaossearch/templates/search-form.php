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
		<button class="btn btn-white btn-large btn-block btn-advanced-search collapsed" type="button" data-toggle="collapse" href="#advanced-search-container">Præciser søgning</button>
	</div>
</form>
