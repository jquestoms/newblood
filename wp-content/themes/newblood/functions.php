<?php
/**
 * New Blood Theme Functions
 */

if ( ! defined( 'ABSPATH' ) ) exit;

define( 'NEWBLOOD_VERSION', '1.0.0' );

/**
 * Enqueue theme styles and scripts
 */
function newblood_enqueue_assets() {
    wp_enqueue_style(
        'newblood-animations',
        get_template_directory_uri() . '/assets/css/animations.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_style(
        'newblood-patterns',
        get_template_directory_uri() . '/assets/css/patterns.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_style(
        'newblood-utilities',
        get_template_directory_uri() . '/assets/css/utilities.css',
        array(),
        NEWBLOOD_VERSION
    );
    wp_enqueue_script(
        'newblood-scroll-reveal',
        get_template_directory_uri() . '/assets/js/scroll-reveal.js',
        array(),
        NEWBLOOD_VERSION,
        true
    );
    wp_enqueue_script(
        'newblood-gradient-mesh',
        get_template_directory_uri() . '/assets/js/gradient-mesh.js',
        array(),
        NEWBLOOD_VERSION,
        true
    );
}
add_action( 'wp_enqueue_scripts', 'newblood_enqueue_assets' );

/**
 * Register block patterns
 */
function newblood_register_pattern_categories() {
    register_block_pattern_category( 'newblood', array(
        'label' => __( 'New Blood', 'newblood' ),
    ) );
    register_block_pattern_category( 'newblood-pages', array(
        'label' => __( 'New Blood Pages', 'newblood' ),
    ) );
}
add_action( 'init', 'newblood_register_pattern_categories' );

/**
 * Theme setup
 */
function newblood_setup() {
    add_theme_support( 'wp-block-styles' );
    add_theme_support( 'editor-styles' );
    add_theme_support( 'responsive-embeds' );
    add_theme_support( 'woocommerce' );
}
add_action( 'after_setup_theme', 'newblood_setup' );
