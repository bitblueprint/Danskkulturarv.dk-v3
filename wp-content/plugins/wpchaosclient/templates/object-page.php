<?php
get_header(); ?>



<article class="container single-material">
    <div class="row">
      <div class="span9">
        <div class="">
          <img src="img/raadhuspladsen.jpg" />
          <h1><?php echo WPChaosClient::get_object()->title; ?></h1>
          <div class="organization"><strong class="strong orange"><?php echo WPChaosClient::get_object()->organization; ?></strong></div>
          <div class="date"><?php echo WPChaosClient::get_object()->published; ?></div>
          <div class="description">
            <?php echo WPChaosClient::get_object()->description; ?>
        </div>
        </div>
      </div>
      <div class="span3">
        <div class="">
          <ul class="nav info">
            <li class="views">Views<strong class="blue pull-right">342</strong></li>
            <li class="share">Del<strong class="blue pull-right">22</strong></li>
            <li class="short-url">kltrv.dk/gt7tG</li>
          </ul>
        </div>
      </div>
    </div>
  </article>

<?php get_footer(); ?>