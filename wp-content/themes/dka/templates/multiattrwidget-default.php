<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
<h1><?php echo WPChaosClient::get_object()->title; ?></h1>
<i title="<?php echo WPChaosClient::get_object()->type_title; ?>" class="<?php echo WPChaosClient::get_object()->type_class; ?>"></i>&nbsp;
<span class="organization">
	<strong class="strong orange">
		<?php echo WPChaosClient::get_object()->organization; ?>
	</strong>
</span>&nbsp;&nbsp;
<i class="icon-calendar"></i>&nbsp;
<span class="date"><?php echo WPChaosClient::get_object()->published; ?></span>
<hr></hr>
<div class="description">
	<p><strong>Beskrivelse</strong></p>
	<?php echo WPChaosClient::get_object()->description; ?>
</div>