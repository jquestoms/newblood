<?php
assert_options( ASSERT_EXCEPTION, 1 );

// Minimal WP-function stubs so pure-logic module files run under plain PHP CLI.
// Existence-only stub: config.php checks defined('ABSPATH'), not its value.
if ( ! defined( 'ABSPATH' ) ) define( 'ABSPATH', __DIR__ . '/' );
if ( ! function_exists( 'sanitize_text_field' ) ) {
    function sanitize_text_field( $s ) { return trim( preg_replace( '/[\r\n\t]+/', ' ', strip_tags( (string) $s ) ) ); }
}
if ( ! function_exists( 'sanitize_textarea_field' ) ) {
    function sanitize_textarea_field( $s ) { return trim( strip_tags( (string) $s ) ); }
}
if ( ! function_exists( 'sanitize_email' ) ) {
    function sanitize_email( $s ) { return filter_var( trim( (string) $s ), FILTER_SANITIZE_EMAIL ); }
}
if ( ! function_exists( 'esc_html' ) ) { function esc_html( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
if ( ! function_exists( 'esc_attr' ) ) { function esc_attr( $s ) { return htmlspecialchars( (string) $s, ENT_QUOTES ); } }
if ( ! function_exists( 'absint' ) ) { function absint( $n ) { return abs( (int) $n ); } }
if ( ! function_exists( 'add_action' ) ) { function add_action() {} }
if ( ! function_exists( 'sanitize_title' ) ) { function sanitize_title( $s ) { return strtolower( trim( preg_replace( '/[^a-z0-9-]/', '-', strtolower( (string) $s ) ) ) ); } }
if ( ! function_exists( 'is_email' ) ) { function is_email( $s ) { return (bool) filter_var( $s, FILTER_VALIDATE_EMAIL ); } }
