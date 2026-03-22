<?php
$is_verified_envato_purchase_code = false;

//Get verified purchase code data
$is_verified_envato_purchase_code = grandportfolio_is_registered();

$customizer_styling_html = '';

//if verified envato purchase code
if($is_verified_envato_purchase_code)
{
	//Begin Create customizer styling import options
	$customizer_styling_arr = array( 
		array('id'	=>	'demo99', 'title' => 'Classic'),
		array('id'	=>	'demo1', 'title' => 'Architect'),
		array('id'	=>	'demo2', 'title' => 'Blogger'),
		array('id'	=>	'demo3', 'title' => 'Creative & Design Agency'),
		array('id'	=>	'demo4', 'title' => 'Fashion Designer'),
		array('id'	=>	'demo5', 'title' => 'Music Band & Singer'),
		array('id'	=>	'demo6', 'title' => 'Photographer'),
		array('id'	=>	'demo7', 'title' => 'Magazine & Publisher'),
		array('id'	=>	'demo8', 'title' => 'Agency Company'),
		array('id'	=>	'demo9', 'title' => 'Creative Company'),
	);
	
	$customizer_styling_html.= '<ul id="get_styling_content" class="styling_list">';
	
	foreach($customizer_styling_arr as $customizer_styling)
	{
		$customizer_styling_html.= '
			<li data-styling="'.$customizer_styling['id'].'">
		    	<div class="item_thumb"><img src="'.get_template_directory_uri().'/cache/demos/customizer/screenshots/'.$customizer_styling['id'].'.png" alt=""/></div>
		    	<div class="item_content">
				    '.$customizer_styling['title'].'
				</div>
		    </li>';
	}
	
	$customizer_styling_html.= '</ul>
	<input id="pp_get_styling_button" name="pp_get_styling_button" type="button" value="Get Selected Styling" class="upload_btn button-primary"/>
	<input type="hidden" id="pp_get_styling" name="pp_get_styling" value=""/>
	<div class="styling_message"><img src="'.get_template_directory_uri().'/functions/images/ajax-loader.gif" alt="" style="vertical-align: middle;"/><br/><br/>*Data is being procressed please be patient, don\'t navigate away from this page</div>';
}
else
{
	$customizer_styling_html.= '
		<div class="tg_notice">
			<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
			<span style="color:#FF3B30">'.THEMENAME.' Demos can only be imported with a valid purchase code</span><br/><br/>
			Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid purchase code to import the full '.THEMENAME.' demos and single pages through Content Builder.
		</div>';
}
//End Create customizer styling import options

//Begin Create demo import options
$demo_import_options_arr = array( 
	array('id'	=>	'demo99', 'title' => 'Classic', 'demo' => 99),
	array('id'	=>	'demo1', 'title' => 'Architect', 'demo' => 1),
	array('id'	=>	'demo2', 'title' => 'Blogger', 'demo' => 2),
	array('id'	=>	'demo3', 'title' => 'Creative & Design Agency', 'demo' => 3),
	array('id'	=>	'demo4', 'title' => 'Fashion Designer', 'demo' => 4),
	array('id'	=>	'demo5', 'title' => 'Music Band & Singer', 'demo' => 5),
	array('id'	=>	'demo6', 'title' => 'Photographer', 'demo' => 6),
	array('id'	=>	'demo7', 'title' => 'Magazine & Publisher', 'demo' => 7),
	array('id'	=>	'demo8', 'title' => 'Agency Company', 'demo' => 8),
	array('id'	=>	'demo9', 'title' => 'Creative Company', 'demo' => 9),
);
//End Create demo import options

