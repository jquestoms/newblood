<?php
/*
Plugin Name: Grand Portfolio Theme Custom Post Type
Plugin URI: https://themes.themegoods.com/grandportfolio/landing
Description: Plugin that will create custom post types for Grand Portfolio theme.
Version: 2.7.2
Author: ThemeGoods
Author URI: https://themeforest.net/user/ThemeGoods
License: GPLv2
*/

//Setup translation string via PO file

add_action('plugins_loaded', 'tg_load_textdomain');
function tg_load_textdomain() 
{
	load_plugin_textdomain( 'grandportfolio-custom-post', FALSE, dirname( plugin_basename(__FILE__) ) . '/languages/' );
}

if (!defined('ENVATOITEMID'))
{
	define("ENVATOITEMID", 14496218);
}

if (!defined('SHORTNAME'))
{
	define("SHORTNAME", "pp");
}

if(!function_exists('grandportfolio_is_registered'))
{
	function grandportfolio_is_registered() {
		$grandportfolio_is_registered = get_option("envato_purchase_code_".ENVATOITEMID);
		
		if(!empty($grandportfolio_is_registered)) {
			return $grandportfolio_is_registered;
		}
		else {
			return false;
		}
	}
}

$is_verified_envato_purchase_code = false;

//Check if verified
$is_verified_envato_purchase_code = grandportfolio_is_registered();

