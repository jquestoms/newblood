<?php
require_once get_template_directory() . "/modules/class-tgm-plugin-activation.php";
add_action( 'tgmpa_register', 'grandportfolio_require_plugins' );
 
function grandportfolio_require_plugins() {
 
    $plugins = array(
	    array(
	        'name'               => 'Grand Portfolio Theme Custom Post Type',
	        'slug'               => 'grandportfolio-custom-post',
	        'source'             => 'https://themegoods-assets.b-cdn.net/grandportfolio-custom-post/grandportfolio-custom-post-v2.7.2.zip',
	        'required'           => true, 
	        'version'            => '2.7.2',
	    ),
	    array(
			'name'               => 'Revolution Slider',
			'slug'               => 'revslider',
			'source'             => 'https://themegoods-assets.b-cdn.net/revslider/revslider-v6.6.10.zip',
			'required'           => true, 
			'version'            => '6.6.10',
		),
	    array(
			'name'               => 'Envato Market',
			'slug'               => 'envato-market',
			'source'             => 'https://themegoods-assets.b-cdn.net/envato-market/envato-market-v2.0.7.zip',
			'required'           => true, 
			'version'            => '2.0.7',
		),
	    array(
	        'name'      => 'MailChimp for WordPress',
	        'slug'      => 'mailchimp-for-wp',
	        'required'  => true, 
	    ),
	    array(
	        'name'      => 'Post Types Order',
	        'slug'      => 'post-types-order',
	        'required'  => true, 
	    ),
	    array(
	        'name'      => 'Facebook Widget',
	        'slug'      => 'facebook-pagelike-widget',
	        'required'  => true, 
	    ),
	    array(
	        'name'      => 'Meks Easy Photo Feed Widget',
	        'slug'      => 'meks-easy-instagram-widget',
	        'required'  => false, 
	    ),
	);
	
	$config = array(
		'domain'	=> 'grandportfolio',
        'default_path' => '',                      // Default absolute path to pre-packaged plugins.
        'menu'         => 'install-required-plugins', // Menu slug.
        'has_notices'  => true,                    // Show admin notices or not.
        'is_automatic' => true,                   // Automatically activate plugins after installation or not.
        'message'      => '',                      // Message to output right before the plugins table.
        'strings'          => array(
	        'page_title'                      => esc_html__('Install Required Plugins', 'grandportfolio' ),
	        'menu_title'                      => esc_html__('Install Plugins', 'grandportfolio' ),
	        'installing'                      => esc_html__('Installing Plugin: %s', 'grandportfolio' ),
	        'oops'                            => esc_html__('Something went wrong with the plugin API.', 'grandportfolio' ),
	        'return'                          => esc_html__('Return to Required Plugins Installer', 'grandportfolio' ),
	        'plugin_activated'                => esc_html__('Plugin activated successfully.', 'grandportfolio' ),
	        'complete'                        => esc_html__('All plugins installed and activated successfully. %s', 'grandportfolio' ),
	        'nag_type'                        => 'update-nag'
	    )
    );
 
    tgmpa( $plugins, $config );
 
}
?>