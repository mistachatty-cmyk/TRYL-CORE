<?php
/**
 * TRYL Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

/**
 * Sets up theme defaults and registers support for various WordPress features.
 *
 * Note that this function is hooked into the after_setup_theme hook, which
 * runs before the init hook.
 *
 * @return void
 */
if ( ! function_exists( 'tryl_theme_setup' ) ) {
function tryl_theme_setup() {
    add_theme_support( 'title-tag' );
    add_theme_support( 'post-thumbnails' );
    add_theme_support( 'custom-logo', array(
        'height'      => 80,
        'width'       => 250,
        'flex-width'  => true,
        'flex-height' => true,
    ) );
}
}
add_action( 'after_setup_theme', 'tryl_theme_setup' );

/**
 * Enqueues the theme's stylesheets and scripts.
 *
 * Includes GSAP and ScrollTrigger for custom animations.
 *
 * @return void
 */
if ( ! function_exists( 'tryl_enqueue_assets' ) ) {
function tryl_enqueue_assets() {
    wp_enqueue_style( 'tryl-style', get_stylesheet_uri(), array(), '1.0.0' );
    wp_enqueue_script( 'gsap', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/gsap.min.js', array(), null, true );
    wp_enqueue_script( 'scrolltrigger', 'https://cdnjs.cloudflare.com/ajax/libs/gsap/3.12.2/ScrollTrigger.min.js', array('gsap'), null, true );
    wp_enqueue_script( 'tryl-script', get_theme_file_uri('script.js'), array('gsap', 'scrolltrigger'), '1.0.0', true );
}
}
add_action( 'wp_enqueue_scripts', 'tryl_enqueue_assets' );

if ( ! function_exists( 'tryl_register_core_product_categories' ) ) {
function tryl_register_core_product_categories() {
    $categories = array( 'Men', 'Women', 'Kids', 'Hats & Accessories' );
    foreach ( $categories as $category ) {
        if ( ! term_exists( $category, 'product_cat' ) ) {
            wp_insert_term( $category, 'product_cat', array(
                'description' => 'The Righteous Yield Life - ' . $category,
                'slug'        => sanitize_title( $category )
            ));
        }
    }
}
} // endif function_exists( 'tryl_register_core_product_categories' )
if ( ! has_action( 'init', 'tryl_register_core_product_categories' ) ) {
    add_action( 'init', 'tryl_register_core_product_categories' );
}