if($is_verified_envato_purchase_code) {
	function post_type_galleries() {
		//Get custom permalink setting
		$tg_permalink_galleries = get_option(SHORTNAME.'_permalink_galleries');
		if(empty($tg_permalink_galleries)) {
			$tg_permalink_galleries = 'galleries';
		}
		
		$tg_permalink_gallerycat = get_option(SHORTNAME.'_permalink_gallerycat');
		if(empty($tg_permalink_gallerycat)) {
			$tg_permalink_gallerycat = 'gallerycat';
		}
		
		$labels = array(
    		'name' => _x('Galleries', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Gallery', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Gallery', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Gallery', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Gallery', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Gallery', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Gallery', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Gallery', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Gallery found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Gallery found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => array('slug' => $tg_permalink_galleries,'with_front' => false),
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title','editor', 'thumbnail', 'excerpt', 'comments' ),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'galleries', $args );
		
  		$labels = array(			  
  	  	'name' => _x( 'Gallery Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Gallery Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Search Gallery Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Gallery Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Gallery Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Gallery Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Gallery Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Gallery Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Gallery Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Gallery Category Name', 'grandportfolio-custom-post' ),
  		); 							  	  
  		
  		register_taxonomy(
			'gallerycat',
			'galleries',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'gallerycat',
				'show_ui' => true,
				'rewrite' => array( 'slug' => $tg_permalink_gallerycat, 'with_front' => false ),
			)
		);	
	} 
								  	
	add_action('init', 'post_type_galleries');
	
	
	function post_type_portfolios() {
		//Get custom permalink setting
		$tg_permalink_portfolio = get_option(SHORTNAME.'_permalink_portfolio');
		if(empty($tg_permalink_portfolio)) {
			$tg_permalink_speaker = 'portfolios';
		}
		
		$tg_permalink_portfoliocat = get_option(SHORTNAME.'_permalink_portfoliocat');
		if(empty($tg_permalink_portfoliocat)) {
			$tg_permalink_portfoliocat = 'portfoliosets';
		}
		
		$labels = array(
    		'name' => _x('Portfolios', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Portfolio', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Portfolio', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Portfolio', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Portfolio', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Portfolio', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Portfolio', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Portfolios', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Portfolio found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Portfolio found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => array('slug' => $tg_permalink_portfolio,'with_front' => false),
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title','editor', 'thumbnail', 'excerpt'),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'portfolios', $args );
		
  		$labels = array(			  
  	  	'name' => _x( 'Portfolio Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Portfolio Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Search Portfolio Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Portfolio Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Portfolio Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Portfolio Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Portfolio Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Portfolio Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Portfolio Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Portfolio Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'portfoliosets',
			'portfolios',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'portfoliosets',
				'show_ui' => true,
				'rewrite' => array( 'slug' => $tg_permalink_portfoliocat, 'with_front' => false ),
			)
		);		  
	} 
								  	
	add_action('init', 'post_type_portfolios');
	
	
	function post_type_events() {
		$labels = array(
    		'name' => _x('Events', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Event', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Event', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Event', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Event', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Event', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Event', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Events', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Event found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Event found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title','editor', 'thumbnail', 'comments', 'excerpt'),
    		'menu_icon' => ''
		);
		
		$labels = array(			  
  	  	'name' => _x( 'Event Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Event Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Search Event Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Event Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Event Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Event Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Event Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Event Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Event Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Event Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'eventscats',
			'events',
			array(
				'public'=>true,
				'hierarchical' => true,
				'labels'=> $labels,
				'query_var' => 'eventscats',
				'show_ui' => true,
				'rewrite' => array( 'slug' => 'eventscats', 'with_front' => false ),
			)
		);
	
		register_post_type( 'events', $args );			  
	} 
								  	
	add_action('init', 'post_type_events');
	
	
	function post_type_testimonials() {
		$labels = array(
    		'name' => _x('Testimonials', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Testimonial', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Testimonial', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Testimonial', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Testimonial', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Testimonial', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Testimonial', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Testimonial', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Testimonial found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Testimonial found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'testimonials', $args );
		
		$labels = array(			  
  	  	'name' => _x( 'Testimonial Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Testimonial Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Search Testimonial Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Testimonial Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Testimonial Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Testimonial Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Testimonial Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Testimonial Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Testimonial Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Testimonial Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'testimonialcats',
			'testimonials',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'testimonialcats',
				'show_ui' => true,
				'rewrite' => array( 'slug' => 'testimonialcats', 'with_front' => false ),
			)
		);		  
	} 
								  	
	add_action('init', 'post_type_testimonials');
	
	
	function post_type_clients() {
		$labels = array(
    		'name' => _x('Clients', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Client', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add Client', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Client', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Client', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Client', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Client', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Client', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Client found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Client found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title', 'editor', 'thumbnail'),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'clients', $args );
		
		$labels = array(			  
  	  	'name' => _x( 'Client Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Client Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Search Client Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Client Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Client Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Client Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Client Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Client Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Client Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Client Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'clientcats',
			'clients',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'clientcats',
				'show_ui' => true,
				'rewrite' => array( 'slug' => 'clientcats', 'with_front' => false ),
			)
		);
	}
	add_action('init', 'post_type_clients');
	
	
	function post_type_team() {
		$labels = array(
    		'name' => _x('Team Members', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Team Member', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Team Member', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Team Member', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Team Member', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Team Member', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Team Member', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Team Members', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Team Member found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Team Member found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title','editor', 'thumbnail'),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'team', $args );
		
		$labels = array(			  
  	  	'name' => _x( 'Team Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Team Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Team Service Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Team Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Team Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Team Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Team Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Team Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Team Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Team Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'teamcats',
			'team',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'teamcats',
				'show_ui' => true,
				'rewrite' => array( 'slug' => 'teamcats', 'with_front' => false ),
			)
		);
	}
	add_action('init', 'post_type_team');
	
	
	function post_type_pricing() {
		$labels = array(
    		'name' => _x('Pricing', 'post type general name', 'grandportfolio-custom-post'),
    		'singular_name' => _x('Pricing', 'post type singular name', 'grandportfolio-custom-post'),
    		'add_new' => _x('Add New Pricing', 'book', 'grandportfolio-custom-post'),
    		'add_new_item' => esc_html__('Add New Pricing', 'grandportfolio-custom-post'),
    		'edit_item' => esc_html__('Edit Pricing', 'grandportfolio-custom-post'),
    		'new_item' => esc_html__('New Pricing', 'grandportfolio-custom-post'),
    		'view_item' => esc_html__('View Pricing', 'grandportfolio-custom-post'),
    		'search_items' => esc_html__('Search Pricings', 'grandportfolio-custom-post'),
    		'not_found' =>  esc_html__('No Pricing found', 'grandportfolio-custom-post'),
    		'not_found_in_trash' => esc_html__('No Pricing found in Trash', 'grandportfolio-custom-post'), 
    		'parent_item_colon' => ''
		);		
		$args = array(
    		'labels' => $labels,
    		'public' => true,
    		'publicly_queryable' => true,
    		'show_ui' => true, 
    		'query_var' => true,
    		'rewrite' => true,
    		'capability_type' => 'post',
    		'hierarchical' => false,
    		'menu_position' => null,
    		'supports' => array('title'),
    		'menu_icon' => ''
		); 		
	
		register_post_type( 'pricing', $args );
		
		$labels = array(			  
  	  	'name' => _x( 'Pricing Categories', 'taxonomy general name', 'grandportfolio-custom-post' ),
  	  	'singular_name' => _x( 'Pricing Category', 'taxonomy singular name', 'grandportfolio-custom-post' ),
  	  	'search_items' =>  __( 'Pricing Service Categories', 'grandportfolio-custom-post' ),
  	  	'all_items' => __( 'All Pricing Categories', 'grandportfolio-custom-post' ),
  	  	'parent_item' => __( 'Parent Pricing Category', 'grandportfolio-custom-post' ),
  	  	'parent_item_colon' => __( 'Parent Pricing Category:', 'grandportfolio-custom-post' ),
  	  	'edit_item' => __( 'Edit Pricing Category', 'grandportfolio-custom-post' ), 
  	  	'update_item' => __( 'Update Pricing Category', 'grandportfolio-custom-post' ),
  	  	'add_new_item' => __( 'Add New Pricing Category', 'grandportfolio-custom-post' ),
  	  	'new_item_name' => __( 'New Pricing Category Name', 'grandportfolio-custom-post' ),
  		); 							  
  		
  		register_taxonomy(
			'pricingcats',
			'pricing',
			array(
				'public'=>true,
				'hierarchical' => false,
				'labels'=> $labels,
				'query_var' => 'pricingcats',
				'show_ui' => true,
				'rewrite' => array( 'slug' => 'pricingcats', 'with_front' => false ),
			)
		);
	}
	add_action('init', 'post_type_pricing');
	
	
	add_filter( 'manage_posts_columns', 'rt_add_gravatar_col');
	function rt_add_gravatar_col($cols) {
		$cols['thumbnail'] = esc_html__('Thumbnail', 'grandportfolio-custom-post');
		return $cols;
	}
	
	add_action( 'manage_posts_custom_column', 'rt_get_author_gravatar');
	function rt_get_author_gravatar($column_name ) {
		if ( $column_name  == 'thumbnail'  ) {
			echo get_the_post_thumbnail(get_the_ID(), array(100, 100));
		}
	}
	
	/*
		Get gallery list
	*/
	$args = array(
    	'numberposts' => -1,
    	'post_type' => array('galleries'),
	);
	
	$galleries_arr = get_posts($args);
	$galleries_select = array();
	$galleries_select['(Display Post Featured Image)'] = '';
	$galleries_select['(Hide Post Featured Image)'] = -1;
	
	foreach($galleries_arr as $gallery)
	{
		$galleries_select[$gallery->post_title] = $gallery->ID;
	}
	
	/*
		Get post layouts
	*/
	$post_layout_select = array();
	$post_layout_select = array(
		'With Right Sidebar' => 'With Right Sidebar',
		'With Left Sidebar' => 'With Left Sidebar',
		'Fullwidth' => 'Fullwidth',
		'Split Screen' => 'Split Screen',
	);
	
	//Get all sidebars
	$theme_sidebar = array(
		'' => '',
		'Page Sidebar' => 'Page Sidebar', 
		'Contact Sidebar' => 'Contact Sidebar', 
		'Blog Sidebar' => 'Blog Sidebar',
	);
	
	$dynamic_sidebar = get_option('pp_sidebar');
	
	if(!empty($dynamic_sidebar))
	{
		foreach($dynamic_sidebar as $sidebar)
		{
			$theme_sidebar[$sidebar] = $sidebar;
		}
	}
	
	/*
		Begin creating custom fields
	*/
	
	$postmetas = 
		array (
			'post' => array(
				array("section" => "Content Type", "id" => "post_layout", "type" => "select", "title" => "Post Layout", "description" => "You can select layout of this single post page.", "items" => $post_layout_select),
				array(
    			"section" => "Featured Content Type", "id" => "post_ft_type", "type" => "select", "title" => "Featured Content Type", "description" => "Select featured content type for this post. Different content type will be displayed on single post page", 
					"items" => array(
						"Image" => "Image",
						"Gallery" => "Gallery",
						"Vimeo Video" => "Vimeo Video",
						"Youtube Video" => "Youtube Video",
					)),
				
				array("section" => "Gallery", "id" => "post_ft_gallery", "type" => "select", "title" => "Gallery (Optional)", "description" => "Please select a gallery<br/><br/><strong>NOTE: enter if you select \"Gallery\" as Featured Content Type</strong>", "items" => $galleries_select),
					
				array("section" => "Vimeo Video ID", "id" => "post_ft_vimeo", "type" => "text", "title" => "Vimeo Video ID (Optional)", "description" => "Please enter Vimeo Video ID for example 73317780"),
				
				array("section" => "Youtube Video ID", "id" => "post_ft_youtube", "type" => "text", "title" => "Youtube Video ID (Optional)", "description" => "Please enter Youtube Video ID for example 6AIdXisPqHc"),
				
				array("section" => "Page Menu", "id" => "post_menu_transparent", "type" => "checkbox", "title" => "Make Menu Transparent", "description" => "Check this option if you want to display menu in transparent"),
			),
			
			'galleries' => array(
				array("section" => "Gallery Template", "id" => "gallery_template", "type" => "select", "title" => "Gallery Template", "description" => "Select gallery template for this gallery", 
					"items" => array(
						"Gallery Fullscreen Cover" => "Gallery Fullscreen Cover",
						"Gallery Kenburns" => "Gallery Kenburns",
						"Gallery Horizontal" => "Gallery Horizontal",
						"Gallery Horizontal Contained" => "Gallery Horizontal Contained",
						"Gallery Striped" => "Gallery Striped",
						"Gallery Flow" => "Gallery Flow",
						"Gallery Split Screen" => "Gallery Split Screen",
						"Gallery Split Screen Wide" => "Gallery Split Screen Wide",
						"Gallery 1 Column Contained" => "Gallery 1 Column Contained",
						"Gallery 2 Columns Contained" => "Gallery 2 Columns Contained",
						"Gallery 3 Columns Contained" => "Gallery 3 Columns Contained",
						"Gallery 4 Columns Contained" => "Gallery 4 Columns Contained",
						"Gallery Mixed Masonry Contained" => "Gallery Mixed Masonry Contained",
						"Gallery Masonry 2 Columns Contained" => "Gallery Masonry 2 Columns Contained",
						"Gallery Masonry 3 Columns Contained" => "Gallery Masonry 3 Columns Contained",
						"Gallery Masonry 4 Columns Contained" => "Gallery Masonry 4 Columns Contained",
						"Gallery 2 Columns Wide" => "Gallery 2 Columns Wide",
						"Gallery 3 Columns Wide" => "Gallery 3 Columns Wide",
						"Gallery 4 Columns Wide" => "Gallery 4 Columns Wide",
						"Gallery 5 Columns Wide" => "Gallery 5 Columns Wide",
						"Gallery Mixed Masonry Wide" => "Gallery Mixed Masonry Wide",
						"Gallery Masonry 2 Columns Wide" => "Gallery Masonry 2 Columns Wide",
						"Gallery Masonry 3 Columns Wide" => "Gallery Masonry 3 Columns Wide",
						"Gallery Masonry 4 Columns Wide" => "Gallery Masonry 4 Columns Wide",
						"Gallery Masonry 5 Columns Wide" => "Gallery Masonry 5 Columns Wide",
						"Gallery Photo Proofing" => "Gallery Photo Proofing",
					)),
				
				array("section" => "Password Protect", "id" => "gallery_password", "title" => "Password (Optional)", "description" => "Enter your password for this gallery"),
				
				array("section" => "Download", "id" => "gallery_download", "type" => "file", "title" => "Gallery Download (Optional)", "description" => "Upload zip file of your gallery images and it can be download by visitors"),
			),
			
			'portfolios' => array(
				array("section" => "Content Type", "id" => "portfolio_type", "type" => "select", "title" => "Content Type", "description" => "Select content type for this project:", 
					"items" => array(
						"Portfolio Content" => "Portfolio Content",
						"Image" => "Image",
						"Vimeo Video" => "Vimeo Video",
						"Youtube Video" => "Youtube Video", 
						"Self-Hosted Video" => "Self-Hosted Video",
						"External Link" => "External Link",
					)),
					
				array("section" => "Content Type", "id" => "portfolio_hide_image", "type" => "checkbox", "title" => "Do not display Featured Image as Header", "description" => "Check this option if you want to hide featured image on single portfolio page"),
				
				array("section" => "Content Type", "id" => "portfolio_video_id", "title" => "Youtube or Vimeo Video ID (Only if you selected Youtube Video or Vimeo Video)", "description" => "Enter your video ID here:"),
				
				array("section" => "Content Type", "id" => "portfolio_mp4_url", "title" => "Video URL (Only if you selected Self-Hosted)", "description" => "Enter your video URL (.mp4 file format):"),
				
				array("section" => "Content Type", "id" => "portfolio_link_url", "title" => "Link URL", "description" => "Project item will link to this URL"),
				
				array("section" => "Page Menu", "id" => "portfolio_menu_transparent", "type" => "checkbox", "title" => "Make Menu Transparent", "description" => "Check this option if you want to display menu in transparent"),
			),
			
			'clients' => array(
				array("section" => "Client Option", "id" => "client_website_url", "type" => "text", "title" => "Client Website URL", "description" => "Enter client website URL here ex. http://google.com"),
			),
			
			'team' => array(
				array("section" => "Team Option", "id" => "team_position", "type" => "text", "title" => "Position and Role", "description" => "Enter team member position and role ex. Marketing Manager"),
				array("section" => "Facebook URL", "id" => "member_facebook", "type" => "text", "title" => "Facebook URL", "description" => "Enter team member Facebook URL"),
		    	array("section" => "Twitter URL", "id" => "member_twitter", "type" => "text", "title" => "Twitter URL", "description" => "Enter team member Twitter URL"),
		    	array("section" => "Google+ URL", "id" => "member_google", "type" => "text", "title" => "Google+ URL", "description" => "Enter team member Google+ URL"),
		    	array("section" => "Linkedin URL", "id" => "member_linkedin", "type" => "text", "title" => "Linkedin URL", "description" => "Enter team member Linkedin URL"),
			),
			
			'events' => array(
		    	array("section" => "Date", "id" => "event_date", "type" => "date", "title" => "Event Date", "description" => "Select date for this event"),
		    	array("section" => "Date", "id" => "event_date_raw", "type" => "date_raw", "title" => "Event Date Raw", "description" => "Select date for this event"),
		    	array("section" => "Time", "id" => "event_from_time", "type" => "time", "title" => "From", "description" => "Select begin time for this event"),
		    	array("section" => "Time", "id" => "event_to_time", "type" => "time", "title" => "To", "description" => "Select end time for this event"),
		    	
		    	array("section" => "Location", "id" => "event_location", "type" => "textarea", "title" => "Event Location", "description" => "Enter address for this event ex. 34 Kingstong Road, United Kingdom"),
		    	
		    	array("section" => "Location Type", "id" => "event_get_direction", "type" => "checkbox", "title" => "Get Direction on Google Map", "description" => "Check this option if you want to find map with direction using location above (Optional)"),
		    	
		    	array("section" => "Ticket", "id" => "event_ticket_url", "title" => "Buy Ticket URL", "description" => "Enter URL where visitor can buy ticket for this event (Optional)"),
			),
			
			'pricing' => array(
				array("section" => "Pricing Option", "id" => "pricing_featured", "type" => "checkbox", "title" => "Make this pricing featured", "description" => "Check this option if you want to display this pricing as featured one."),
				array("section" => "Pricing Option", "id" => "pricing_plan_currency", "type" => "text", "title" => "Currency", "description" => "Enter currency", "sample" => "$"),
				array("section" => "Pricing Option", "id" => "pricing_plan_price", "type" => "text", "title" => "Exact Price", "description" => "Enter exact price", "sample" => "10"),
				array("section" => "Pricing Option", "id" => "pricing_plan_time", "type" => "text", "title" => "Time", "description" => "Enter price per time (optional)", "sample" => 'month'),
				array("section" => "Pricing Option", "id" => "pricing_plan_features", "type" => "textarea", "title" => "Plan Features", "description" => "Enter pricing plan features.", "sample" => "Unlimited Pages\nUnlimited Storage\nMobile Website\n24/7 Customer Support"),
				array("section" => "Pricing Option", "id" => "pricing_button_text", "type" => "text", "title" => "Button Text", "description" => "Enter pricing button text", "sample" => "Purchase Now"),
		    	array("section" => "Pricing Option", "id" => "pricing_button_url", "type" => "text", "title" => "Button URL", "description" => "Enter pricing button  URL", "sample" => "http://themeforest.net"),
			),
			
			'testimonials' => array(
				array("section" => "Testimonial Option", "id" => "testimonial_name", "type" => "text", "title" => "Customer Name", "description" => "Enter customer name"),
				array("section" => "Testimonial Option", "id" => "testimonial_position", "type" => "text", "title" => "Customer Position", "description" => "Enter customer position in company"),
				array("section" => "Testimonial Option", "id" => "testimonial_company_name", "type" => "text", "title" => "Company Name", "description" => "Enter customer company name"),
				array("section" => "Testimonial Option", "id" => "testimonial_company_website", "type" => "text", "title" => "Company Website URL", "description" => "Enter customer company website URL"),
			),
	);
	
	function create_meta_box() {
	
		global $postmetas;
		
		if(!isset($_GET['post_type']) OR empty($_GET['post_type']))
		{
			if(isset($_GET['post']) && !empty($_GET['post']))
			{
				$post_obj = get_post($_GET['post']);
				$_GET['post_type'] = $post_obj->post_type;
			}
			else
			{
				$_GET['post_type'] = 'post';
			}
		}
		
		if ( function_exists('add_meta_box') && isset($postmetas) && count($postmetas) > 0 ) {  
			foreach($postmetas as $key => $postmeta)
			{
				if($_GET['post_type']==$key && !empty($postmeta))
				{
					if($key != 'pricing')
					{
						add_meta_box( 'metabox', ucfirst($key).' Options', 'new_meta_box', $key, 'side', 'high' );
					}
					else
					{
						add_meta_box( 'metabox', ucfirst($key).' Options', 'new_meta_box', $key, 'normal', 'high' );
					}
				}
			}
		}
	
	}  
	
	function new_meta_box() {
		global $post, $postmetas;
		
		if(!isset($_GET['post_type']) OR empty($_GET['post_type']))
		{
			if(isset($_GET['post']) && !empty($_GET['post']))
			{
				$post_obj = get_post($_GET['post']);
				$_GET['post_type'] = $post_obj->post_type;
			}
			else
			{
				$_GET['post_type'] = 'post';
			}
		}
	
		echo '<input type="hidden" name="pp_meta_form" id="pp_meta_form" value="' . wp_create_nonce( plugin_basename(__FILE__) ) . '" />';
		
		$meta_section = '';
	
		foreach ( $postmetas as $key => $postmeta ) {
		
			if($_GET['post_type'] == $key)
			{
			
				foreach ( $postmeta as $postmeta_key => $each_meta ) {
			
					$meta_id = $each_meta['id'];
					$meta_title = $each_meta['title'];
					$meta_description = $each_meta['description'];
					
					if(isset($postmeta['section']))
					{
						$meta_section = $postmeta['section'];
					}
					
					$meta_type = '';
					if(isset($each_meta['type']))
					{
						$meta_type = $each_meta['type'];
					}
					
					echo '<div id="post_option_'.strtolower($each_meta['id']).'">';
					echo "<br/><strong>".$meta_title."</strong><hr class='pp_widget_hr'/>";
					echo "<div class='pp_widget_description'>$meta_description</div>";
					
					if ($meta_type == 'checkbox') {
						$checked = get_post_meta($post->ID, $meta_id, true) == '1' ? "checked" : "";
						echo "<br style='clear:both'><input type='checkbox' name='$meta_id' id='$meta_id' class='iphone_checkboxes' value='1' $checked /><br style='clear:both'><br/>";
					}
					else if ($meta_type == 'select') {
						echo "<p><select name='$meta_id' id='$meta_id'>";
						
						if(!empty($each_meta['items']))
						{
							foreach ($each_meta['items'] as $key => $item)
							{
								echo '<option value="'.$item.'"';
								
								if($item == get_post_meta($post->ID, $meta_id, true))
								{
									echo ' selected ';
								}
								
								echo '>'.$key.'</option>';
							}
						}
						
						echo "</select></p>";
					}
					else if ($meta_type == 'file') { 
				    	echo "<p><input type='text' name='$meta_id' id='$meta_id' class='' value='".get_post_meta($post->ID, $meta_id, true)."' style='width:89%' /><input id='".$meta_id."_button' name='".$meta_id."_button' type='button' value='Upload' class='metabox_upload_btn button' readonly='readonly' rel='".$meta_id."' style='margin:7px 0 0 5px' /></p>";
					}
					else if ($meta_type == 'textarea') {
						if(isset($postmeta[$postmeta_key]['sample']))
						{
							echo "<p><textarea name='$meta_id' id='$meta_id' class=' hint' style='width:100%' rows='7' title='".$postmeta[$postmeta_key]['sample']."'>".get_post_meta($post->ID, $meta_id, true)."</textarea></p>";
						}
						else
						{
							echo "<p><textarea name='$meta_id' id='$meta_id' class='' style='width:100%' rows='7'>".get_post_meta($post->ID, $meta_id, true)."</textarea></p>";
						}
					}			
					else {
						if(isset($postmeta[$postmeta_key]['sample']))
						{
							echo "<p><input type='text' name='$meta_id' id='$meta_id' class='' title='".$postmeta[$postmeta_key]['sample']."' value='".get_post_meta($post->ID, $meta_id, true)."' style='width:99%' /></p>";
						}
						else
						{
							echo "<p><input type='text' name='$meta_id' id='$meta_id' class='' value='".get_post_meta($post->ID, $meta_id, true)."' style='width:99%' /></p>";
						}
					}
					
					echo '</div>';
				}
			}
		}
		
		echo '<br/>';
	
	}
	
	function save_postdata( $post_id ) {
	
		global $postmetas;
	
		// verify this came from the our screen and with proper authorization,
		// because save_post can be triggered at other times
	
		if ( isset($_POST['pp_meta_form']) && !wp_verify_nonce( $_POST['pp_meta_form'], plugin_basename(__FILE__) )) {
			return $post_id;
		}
	
		// verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
	
		if ((defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) || (defined('DOING_AJAX') && DOING_AJAX) || isset($_REQUEST['bulk_edit']))
        	return;
	
		// Check permissions
	
		if ( isset($_POST['post_type']) && 'page' == $_POST['post_type'] ) {
			if ( !current_user_can( 'edit_page', $post_id ) )
				return $post_id;
			} else {
			if ( !current_user_can( 'edit_post', $post_id ) )
				return $post_id;
		}
	
		// OK, we're authenticated
	
		if ( $parent_id = wp_is_post_revision($post_id) )
		{
			$post_id = $parent_id;
		}
		
		if (isset($_POST['pp_meta_form'])) 
		{
			//If import page content builder
			if(is_admin() && isset($_POST['ppb_import_current']) && !empty($_POST['ppb_import_current']))
			{
				//If upload import builder file
				if(isset($_FILES['ppb_import_current_file']['name']) && !empty($_FILES['ppb_import_current_file']['name']))
				{
					//Check if zip file
					$import_filename = $_FILES['ppb_import_current_file']['name'];
					$import_type = $_FILES['ppb_import_current_file']['type'];
					$is_zip = FALSE;
					$new_filename = basename($import_filename, '_.zip');
					
					$accepted_types = array('application/zip', 
	                                	'application/x-zip-compressed', 
	                                	'multipart/x-zip', 
	                                	'application/s-compressed');
	 	
			    	foreach($accepted_types as $mime_type) {
			        	if($mime_type == $import_type) {
			            	$is_zip = TRUE;
			            	break;
			        	} 
			    	}
				}
				//If import demo pages
				else if(isset($_POST['ppb_import_demo_file']) && !empty($_POST['ppb_import_demo_file']))
				{
					$is_zip = FALSE;
				}
				
				if($is_zip)
				{
					WP_Filesystem();
					$upload_dir = wp_upload_dir();
					$cache_dir = '';
					
					if(isset($upload_dir['basedir']))
					{
						$cache_dir = $upload_dir['basedir'].'/meteors';
					}
					
					move_uploaded_file($_FILES["ppb_import_current_file"]["tmp_name"], $cache_dir.'/'.$import_filename);
					//$unzipfile = unzip_file( $cache_dir.'/'.$import_filename, $cache_dir);
					
					$zip = new ZipArchive();
					$x = $zip->open($cache_dir.'/'.$import_filename);
					
					for($i = 0; $i < $zip->numFiles; $i++) {
			        	$new_filename = $zip->getNameIndex($i);
			        	break;
			    	}  
					
					if ($x === true) {
						$zip->extractTo($cache_dir); 
						$zip->close();
					}
	
					$import_options_json = file_get_contents($cache_dir.'/'.$new_filename);
					unlink($cache_dir.'/'.$import_filename);
					unlink($cache_dir.'/'.$new_filename);
				}
				else
				{
					if(isset($_POST['ppb_import_demo_file']) && !empty($_POST['ppb_import_demo_file']))
					{
						$import_options_json = file_get_contents(get_template_directory().'/cache/demos/pages/'.$_POST['ppb_import_demo_file']);
					}
					else
					{
						//If .json file then import
						$import_options_json = file_get_contents($_FILES["ppb_import_current_file"]["tmp_name"]);
					}
				}
				
				$import_options_arr = json_decode($import_options_json, true);
				
				if(isset($import_options_arr['ppb_form_data_order'][0]) && !empty($import_options_arr['ppb_form_data_order'][0]))
				{
					grandportfolio_page_update_custom_meta($post_id, $import_options_arr['ppb_form_data_order'][0], 'ppb_form_data_order');
				}
				
				$ppb_item_arr = explode(',', $import_options_arr['ppb_form_data_order'][0]);
				
				if(is_array($ppb_item_arr) && !empty($ppb_item_arr))
				{
					foreach($ppb_item_arr as $key => $ppb_item_arr)
					{
						if(isset($import_options_arr[$ppb_item_arr.'_data'][0]) && !empty($import_options_arr[$ppb_item_arr.'_data'][0]))
						{
							grandportfolio_page_update_custom_meta($post_id, $import_options_arr[$ppb_item_arr.'_data'][0], $ppb_item_arr.'_data');
						}
						
						if(isset($import_options_arr[$ppb_item_arr.'_size'][0]) && !empty($import_options_arr[$ppb_item_arr.'_size'][0]))
						{
							grandportfolio_page_update_custom_meta($post_id, $import_options_arr[$ppb_item_arr.'_size'][0], $ppb_item_arr.'_size');
						}
					}
				}
				
				header("Location: ".$_SERVER['HTTP_REFERER']);
				exit;
			}
		
			//If export page content builder
			if(is_admin() && isset($_POST['ppb_export_current']) && !empty($_POST['ppb_export_current']))
			{
				$page_title = get_the_title($post_id);
			
				$json_file_name = THEMENAME.'-Page-'.sanitize_title($page_title).'-Export-'.date('m-d-Y_hia');
				$json_file_name = strtolower($json_file_name);
		
				header('Content-disposition: attachment; filename='.$json_file_name.'.json');
				header('Content-type: application/json');
				
				//Get current content builder data
				$ppb_form_data_order = get_post_meta($post_id, 'ppb_form_data_order');
				$export_options_arr = array();
				
				if(!empty($ppb_form_data_order))
				{
					$export_options_arr['ppb_form_data_order'] = $ppb_form_data_order;
	
					//Get each builder module data
					$ppb_form_item_arr = explode(',', $ppb_form_data_order[0]);
				
					foreach($ppb_form_item_arr as $key => $ppb_form_item)
					{
						$ppb_form_item_data = get_post_meta($post_id, $ppb_form_item.'_data');
						$export_options_arr[$ppb_form_item.'_data'] = $ppb_form_item_data;
						
						$ppb_form_item_size = get_post_meta($post_id, $ppb_form_item.'_size');
						$export_options_arr[$ppb_form_item.'_size'] = $ppb_form_item_size;
					}
				}
			
				echo json_encode($export_options_arr);
				
				exit;
			}
		
			foreach ( $postmetas as $postmeta ) {
				foreach ( $postmeta as $each_meta ) {
			
					if (isset($_POST[$each_meta['id']]) && $_POST[$each_meta['id']]) {
						update_custom_meta($post_id, $_POST[$each_meta['id']], $each_meta['id']);
					}
					
					if (isset($_POST[$each_meta['id']]) && $_POST[$each_meta['id']] == "") {
						delete_post_meta($post_id, $each_meta['id']);
					}
					
					if (!isset($_POST[$each_meta['id']])) {
						delete_post_meta($post_id, $each_meta['id']);
					}
				
				}
			}
		
			// Saving Page Builder Data
			if(isset($_POST['ppb_enable']) && !empty($_POST['ppb_enable']))
			{
		    	update_custom_meta($post_id, $_POST['ppb_enable'], 'ppb_enable');
			}
			else
			{
		    	delete_post_meta($post_id, 'ppb_enable');
			}
			
			if(isset($_POST['ppb_form_data_order']) && !empty($_POST['ppb_form_data_order']))
			{
		    	update_custom_meta($post_id, $_POST['ppb_form_data_order'], 'ppb_form_data_order');
		    	
		    	$ppb_item_arr = explode(',', $_POST['ppb_form_data_order']);
		    	if(is_array($ppb_item_arr) && !empty($ppb_item_arr))
		    	{
		    		foreach($ppb_item_arr as $key => $ppb_item_arr)
		    		{
		    			if(isset($_POST[$ppb_item_arr.'_data']) && !empty($_POST[$ppb_item_arr.'_data']))
		    			{
		    				update_custom_meta($post_id, $_POST[$ppb_item_arr.'_data'], $ppb_item_arr.'_data');
		    			}
		    			
		    			if(isset($_POST[$ppb_item_arr.'_size']) && !empty($_POST[$ppb_item_arr.'_size']))
		    			{
		    				update_custom_meta($post_id, $_POST[$ppb_item_arr.'_size'], $ppb_item_arr.'_size');
		    			}
		    		}
		    	}
			}
			//If content builder is empty
			else
			{
		    	update_custom_meta($post_id, '', 'ppb_form_data_order');
			}
			
			//If enable Content Builder then also copy its content to standard page content
			if (isset($_POST['ppb_enable']) && !empty($_POST['ppb_enable']) && ! wp_is_post_revision( $post_id ) )
			{
				//unhook this function so it doesn't loop infinitely
				remove_action('save_post', 'save_postdata');
			
				//update the post, which calls save_post again
				$ppb_page_content = grandportfolio_apply_builder($post_id, 'portfolios', FALSE);
				
				$current_post = array (
		      	'ID'           => $post_id,
		      	'post_content' => $ppb_page_content,
		    	);
		    	
		    	wp_update_post($current_post);
		    	if (is_wp_error($post_id)) {
					$errors = $post_id->get_error_messages();
					foreach ($errors as $error) {
						echo esc_html($error);
					}
				}
		
				//re-hook this function
				add_action('save_post', 'save_postdata');
			}
		}
	}
	
	function update_custom_meta($postID, $newvalue, $field_name) {
	
		if (isset($_POST['pp_meta_form'])) 
		{
			if (!get_post_meta($postID, $field_name)) {
				add_post_meta($postID, $field_name, $newvalue);
			} else {
				update_post_meta($postID, $field_name, $newvalue);
			}
		}
	
	}
	
	//init
	
	add_action('admin_menu', 'create_meta_box'); 
	add_action('save_post', 'save_postdata'); 
	
	/*
		End creating custom fields
	*/
	
	//Include gallery admin interface
	include_once (plugin_dir_path( __FILE__ ) . "/gallery/tg-gallery.php");
	
	//Include Theme Shortcode
	include_once (plugin_dir_path( __FILE__ ) . "tg-shortcode.php");
	
	//Include Content Builder Shortcode
	include_once (plugin_dir_path( __FILE__ ) . "tg-contentbuilder.php");
}
?>