$demo_import_options_html = '';
//if verified envato purchase code
if($is_verified_envato_purchase_code)
{
	$demo_import_options_html.= grandportfolio_check_system().'<p>
			<strong>*Notice: Please note that all recommended value suggested for above table is only if you want to use DEMO CONTENT IMPORTER feature only. If you won\'t use importer, you can ignore them.</strong>
		</p>
	    <ul id="import_demo_content" class="demo_list">';
		
	foreach($demo_import_options_arr as $demo_import_option)
	{
	$demo_import_options_html.= '<li class="fullwidth" data-demo="'.$demo_import_option['demo'].'">
			    	<div class="item_content_wrapper">
			    		<div class="item_content">
			    			<div class="item_thumb"><img src="'.get_template_directory_uri().'/cache/demos/customizer/screenshots/'.$demo_import_option['id'].'.png" alt=""/></div>
			    			<div class="item_content">
			    				<h3>'.$demo_import_option['title'].'</h3>
						    	What\'s Included?: posts, pages and custom post type contents, images, videos and theme settings.
						    </div>
					    </div>
				    </div>
			    </li>';
	}
	
	$demo_import_options_html.= '</ul>
	    <input id="pp_import_content_button" name="pp_import_content_button" type="button" value="Import Selected" class="upload_btn button-primary"/>
	    <input type="hidden" id="grandportfolio_import_demo_content" name="grandportfolio_import_demo_content" value=""/>
	    <div class="import_message"><img src="'.get_template_directory_uri().'/functions/images/ajax-loader.gif" alt="" style="vertical-align: middle;"/><br/><br/>*Data is being imported please be patient, don\'t navigate away from this page</div>';
}
else
{
	$demo_import_options_html.= '
		<div class="tg_notice">
			<span class="dashicons dashicons-warning" style="color:#FF3B30"></span> 
			<span style="color:#FF3B30">'.THEMENAME.' Demos can only be imported with a valid purchase code</span><br/><br/>
			Please visit <a href="javascript:jQuery(\'#pp_panel_registration_a\').trigger(\'click\');">Product Registration page</a> and enter a valid purchase code to import the full '.THEMENAME.' demos and single pages through Content Builder.
		</div>';
}

