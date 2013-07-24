<?php
/**
 * @package WP Chaos Search
 * @version 1.0
 */
?>
<form method="GET" action="<?php echo $page; ?>" class="span12">
	<div class="input-append">
		<input class="span11" id="appendedInputButton" type="text" name="<?php echo WPChaosSearch::QUERY_KEY_FREETEXT; ?>" value="<?php echo WPChaosSearch::get_search_var(self::QUERY_KEY_FREETEXT, 'esc_attr'); ?>" placeholder="<?php echo $freetext_placeholder; ?>" /><button type="submit" class="btn btn-large btn-search">Søg</button>
	</div>
</form>