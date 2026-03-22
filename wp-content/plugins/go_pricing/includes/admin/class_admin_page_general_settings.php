<?php
/**
 * General Settings page controller class
 */
 
 
// Prevent direct call
if ( !defined( 'WPINC' ) ) die;
if ( !class_exists( 'GW_GoPricing' ) ) die;	


// Class
class GW_GoPricing_AdminPage_GeneralSettings extends GW_GoPricing_AdminPage {
	
	/**
	 * Register ajax actions
	 *
	 * @return void
	 */	

	public function register_ajax_actions( $ajax_action_callback ) { 
	
		GW_GoPricing_Admin::register_ajax_action( 'general_settings', $ajax_action_callback );
		
	}
	

	/**
	 * Action
	 *
	 * @return void
	 */	
	 	
	public function action() {
        
		// Create custom nonce
		$this->create_nonce( 'general_settings' );
		
		// Load views if action is empty		
		if ( empty( $this->action ) ) {
			
			$this->content( $this->view() );
			
		}
		
		// Load views if action is not empty (handle postdata)
        
		if ( !empty( $this->action ) && check_admin_referer( $this->nonce, '_nonce' ) ) {
			$this->process_postdata( $_POST );
			
			if ( $this->is_ajax === false ) {
				wp_redirect( $this->referrer );	
				exit;
			} else {
				GW_GoPricing_AdminNotices::show();
			}
			
		}
			
	}
	
	
	/**
	 * Load views
	 *
	 * @return string
	 */	
	
	public function view( $view = '', $data = null ) {
        
		ob_start();
		include_once( 'views/page/general_settings.php' );
		return ob_get_clean();
		
	}

	
	/**
	 * Process postdata
	 *
	 * @return void
	 */		

	public function process_postdata( $postdata ) {
        
		// Clean custom CSS
		if ( isset( $postdata['custom-css'] ) ) {
			$custom_css = GW_GoPricing_Helper::clean_input( array( $postdata['custom-css'] ), 'no_html' );
			$postdata['custom-css'] = $custom_css[0];
		}		
		
		// Clean postdata (the rest)
		$postdata = GW_GoPricing_Helper::clean_input( $postdata, 'filtered', '', array( 'thousand-sep', 'custom-css' ) );
		$postdata = GW_GoPricing_Helper::remove_input( $postdata, 'action' );
		
		$settings = get_option( self::$plugin_prefix . '_table_settings', $postdata, [] );
        
        $cap_settings = ['capability' => isset($settings['capability']) ? $settings['capability'] : 'manage_options'];
        if (!empty($postdata) && !empty($postdata['capability'])) $cap_settings['capability'] = $postdata['capability'];
        
        // Unauthorized: cannot change cap unless user can manage options
        if (!current_user_can('manage_options') && !empty($postdata['capability'])) {
            GW_GoPricing_AdminNotices::add( 'general_settings', 'error', __( 'You are not authorized for the operation!', 'go_pricing_textdomain' ) );
            return;
        }

        // Unauthorized: cannot update settings unless user has the required cap
        if (!current_user_can($cap_settings['capability'])) {
            GW_GoPricing_AdminNotices::add( 'general_settings', 'error', __( 'You are not authorized for the operation!', 'go_pricing_textdomain' ) );
            return;
        }
        
		// Verify and save data
		if ( !empty( $postdata ) ) {
            
			if ( empty( $settings ) || ( !empty( $settings ) && $settings != $postdata ) ) {
				update_option( self::$plugin_prefix . '_table_settings', array_merge($cap_settings, $postdata) );
                GW_GoPricing_AdminNotices::add( 'general_settings', 'success', __( 'General Settings has been successfully updated!', 'go_pricing_textdomain' ) );
			} else {
				GW_GoPricing_AdminNotices::add( 'general_settings', 'success', __( 'General Settings has been successfully updated!', 'go_pricing_textdomain' ) );	
			}
					
		} else {
			GW_GoPricing_AdminNotices::add( 'general_settings', 'error', __( 'Oops, something went wrong!', 'go_pricing_textdomain' ) );	
		}
        

	}
	
}