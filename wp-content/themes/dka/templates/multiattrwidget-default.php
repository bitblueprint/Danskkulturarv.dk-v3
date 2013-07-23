<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
<h1><?php echo WPChaosClient::get_object()->title; ?></h1>
<i class="icon-film"></i>&nbsp;<span class="organization"><strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong></span><i class="icon-calendar"></i>
<p class="date"><?php echo WPChaosClient::get_object()->published; ?></p>
<div class="description"><?php echo WPChaosClient::get_object()->description; ?></div>