//Check if Instagram plugin is installed	
if( !function_exists('is_plugin_active') ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$instagram_widget_settings_notice = '';
$meks_easy_instagram_widget = 'meks-easy-instagram-widget/meks-easy-instagram-widget.php';
$meks_easy_instagram_widget_activated = is_plugin_active($meks_easy_instagram_widget);

if(!$meks_easy_instagram_widget_activated)
{
	$instagram_widget_settings_notice = 'Required plugin "Meks Easy Photo Feed Widget" is required. <a href="'.admin_url("themes.php?page=install-required-plugins").'">Please install the plugin here</a>. or read more detailed instruction about <a href="https://themes.themegoods.com/grandportfolio/doc/instagram-api-setup/" target="_blank">How to setup the plugin here</a>';
}
else
{
	//Verify Instagram API aurthorization
	$instagram_widget_settings = get_option('meks_instagram_settings');
	
	if(empty($instagram_widget_settings))
	{
		$instagram_widget_settings_notice = 'Please authorize with your Instagram account <a href="'.admin_url("options-general.php?page=meks-instagram").'">here</a>';
	}
	else
	{
		$instagram_widget_settings_notice = esc_html__('Authorized', 'photography');
	}
}

/*
	Begin creating admin options
*/

$getting_started_html = '<div class="one">
		<div class="step_icon">
			<a href="'.admin_url("themes.php?page=install-required-plugins").'">
				<i class="fa fa-plug"></i>
				<div class="step_title">Install Plugins</div>
			</a>
		</div>
		<div class="step_info">
			Theme has required and recommended plugins in order to build your website using layouts you saw on our demo site. We recommend you to install all plugins first.
		</div>
	</div>';

//Check if Grand Portfolio plugin is installed	
$grandportfolio_custom_post = 'grandportfolio-custom-post/grandportfolio-custom-post.php';

if( !function_exists('is_plugin_active') ) {
	include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
}

$grandportfolio_custom_post_activated = is_plugin_active($grandportfolio_custom_post);
if($grandportfolio_custom_post_activated)
{
	$getting_started_html.= '<div class="one">
		<div class="step_icon">
			<a href="'.admin_url("post-new.php?post_type=galleries").'">
				<i class="fa fa-photo"></i>
				<div class="step_title">Create Gallery</div>
			</a>
		</div>
		<div class="step_info">
			'.THEMENAME.' provide advanced gallery option. You can use bulk images uploader, select from various gallery templates option.
		</div>
	</div>
	
	<br style="clear:both;"/>
	
	<div class="one">
		<div class="step_icon">
			<a href="'.admin_url("post-new.php?post_type=portfolios").'">
				<i class="fa fa-folder-open-o"></i>
				<div class="step_title">Create Portfolio</div>
			</a>
		</div>
		<div class="step_info">
			'.THEMENAME.' provide advanced portfolio option. Portfolio is using for other portfolio content type for example text, HTML or video.
		</div>
	</div>';
}
	
$getting_started_html.='<div class="one">
		<div class="step_icon">
			<a href="'.admin_url("post-new.php?post_type=page").'">
				<i class="fa fa-file-text-o"></i>
				<div class="step_title">Create Page</div>
			</a>
		</div>
		<div class="step_info">
			'.THEMENAME.' support standard WordPress page option. You can also use our live content builder to create and organise page contents.
		</div>
	</div>
	
	<div class="one">
		<div class="step_icon">
			<a href="'.admin_url("customize.php").'">
				<i class="fa fa-sliders"></i>
				<div class="step_title">Customize Theme</div>
			</a>
		</div>
		<div class="step_info">
			Start customize theme\'s layouts, typography, elements colors using WordPress customize and see your changes in live preview instantly.
		</div>
	</div>
	
	<div class="one">
		<div class="step_icon">
			<a href="javascript:;" onclick="jQuery(\'#pp_panel_demo-content_a\').trigger(\'click\');">
				<i class="fa fa-database"></i>
				<div class="step_title">Import Demo</div>
			</a>
		</div>
		<div class="step_info">
			Upload demo content from our demo site. We recommend you to install all recommended plugins before importing demo contents.
		</div>
	</div>';
	
//if verified envato purchase code
if($is_verified_envato_purchase_code)
{
	$getting_started_html.='<br style="clear:both;"/>
	
	<div style="height:30px"></div>
	
	<h1>Support</h1>
	<div class="getting_started_desc">To access our support portal. You first must find your purchased code.</div>
	<div style="height:30px"></div>
	<hr/>
	<div style="height:30px"></div>
	<div class="one">
		<div class="step_icon">
			<a href="https://themegoods.ticksy.com/submit/" target="_blank">
				<i class="fa fa-commenting-o"></i>
				<div class="step_title">Submit a Ticket</div>
			</a>
		</div>
		<div class="step_info">
			We offer excellent support through our ticket system. Please make sure you prepare your purchased code first to access our services.
		</div>
	</div>
	
	<div class="one">
		<div class="step_icon">
			<a href="https://themes.themegoods.com/grandportfolio/doc" target="_blank">
				<i class="fa fa-book"></i>
				<div class="step_title">Theme Document</div>
			</a>
		</div>
		<div class="step_info">
			This is the place to go find all reference aspects of theme functionalities. Our online documentation is resource for you to start using theme.
		</div>
	</div>
';
}

//Get product registration

//if verified envato purchase code
$check_icon = '';
$verification_desc = 'Thank you for choosing '.THEMENAME.' theme. Your product must be registered to receive many advantage features ex. demos import and support. We are sorry about this extra step but we built the activation system to prevent mass piracy of our themes. This will help us to better serve our paying customers.';

//Check if have any purchase code verification error
$response_html = '';
$purchase_code = '';
$register_button_html = '<input type="submit" name="submit" id="themegoods-envato-code-submit" class="button button-primary button-large" value="Register"/>';

//If already registered
if(!empty($is_verified_envato_purchase_code))
{
	$response_html.= '<br style="clear:both;"/><div class="tg_valid"><span class="dashicons dashicons-yes"></span>Your product is registered.</div>';
	$register_button_html = '<input type="submit" name="submit" id="themegoods-envato-code-unregister" class="button button-primary button-large" value="Unregister"/>';
	$purchase_code = $is_verified_envato_purchase_code;
}

//Displays purchase code verification response
if(isset($_GET['response']) && !empty($_GET['response'])) {
	$response_arr = json_decode(stripcslashes($_GET['response']));
	$purchase_code = '';
	if(isset($_GET['purchase_code']) && !empty($_GET['purchase_code'])) {
		$purchase_code = $_GET['purchase_code'];
	}
	
	if(isset($response_arr->response_code)) {
		if(!$response_arr->response_code) {
			$response_html.= '<br style="clear:both;"/><div class="tg_error"><span class="dashicons dashicons-warning"></span>'.$response_arr->response.'</div>';
		}
	}
	else {
		$response_html.= '<br style="clear:both;"/><div class="tg_error"><span class="dashicons dashicons-warning"></span> We can\'t verify your purchase of '.THEMENAME.' theme. Please make sure you enter correct purchase code. If you are sure you enter correct one. <a href="https://themegoods.ticksy.com" target="_blank">Please open a ticket</a> to us so our support staff can help you. Thank you very much.</div>';
	}
}

$product_registration_html ='
		<h1>Product Registration</h1>
		<div class="getting_started_desc">'.$verification_desc.'</div>
		<br style="clear:both;"/>
		
		<div style="height:10px"></div>
		
		<label for="pp_envato_personal_token">'.$check_icon.'Purchase Code</label>
		';

$purchase_code_input_class = '';
if(!empty($is_verified_envato_purchase_code)) {
	$purchase_code_input_class = 'themegoods-verified';
}

$product_registration_html.= '<input name="pp_envato_personal_token" id="pp_envato_personal_token" type="text" value="'.esc_attr($purchase_code).'" class="'.esc_attr($purchase_code_input_class).'"/>
		<input name="themegoods-site-domain" id="themegoods-site-domain" type="hidden" value="'.esc_attr(grandportfolio_get_site_domain()).'"/>
	';
	$product_registration_html.= $register_button_html;
	$product_registration_html.= $response_html;
	
if(isset($_GET['action']) && $_GET['action'] == 'invalid-purchase')
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:20px"></div><div class="tg_error"><span class="dashicons dashicons-warning"></span> We can\'t find your purchase of '.THEMENAME.' theme. Please make sure you enter correct Envato Token. If you are sure you enter correct one. <a href="https://themegoods.ticksy.com" target="_blank">Please open a ticket</a> to us so our support staff can help you. Thank you very much.</div>
	
	<br style="clear:both;"/>
	
	<div style="height:10px"></div>';
}

