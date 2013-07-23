<?php
/**
 * @package WordPress
 * @subpackage DKA
 */
?>
<div style="width:100%; background-color:#ccff00; height:400px;">Temporary clip placeholder</div>
<h1><?php echo WPChaosClient::get_object()->title; ?></h1>
<i class="icon-film"></i>&nbsp;<span class="organization"><strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong></span>&nbsp;&nbsp;<i class="icon-calendar"></i>&nbsp;<span class="date"><?php echo WPChaosClient::get_object()->published; ?></span>
<hr></hr><div class="description"><p><strong>Beskrivelse</strong></p><?php echo WPChaosClient::get_object()->description; ?></div>