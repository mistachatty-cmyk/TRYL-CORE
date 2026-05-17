<?php
/**
 * Template Name: Righteous Yield Master Grid
 * Description: Updated to use the core TRYL Premium E-Commerce shortcode system.
 */

get_header(); 

echo '<main class="tryl-shop-template-wrapper" style="min-height: 80vh;">';
echo do_shortcode('[tryl_3d_shop]');
echo '</main>';

get_footer();
?>