if(!$is_verified_envato_purchase_code)
{
	$product_registration_html.='
		<br style="clear:both;"/>
		<div style="height:30px"></div>
		<h1>How to get Purchase Code</h1>
		<div style="height:5px"></div>
		<ol>
		 <li>You must be logged into the same Envato account that purchased '.THEMENAME.' theme.</li>
		 <li>Hover the mouse over your username at the top right corner of the screen.</li>
								<li>Click "Downloads" from the drop-down menu.</li>
								<li>Find '.THEMENAME.' theme your downloads list</li>
								<li>Click "Download" button and click "License certificate & purchase code" (available as PDF or text file).</li>
		</ol>
		<strong>You can see detailed article and video screencast about "how to find your purchase code" <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">here</a>.</strong>
	';
}

//If already registered and add a link to license manager
if(!empty($is_verified_envato_purchase_code))
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:20px"></div>
	<h1>Manage Your License</h1>
	<div class="getting_started_desc">To manage all your purchase code. Please open an account or login on <a href="https://license.themegoods.com/manager/" target="_blank">ThemeGoods License Manager</a>.
	</div>
	';
}

//Check if Envato Market plugin is installed	
$envato_market_activated = function_exists('envato_market');

if($is_verified_envato_purchase_code && !$envato_market_activated)
{
	$product_registration_html.='<br style="clear:both;"/><div style="height:20px"></div>
	<h1>Auto Update</h1>
	<div class="getting_started_desc">To enable auto update feature. You first must <a href="'.admin_url('themes.php?page=install-required-plugins').'">install Envato Market plugin</a> and enter your purchase code there. <a href="https://help.market.envato.com/hc/en-us/articles/202822600-Where-Is-My-Purchase-Code-" target="_blank">Find your purchase code</a></div>
	<br style="clear:both;"/>
	
	<div style="height:10px"></div>
	';
}

/*
	Begin creating admin options
*/

$api_url = (!empty($_SERVER['HTTPS'])) ? "https://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'] : "http://".$_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI'];

$grandportfolio_options = array (
 
//Begin admin header
array( 
		"name" => THEMENAME." Options",
		"type" => "title"
),
//End admin header

//Begin second tab "Registration"
array( 	"name" => "Registration",
		"type" => "section",
		"icon" => "fa-key",	
),
array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_registration",
	"type" => "html",
	"html" => $product_registration_html,
),

array( "type" => "close"),
//End second tab "Registration"

//Begin second tab "Home"
array( 	"name" => "Getting-Started",
		"type" => "section",
		"icon" => "fa-home",	
),
array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_home",
	"type" => "html",
	"html" => '
	<h1>Getting Started</h1>
	<div class="getting_started_desc">Welcome to '.THEMENAME.' theme. '.THEMENAME.' is now installed and ready to use! Read below for additional informations. We hope you enjoy using the theme!</div>
	<div style="height:30px"></div>
	<hr/>
	<div style="height:30px"></div>
	'.$getting_started_html.'
	',
),

