<?php
/**
 * Template Name: Righteous Yield Master Grid
 * Description: Custom TRYL shop grid with theme-switching support, 3D tilt, and AJAX mini-cart integration.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

get_header();

echo '<main class="tryl-shop-template-wrapper">';
echo do_shortcode('[tryl_3d_shop]');
echo '</main>';

get_footer();
?>
