<?php
/**
* Enqueues child theme stylesheet, loading first the parent theme stylesheet.
*/
function newbloodchildtheme_custom_enqueue_child_theme_styles() {
wp_enqueue_style( 'parent-theme-css', get_template_directory_uri() . '/style.css' );
}
add_action( 'wp_enqueue_scripts', 'newbloodchildtheme_custom_enqueue_child_theme_styles' );

add_action( 'action_scheduler_run_queue', 'act_sched_function' );
function act_sched_function() {
	ActionScheduler::runner()->run();
}