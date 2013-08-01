<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>

	<li>
		<div>Visninger <span class="pull-right"><strong><?php echo WPChaosClient::get_object()->views; ?></strong></span></div>
		<div><?php echo WPChaosClient::get_object()->rights; ?></div>
		<div class="social"><?php dka_social_share(array("link"=>WPChaosClient::get_object()->url)); ?></div>
	</li>
	<li>
		<h4>Tags</h4>
		<?php echo WPChaosClient::get_object()->tags; ?>
	</li>
