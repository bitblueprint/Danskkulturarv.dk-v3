<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>

		<div><i class="icon-eye-open"></i> Visninger <strong class="pull-right"><?php echo WPChaosClient::get_object()->views; ?></strong></div>
<?php if(WPChaosClient::get_object()->externalurl) : ?>
		<div><i class="icon-external-link"></i> <a target="_blank" href="<?php echo WPChaosClient::get_object()->externalurl; ?>" title="Læs mere hos <?php echo WPChaosClient::get_object()->organization; ?>">Læs mere hos <?php echo WPChaosClient::get_object()->organization; ?></a></div>
<?php endif; ?>
		<hr>
		<div class="rights-container"><?php echo WPChaosClient::get_object()->rights; ?></div>
		<hr>
		<div class="social"><?php dka_social_share(array("link"=>WPChaosClient::get_object()->url)); ?></div>
		<hr>

		<h4>Tags</h4>
		<?php echo WPChaosClient::get_object()->tags; ?>

