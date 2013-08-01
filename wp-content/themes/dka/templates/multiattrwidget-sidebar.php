<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>

	<li>
		<div><i class="icon-eye-open"></i> Visninger <strong class="pull-right"><?php echo WPChaosClient::get_object()->views; ?></strong></div>
		<div><i class="icon-external-link"></i> <a target="_blank" href="<?php echo WPChaosClient::get_object()->externalurl; ?>" title="Læs original artikel om <?php echo WPChaosClient::get_object()->title; ?>">Læs original artikel</a></div>
		<hr>
		<div><?php echo WPChaosClient::get_object()->rights; ?></div>
		<hr>
		<div class="social"><?php dka_social_share(array("link"=>WPChaosClient::get_object()->url)); ?></div>
		<hr>
	</li>
	<li>
		<h4>Tags</h4>
		<?php echo WPChaosClient::get_object()->tags; ?>
	</li>