array( "type" => "close"),
//End second tab "Home"

//Begin second tab "General"
array( 	"name" => "General",
		"type" => "section",
		"icon" => "fa-gear",	
),
array( "type" => "open"),

array( "name" => "<h2>Contact Form Settings</h2>Your email address",
	"desc" => "Enter which email address will be sent from contact form",
	"id" => SHORTNAME."_contact_email",
	"type" => "text",
	"validation" => "email",
	"std" => ""

),
array( "name" => "Select and sort contents on your contact page. Use fields you want to show on your contact form",
	"sort_title" => "Contact Form Manager",
	"desc" => "",
	"id" => SHORTNAME."_contact_form",
	"type" => "sortable",
	"options" => array(
		0 => 'Empty field',
		1 => 'Name',
		2 => 'Email',
		3 => 'Message',
		4 => 'Address',
		5 => 'Phone',
		6 => 'Mobile',
		7 => 'Company Name',
		8 => 'Country',
	),
	"options_disable" => array(1, 2, 3),
	"std" => ''
),

array( "name" => "<h2>Google Maps Setting</h2>API Key",
	"desc" => "Enter Google Maps API Key <a href=\"https://themegoods.ticksy.com/article/7785/\" target=\"_blank\">How to get API Key</a>",
	"id" => SHORTNAME."_googlemap_api_key",
	"type" => "text",
	"std" => ""
),

array( "name" => "Custom Google Maps Style",
	"desc" => "Enter javascript style array of map. You can get sample one from <a href=\"https://snazzymaps.com\" target=\"_blank\">Snazzy Maps</a>",
	"id" => SHORTNAME."_googlemap_style",
	"type" => "textarea",
	"std" => ""
),
array( "name" => "<h2>Captcha Settings</h2>Enable Captcha",
	"desc" => "If you enable this option, contact page will display captcha image to prevent possible spam",
	"id" => SHORTNAME."_contact_enable_captcha",
	"type" => "iphone_checkboxes",
	"std" => 1,
),

array( "name" => "<h2>Custom Sidebar Settings</h2>Add a new sidebar",
	"desc" => "Enter sidebar name",
	"id" => SHORTNAME."_sidebar0",
	"type" => "text",
	"validation" => "text",
	"std" => "",
),

array( "name" => "<h2>Custom Permalink Settings</h2>Gallery Page Slug",
	"desc" => "Enter custom permalink slug for gallery page",
	"id" => SHORTNAME."_permalink_galleries",
	"type" => "text",
	"std" => "galleries"
),

array( "name" => "Gallery Category Page Slug",
	"desc" => "Enter custom permalink slug for gallery category page",
	"id" => SHORTNAME."_permalink_gallerycat",
	"type" => "text",
	"std" => "gallerycat"
),

array( "name" => "Portfolio Page Slug",
	"desc" => "Enter custom permalink slug for portfolio page",
	"id" => SHORTNAME."_permalink_portfolio",
	"type" => "text",
	"std" => "portfolios"
),

array( "name" => "Portfolio Category Page Slug",
	"desc" => "Enter custom permalink slug for portfolio category page",
	"id" => SHORTNAME."_permalink_portfoliocat",
	"type" => "text",
	"std" => "portfoliosets"
),

array( "type" => "close"),
//End second tab "General"


//Begin second tab "Styling"
array( "name" => "Styling",
	"type" => "section",
	"icon" => "fa-paint-brush",
),

array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_get_styling_button",
	"type" => "html",
	"html" => $customizer_styling_html,
),
 
array( "type" => "close"),


//Begin fifth tab "Social Profiles"
array( 	"name" => "Social-Profiles",
		"type" => "section",
		"icon" => "fa-facebook-official",
),
array( "type" => "open"),
	
