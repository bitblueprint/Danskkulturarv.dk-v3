<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>

		<div><i class="icon-eye-open"></i> <?php _e('Views','dka'); ?> <strong class="pull-right"><?php echo WPChaosClient::get_object()->views; ?></strong></div>
<?php if(WPChaosClient::get_object()->externalurl) : ?>
		<div class="external-link-container"><i class="icon-external-link"></i> <a target="_blank" href="<?php echo WPChaosClient::get_object()->externalurl; ?>" title="<?php printf(__('Read more at %s','dka'),WPChaosClient::get_object()->organization); ?>"><?php printf(__('Read more at %s','dka'),WPChaosClient::get_object()->organization); ?></a></div>
<?php endif; ?>
		<hr>
		<div class="rights-container"><?php echo WPChaosClient::get_object()->rights; ?></div>
		<hr>
		<div class="social"><?php dka_social_share(array("link"=>WPChaosClient::get_object()->url)); ?></div>
		<hr>

		<h4><?php _e('Tags','dka'); ?></h4>
		<?php echo WPChaosClient::get_object()->tags; ?>

<?php
//iff status is active or frozen
if(intval(get_option('wpdkatags-status')) > 0) : ?>
		<h4><?php _e('User Tags','wpdkatags'); ?></h4>
		<?php echo WPChaosClient::get_object()->usertags; ?>
<?php endif; ?>
