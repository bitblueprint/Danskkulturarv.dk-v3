<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
<h1><?php echo WPChaosClient::get_object()->title; ?></h1>
<div class="organization"><strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong></div>
<p class="date"><?php echo WPChaosClient::get_object()->published; ?></p>
<div class="description"><?php echo WPChaosClient::get_object()->description; ?></div>
