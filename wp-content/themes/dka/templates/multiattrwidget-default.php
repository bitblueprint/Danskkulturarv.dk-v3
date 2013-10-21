<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
<h1><?php echo WPChaosClient::get_object()->title; ?></h1>
<i title="<?php echo WPChaosClient::get_object()->type_title; ?>" class="<?php echo WPChaosClient::get_object()->type_class; ?>"></i>&nbsp;
<strong class="organization">
<?php if(WPChaosClient::get_object()->organization_link) : ?>
	<a class="strong" href="<?php echo WPChaosClient::get_object()->organization_link; ?>" title="<?php echo esc_attr(WPChaosClient::get_object()->organization); ?>"><?php echo WPChaosClient::get_object()->organization; ?></a>
<?php else : ?>
	<span class="strong"><?php echo WPChaosClient::get_object()->organization; ?></span>
<?php endif; ?>
</strong>
<?php if(WPChaosClient::get_object()->published) : ?>
&nbsp;&nbsp;<i class="icon-calendar"></i>&nbsp;	
<span class="date"><?php echo WPChaosClient::get_object()->published; ?></span>
<?php endif; ?>
<hr>
<div class="description">
	<h2><strong><?php _e('Description', 'dka'); ?></strong></h2>
	<?php echo WPChaosClient::get_object()->description; ?>
</div>
<div class="colofon">
	<h2><strong><?php _e('Colofon', 'dka'); ?></strong></h2>
	<?php echo WPChaosClient::get_object()->creator; ?>
	<?php echo WPChaosClient::get_object()->contributor; ?>
</div>
<!--<div class="contributors">
	<h2><strong><?php _e('Contributors', 'dka'); ?></strong></h2>
	<?php echo WPChaosClient::get_object()->contributor; ?>
</div>-->