array( "name" => "<h2>Accounts Settings</h2>Facebook page URL",
	"desc" => "Enter full Facebook page URL",
	"id" => SHORTNAME."_facebook_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Twitter Username",
	"desc" => "Enter Twitter username",
	"id" => SHORTNAME."_twitter_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Flickr Username",
	"desc" => "Enter Flickr username",
	"id" => SHORTNAME."_flickr_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Youtube Profile URL",
	"desc" => "Enter Youtube Profile URL",
	"id" => SHORTNAME."_youtube_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Vimeo Username",
	"desc" => "Enter Vimeo username",
	"id" => SHORTNAME."_vimeo_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Tumblr Username",
	"desc" => "Enter Tumblr username",
	"id" => SHORTNAME."_tumblr_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Dribbble Username",
	"desc" => "Enter Dribbble username",
	"id" => SHORTNAME."_dribbble_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Linkedin URL",
	"desc" => "Enter full Linkedin URL",
	"id" => SHORTNAME."_linkedin_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Pinterest Username",
	"desc" => "Enter Pinterest username",
	"id" => SHORTNAME."_pinterest_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Instagram Username",
	"desc" => "Enter Instagram username",
	"id" => SHORTNAME."_instagram_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "Behance Username",
	"desc" => "Enter Behance username",
	"id" => SHORTNAME."_behance_username",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "500px Profile URL",
	"desc" => "Enter 500px Profile URL",
	"id" => SHORTNAME."_500px_url",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "name" => "<h2>Photo Stream</h2>Select photo stream photo source. It displays before footer area",
	"desc" => "",
	"id" => SHORTNAME."_photostream",
	"type" => "select",
	"options" => array(
		'' => 'Disable Photo Stream',
		'instagram' => 'Instagram',
		'flickr' => 'Flickr',
	),
	"std" => ''
),

array( "name" => "Connect with your Instagram account",
	"desc" => "",
	"id" => SHORTNAME."_instagram_account_notice",
	"type" => "html",
	"html" => "<div class='html_inline_wrapper'>".$instagram_widget_settings_notice."</div>",
),

array( "name" => "Flickr ID <a href=\"http://idgettr.com/\" target=\"_blank\">Find your Flickr ID here</a>",
	"desc" => "Enter Flickr ID",
	"id" => SHORTNAME."_flickr_id",
	"type" => "text",
	"std" => "",
	"validation" => "text",
),
array( "type" => "close"),

//End fifth tab "Social Profiles"


//Begin second tab "Script"
array( "name" => "Script",
	"type" => "section",
	"icon" => "fa-css3",
),

array( "type" => "open"),

array( "name" => "<h2>CSS Settings</h2>Custom CSS for desktop",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPad Portrait View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_tablet_portrait",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPhone Landscape View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_mobile_landscape",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "Custom CSS for iPhone Portrait View",
	"desc" => "You can add your custom CSS here",
	"id" => SHORTNAME."_custom_css_mobile_portrait",
	"type" => "textarea",
	"std" => "",
	'validation' => '',
),

array( "name" => "<h2>CSS and Javascript Optimisation Settings</h2>Combine and compress theme's CSS files",
	"desc" => "Combine and compress all CSS files to one. Help reduce page load time. NOTE: If you enable child theme CSS compression is not support",
	"id" => SHORTNAME."_advance_combine_css",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "Combine and compress theme's javascript files",
	"desc" => "Combine and compress all javascript files to one. Help reduce page load time",
	"id" => SHORTNAME."_advance_combine_js",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "Cache theme's custom CSS",
	"desc" => "Cache theme custom CSS code which generate by customizer options to help improving loading speed",
	"id" => SHORTNAME."_advance_cache_custom_css",
	"type" => "iphone_checkboxes",
	"std" => 1
),

array( "name" => "<h2>Cache Settings</h2>Clear Cache",
	"desc" => "Try to clear cache when you enable javascript and CSS compression and theme went wrong",
	"id" => SHORTNAME."_advance_clear_cache",
	"type" => "html",
	"html" => '<a id="'.SHORTNAME.'_advance_clear_cache" href="'.$api_url.'" class="button">Click here to start clearing cache files</a>',
),
 
array( "type" => "close"),

//Begin second tab "Demo"
array( "name" => "Demo-Content",
	"type" => "section",
	"icon" => "fa-database",
),

array( "type" => "open"),

array( "name" => "",
	"desc" => "",
	"id" => SHORTNAME."_import_demo_content",
	"type" => "html",
	"html" => $demo_import_options_html,
),

array( "type" => "close"),

array( 	"name" => "Buy-Another-License",
"type" => "section",
"icon" => "",		
),

array( "type" => "open"),

array( "type" => "close"),

);

grandportfolio_set_options($grandportfolio_options);
?>