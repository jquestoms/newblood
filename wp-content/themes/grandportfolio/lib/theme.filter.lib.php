<?php
//Add more CURL timeout if purchase code is not registered
$is_verified_envato_purchase_code = grandportfolio_is_registered();

//Check if registered purchase code valid
if(empty($is_verified_envato_purchase_code)) {
  add_filter('http_request_args', 'grandportfolio_http_request_args', 100, 1);
  function grandportfolio_http_request_args($r) 
  {
    $r['timeout'] = 30;
    return $r;
  }
   
  add_action('http_api_curl', 'grandportfolio_http_api_curl', 100, 1);
  function grandportfolio_http_api_curl($handle) 
  {
    curl_setopt( $handle, CURLOPT_CONNECTTIMEOUT, 30 );
    curl_setopt( $handle, CURLOPT_TIMEOUT, 30 );
  }
}

if(THEMEDEMO) {
  //Remove Gutenberg Block Library CSS from loading on the frontend
  function grandportfolio_remove_wp_block_library_css(){
    wp_dequeue_style('wp-block-library');
    
    // This also removes some inline CSS variables for colors since 5.9 - global-styles-inline-css
    wp_dequeue_style('global-styles');
    
    // WooCommerce - you can remove the following if you don't use Woocommerce
    wp_dequeue_style('wc-block-style');
    wp_dequeue_style('wc-blocks-vendors-style');
    wp_dequeue_style('wc-blocks-style'); 
  } 
  add_action( 'wp_enqueue_scripts', 'grandportfolio_remove_wp_block_library_css', 100 );
}

add_filter('wpcf7_autop_or_not', '__return_false');

/**
 * This function runs when WordPress completes its upgrade process
 * It iterates through each plugin updated to see if ours is included
 * @param $upgrader_object Array
 * @param $options Array
 */
function grandportfolio_upgrade_completed( $upgrader_object, $hook_extra ) { 
  
  if ($hook_extra['type'] = 'theme' && !THEMEDEMO) {
    //Get verified purchase code data
    $is_verified_envato_purchase_code = grandportfolio_is_registered();
    
    //Check if registered purchase code valid
    if(!empty($is_verified_envato_purchase_code)) {
      $site_domain = grandportfolio_get_site_domain();
      
      if($site_domain != 'localhost') {
        $url = THEMEGOODS_API.'/check-purchase-domain';
        //var_dump($url);
        $data = array(
          'purchase_code' => $is_verified_envato_purchase_code, 
          'domain' => $site_domain,
        );
        $data = wp_json_encode( $data );
        $args = array( 
          'method'   	=> 'POST',
          'body'		=> $data,
        );
        //print '<pre>'; var_dump($args); print '</pre>';
        
        $response = wp_remote_post( $url, $args );
        $response_body = wp_remote_retrieve_body( $response );
        $response_obj = json_decode($response_body);
        
        $response_json = urlencode($response_body);
        
        //If no data then unregister theme
        if(!$response_obj->response_code OR empty($response_body)) {
          
        }
        else {
          if(!empty($response_body)) {
            $response_body_obj = json_decode($response_body);
            
            if(!$response_body_obj->response[0]->domain) {
              
            }
            else {
              /*print '<pre>'; var_dump($response_body_obj->response[0]->domain); print '</pre>';
              die;*/
              if(!empty($response_body_obj->response[0]->domain) && $response_body_obj->response[0]->domain != $site_domain) {
                grandportfolio_unregister_theme();
              }
            }
          }
        }
      }
    }
  }
}
add_action( 'upgrader_process_complete', 'grandportfolio_upgrade_completed', 10, 2 );

add_action( 'admin_init', 'grandportfolio_gutenberg_init', 10 );

function grandportfolio_gutenberg_init()
{
	if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly
    }
    
    global $pagenow;
    if($pagenow == 'post.php' && isset($_GET['post']))
    {
		if(current_user_can('edit_post', $_GET['post']));
		{
			if (!isset( $_GET['gutenberg-editor'] ) && (isset($_GET['action']) && $_GET['action'] == 'edit') && (function_exists( 'is_gutenberg_page' ) && !is_gutenberg_page())) {
			    // Disable Gutenberg
				add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
				add_filter( 'use_block_editor_for_post_type', '__return_false' );
			}
			
			if (isset( $_GET['gutenberg-editor'] ))
			{
				if(isset($_GET['post']) && !empty($_GET['post']))
				{
					delete_post_meta($_GET['post'], 'ppb_enable');
					$ppb_enable = get_post_meta($_GET['post'], 'ppb_enable', true);
				}
			}
		
			if (isset( $_GET['classic-editor'] ))
			{
				// Disable Gutenberg
				add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
				add_filter( 'use_block_editor_for_post_type', '__return_false' );
			}
			
			if (isset( $_GET['action'] ) && $_GET['action'] == 'edit' && !isset( $_GET['gutenberg-editor'] ))
			{
				$ppb_enable = get_post_meta($_GET['post'], 'ppb_enable', true);
				if(!empty($ppb_enable))
				{
					// Disable Gutenberg
					add_filter( 'gutenberg_can_edit_post_type', '__return_false' );
					add_filter( 'use_block_editor_for_post_type', '__return_false' );
				}
			}
		}
	}
}	

// A callback function to add a custom field to our "gallery categories" taxonomy
function grandportfolio_gallerycat_taxonomy_custom_fields($tag) {

   // Check for existing taxonomy meta for the term you're editing
    $t_id = $tag->term_id; // Get the ID of the term you're editing
    $term_meta = get_option( "taxonomy_term_$t_id" ); // Do the check
?>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="gallerycat_template"><?php esc_html_e('Gallery Category Page Template', 'grandportfolio'); ?></label>
	</th>
	<td>
		<select name="gallerycat_template" id="gallerycat_template">
			<?php
				//Get all gallery archive templates
				$tg_gallery_archive_templates = array(
					'gallery-archive-fullscreen' => 'Fullscreen',
					'gallery-archive-split-screen' => 'Split Screen',
					'gallery-archive-2-contained' => '2 Columns Contained', 
					'gallery-archive-3-contained' => '3 Columns Contained',
					'gallery-archive-4-contained' => '4 Columns Contained',
					'gallery-archive-2-wide' => '2 Columns Wide',
					'gallery-archive-3-wide' => '3 Columns Wide',
					'gallery-archive-4-wide' => '4 Columns Wide',
					'gallery-archive-parallax' => 'Parallax',
				);
				
				foreach($tg_gallery_archive_templates as $key => $tg_gallery_archive_template)
				{
			?>
			<option value="<?php echo esc_attr($key); ?>" <?php if($term_meta['gallerycat_template']==$key) { ?>selected<?php } ?>><?php echo esc_html($tg_gallery_archive_template); ?></option>
			<?php
				}
			?>
		</select>
		<br />
		<span class="description"><?php esc_html_e('Select page template for this gallery category', 'grandportfolio'); ?></span>
	</td>
</tr>

<?php
}

// A callback function to save our extra taxonomy field(s)
function grandportfolio_save_gallerycat_custom_fields( $term_id ) {
    if ( isset( $_POST['gallerycat_template'] ) ) {
        $t_id = $term_id;
        $term_meta = get_option( "taxonomy_term_$t_id" );

        if ( isset( $_POST['gallerycat_template'] ) ){
            $term_meta['gallerycat_template'] = $_POST['gallerycat_template'];
        }
        
        //save the option array
        update_option( "taxonomy_term_$t_id", $term_meta );
    }
}

// Add the fields to the "gallery categories" taxonomy, using our callback function
add_action( 'gallerycat_edit_form_fields', 'grandportfolio_gallerycat_taxonomy_custom_fields', 10, 2 );

// Save the changes made on the "presenters" taxonomy, using our callback function
add_action( 'edited_gallerycat', 'grandportfolio_save_gallerycat_custom_fields', 10, 2 );


// A callback function to add a custom field to our "gallery categories" taxonomy
function grandportfolio_portfoliosets_taxonomy_custom_fields($tag) {

   // Check for existing taxonomy meta for the term you're editing
    $t_id = $tag->term_id; // Get the ID of the term you're editing
    $term_meta = get_option( "taxonomy_term_$t_id" ); // Do the check
?>

<tr class="form-field">
	<th scope="row" valign="top">
		<label for="portfoliosets_template"><?php esc_html_e('Portfolio Category Page Template', 'grandportfolio'); ?></label>
	</th>
	<td>
		<select name="portfoliosets_template" id="portfoliosets_template">
			<?php
				//Get all gallery archive templates
				$tg_gallery_archive_templates = array(
					'portfolio-fullscreen' => 'Fullscreen',
					'portfolio-parallax' => 'Parallax',
					'portfolio-2-contained' => '2 Columns Contained',
					'portfolio-3-contained' => '3 Columns Contained',
					'portfolio-4-contained' => '4 Columns Contained',
					'portfolio-2-contained-classic' => '2 Columns Classic',
					'portfolio-3-contained-classic' => '3 Columns Classic',
					'portfolio-4-contained-classic' => '4 Columns Classic',
					'portfolio-2-contained-masonry-classic' => '2 Columns Masonry Classic',
					'portfolio-2-wide' => '2 Columns Wide',
					'portfolio-3-wide' => '3 Columns Wide',
					'portfolio-4-wide' => '4 Columns Wide',
					'portfolio-5-wide' => '5 Columns Wide',
					'portfolio-2-wide-classic' => '2 Columns Wide Classic',
					'portfolio-3-wide-classic' => '3 Columns Wide Classic',
					'portfolio-4-wide-classic' => '4 Columns Wide Classic',
					'portfolio-3-wide-masonry' => 'Masonry',
					'portfolio-split-screen' => 'Split Screen',
					'portfolio-split-screen-wide' => 'Split Screen Wide',
					'portfolio-split-screen-masonry' => 'Split Screen Masonry',
				);
				
				foreach($tg_gallery_archive_templates as $key => $tg_gallery_archive_template)
				{
			?>
			<option value="<?php echo esc_attr($key); ?>" <?php if($term_meta['portfoliosets_template']==$key) { ?>selected<?php } ?>><?php echo esc_html($tg_gallery_archive_template); ?></option>
			<?php
				}
			?>
		</select>
		<br />
		<span class="description"><?php esc_html_e('Select page template for this gallery category', 'grandportfolio'); ?></span>
	</td>
</tr>
<tr class="form-field">
	<th scope="row" valign="top">
		<label for="portfoliosets_custom_url"><?php esc_html_e('Custom URL', 'grandportfolio'); ?> (<?php esc_html_e('Optional', 'grandportfolio'); ?>)</label>
	</th>
	<td>
		<input name="portfoliosets_custom_url" id="portfoliosets_custom_url" value="<?php if(isset($term_meta['portfoliosets_custom_url'])) { echo esc_attr($term_meta['portfoliosets_custom_url']); } ?>" size="40"/>
		<br />
		<span class="description"><?php esc_html_e('Enter custom portfolio URL for this portfolio category. It will replace default portfolio category URL', 'grandportfolio'); ?></span>
	</td>
</tr>

<?php
}

// A callback function to save our extra taxonomy field(s)
function grandportfolio_save_portfoliosets_custom_fields( $term_id ) {
    if ( isset( $_POST['portfoliosets_template'] ) ) {
        $t_id = $term_id;
        $term_meta = get_option( "taxonomy_term_$t_id" );

        if ( isset( $_POST['portfoliosets_template'] ) ){
            $term_meta['portfoliosets_template'] = $_POST['portfoliosets_template'];
        }
        
        //save the option array
        update_option( "taxonomy_term_$t_id", $term_meta );
    }
    
    if ( isset( $_POST['portfoliosets_custom_url'] ) ) {
        $t_id = $term_id;
        $term_meta = get_option( "taxonomy_term_$t_id" );

        if ( isset( $_POST['portfoliosets_custom_url'] ) ){
            $term_meta['portfoliosets_custom_url'] = $_POST['portfoliosets_custom_url'];
        }
        
        //save the option array
        update_option( "taxonomy_term_$t_id", $term_meta );
    }
}

// Add the fields to the "portfolio categories" taxonomy, using our callback function
add_action( 'portfoliosets_edit_form_fields', 'grandportfolio_portfoliosets_taxonomy_custom_fields', 10, 2 );

// Save the changes made on the "presenters" taxonomy, using our callback function
add_action( 'edited_portfoliosets', 'grandportfolio_save_portfoliosets_custom_fields', 10, 2 );


function grandportfolio_tag_cloud_filter($args = array()) {
   $args['smallest'] = 13;
   $args['largest'] = 13;
   $args['unit'] = 'px';
   return $args;
}

add_filter('widget_tag_cloud_args', 'grandportfolio_tag_cloud_filter', 90);

//Control post excerpt length
function grandportfolio_custom_excerpt_length( $length ) {
	return 40;
}
add_filter( 'excerpt_length', 'grandportfolio_custom_excerpt_length', 200 );

//Customise Widget Title Code
add_filter( 'dynamic_sidebar_params', 'grandportfolio_wrap_widget_titles', 1 );
function grandportfolio_wrap_widget_titles( array $params ) 
{
	$widget =& $params[0];
	$widget['before_title'] = '<h2 class="widgettitle"><span>';
	$widget['after_title'] = '</span></h2>';
	
	return $params;
}

/**
 * Change default fields, add placeholder and change type attributes.
 *
 * @param  array $fields
 * @return array
 */
add_filter( 'comment_form_default_fields', 'grandportfolio_comment_placeholders' );
 
function grandportfolio_comment_placeholders( $fields )
{
    $fields['author'] = str_replace('<input', '<input placeholder="'. esc_html__('Name', 'grandportfolio'). '*"',$fields['author']);
    $fields['email'] = str_replace('<input id="email" name="email" type="text"', '<input type="email" placeholder="'.esc_html__('Email', 'grandportfolio').'*"  id="email" name="email"',$fields['email']);
    $fields['url'] = str_replace('<input id="url" name="url" type="text"', '<input placeholder="'.esc_html__('Website', 'grandportfolio').'" id="url" name="url" type="url"',$fields['url']);

    return $fields;
}

//Make widget support shortcode
add_filter('widget_text', 'do_shortcode');

//Add upload form to page
if (is_admin()) {
  $current_admin_page = substr(strrchr($_SERVER['PHP_SELF'], '/'), 1, -4);

  if ($current_admin_page == 'post' || $current_admin_page == 'post-new')
  {
 
    /** Need to force the form to have the correct enctype. */
    function grandportfolio_add_post_enctype() {
      echo "<script type=\"text/javascript\">
        jQuery(document).ready(function(){
        jQuery('#post').attr('enctype','multipart/form-data');
        jQuery('#post').attr('encoding', 'multipart/form-data');
        });
        </script>";
    }
 
    add_action('admin_head', 'grandportfolio_add_post_enctype');
  }
}

// remove version query string from scripts and stylesheets
function grandportfolio_remove_script_styles_version( $src ){
    return remove_query_arg( 'ver', $src );
}
add_filter( 'script_loader_src', 'grandportfolio_remove_script_styles_version' );
add_filter( 'style_loader_src', 'grandportfolio_remove_script_styles_version' );


function grandportfolio_theme_queue_js(){
  if ( is_singular() && comments_open() && get_option( 'thread_comments' ) ) { 
    // enqueue the javascript that performs in-link comment reply fanciness
    wp_enqueue_script( 'comment-reply' ); 
  }
}
add_action('get_header', 'grandportfolio_theme_queue_js');


/**
* Add Photographer Name and URL fields to media uploader
*/
 
function phorography_attachment_field_credit ($form_fields, $post) {
	$form_fields['grandportfolio-purchase-url'] = array(
		'label' => esc_html__('Purchase URL', 'grandportfolio'),
		'input' => 'text',
		'value' => esc_url(get_post_meta( $post->ID, 'grandportfolio_purchase_url', true )),
	);

	return $form_fields;
}

add_filter( 'attachment_fields_to_edit', 'phorography_attachment_field_credit', 10, 2 );

/**
* Save values of Photographer Name and URL in media uploader
*/

function phorography_attachment_field_credit_save ($post, $attachment) {
	if( isset( $attachment['grandportfolio-purchase-url'] ) )
update_post_meta( $post['ID'], 'grandportfolio_purchase_url', esc_url( $attachment['grandportfolio-purchase-url'] ) );

	return $post;
}

add_filter( 'attachment_fields_to_save', 'phorography_attachment_field_credit_save', 10, 2 );

add_action( 'add_meta_boxes', array ( 'Grandportfolio_Richtext_Excerpt', 'switch_boxes' ) );

function grandportfolio_add_meta_tags() {
    $post = grandportfolio_get_wp_post();
    
    echo '<meta charset="'.get_bloginfo( 'charset' ).'" />';
    
    //Check if responsive layout is enabled
    $tg_mobile_responsive = kirki_get_option('tg_mobile_responsive');
	
	if(!empty($tg_mobile_responsive))
	{
		echo '<meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />';
	}
	
	//meta for phone number link on mobile
	echo '<meta name="format-detection" content="telephone=no">';
    
    //check if single post then add meta description and keywords
    if (is_single()) 
    {
        //Prepare data for Facebook opengraph sharing
        if(has_post_thumbnail(get_the_ID(), 'grandportfolio-blog'))
		{
		    $image_id = get_post_thumbnail_id(get_the_ID());
		    $fb_thumb = wp_get_attachment_image_src($image_id, 'grandportfolio-blog', true);
		}
	
		if(isset($fb_thumb[0]) && !empty($fb_thumb[0]))
		{
			$post_content = get_post_field('post_excerpt', $post->ID);
			
			echo '<meta property="og:type" content="article" />';
			echo '<meta property="og:image" content="'.esc_url($fb_thumb[0]).'"/>';
			echo '<meta property="og:title" content="'.esc_attr(get_the_title()).'"/>';
			echo '<meta property="og:url" content="'.esc_url(get_permalink($post->ID)).'"/>';
			echo '<meta property="og:description" content="'.esc_attr(strip_tags($post_content)).'"/>';
		}
    }
}
add_action( 'wp_head', 'grandportfolio_add_meta_tags' , 2 );


/**
 * Replaces the default excerpt editor with TinyMCE.
 */
class Grandportfolio_Richtext_Excerpt
{
    /**
     * Replaces the meta boxes.
     *
     * @return void
     */
    public static function switch_boxes()
    {
        if ( ! post_type_supports( $GLOBALS['post']->post_type, 'excerpt' ) )
        {
            return;
        }

        remove_meta_box(
            'postexcerpt' // ID
        ,   ''            // Screen, empty to support all post types
        ,   'normal'      // Context
        );

        add_meta_box(
            'postexcerpt2'     // Reusing just 'postexcerpt' doesn't work.
        ,   esc_html__('Excerpt', 'grandportfolio' )    // Title
        ,   array ( __CLASS__, 'show' ) // Display function
        ,   null              // Screen, we use all screens with meta boxes.
        ,   'normal'          // Context
        ,   'core'            // Priority
        );
    }

    /**
     * Output for the meta box.
     *
     * @param  object $post
     * @return void
     */
    public static function show( $post )
    {
    ?>
        <label class="screen-reader-text" for="excerpt"><?php
        esc_html_e('Excerpt', 'grandportfolio' )
        ?></label>
        <?php
        // We use the default name, 'excerpt', so we don’t have to care about
        // saving, other filters etc.
        wp_editor(
            self::unescape( $post->post_excerpt ),
            'excerpt',
            array (
            'textarea_rows' => 15
        ,   'media_buttons' => FALSE
        ,   'teeny'         => TRUE
        ,   'tinymce'       => TRUE
            )
        );
    }

    /**
     * The excerpt is escaped usually. This breaks the HTML editor.
     *
     * @param  string $str
     * @return string
     */
    public static function unescape( $str )
    {
        return str_replace(
            array ( '&lt;', '&gt;', '&quot;', '&amp;', '&nbsp;', '&amp;nbsp;' )
        ,   array ( '<',    '>',    '"',      '&',     ' ', ' ' )
        ,   $str
        );
    }
}

/**
 * Add menu description
 */
 
class Grandportfolio_Menu_With_Description extends Walker_Nav_Menu 
{
	function start_el(&$output, $item, $depth = 0, $args = array(), $id = 0) {
		$indent = ( $depth ) ? str_repeat( "\t", $depth ) : '';
		
		$class_names = $value = '';

		$classes = empty( $item->classes ) ? array() : (array) $item->classes;

		$class_names = join( ' ', apply_filters( 'nav_menu_css_class', array_filter( $classes ), $item ) );
		$class_names = ' class="' . esc_attr( $class_names ) . '"';

		$output .= $indent . '<li id="menu-item-'. $item->ID . '"' . $value . $class_names .'>';

		$attributes = ! empty( $item->attr_title ) ? ' title="' . esc_attr( $item->attr_title ) .'"' : '';
		$attributes .= ! empty( $item->target ) ? ' target="' . esc_attr( $item->target ) .'"' : '';
		$attributes .= ! empty( $item->xfn ) ? ' rel="' . esc_attr( $item->xfn ) .'"' : '';
		$attributes .= ! empty( $item->url ) ? ' href="' . esc_attr( $item->url ) .'"' : '';

		$item_output = $args->before;
		$item_output .= '<a'. $attributes .'>';
		$item_output .= $args->link_before . apply_filters( 'the_title', $item->title, $item->ID ) . $args->link_after;
		$item_output .= '<div class="menu-description">' . $item->description . '</div>';
		$item_output .= '</a>';
		$item_output .= $args->after;

		$output .= apply_filters( 'walker_nav_menu_start_el', $item_output, $item, $depth, $args, $id );
	}
}

/* Pagination fix for custom loops on pages */
add_filter('redirect_canonical','custom_disable_redirect_canonical');
function custom_disable_redirect_canonical($redirect_url) {if (is_paged() && is_singular()) $redirect_url = false; return $redirect_url; }

add_action( 'admin_enqueue_scripts', 'grandportfolio_admin_pointers_header' );

function grandportfolio_admin_pointers_header() {
   if ( grandportfolio_admin_pointers_check() ) {
      add_action( 'admin_print_footer_scripts', 'grandportfolio_admin_pointers_footer' );

      wp_enqueue_script( 'wp-pointer' );
      wp_enqueue_style( 'wp-pointer' );
   }
}

function grandportfolio_admin_pointers_check() {
   $admin_pointers = grandportfolio_admin_pointers();
   foreach ( $admin_pointers as $pointer => $array ) {
      if ( $array['active'] )
         return true;
   }
}

function grandportfolio_admin_pointers_footer() {
   $admin_pointers = grandportfolio_admin_pointers();
   ?>
<script type="text/javascript">
/* <![CDATA[ */
( function($) {
   <?php
   foreach ( $admin_pointers as $pointer => $array ) {
      if ( $array['active'] ) {
         ?>
         $( '<?php echo $array['anchor_id']; ?>' ).pointer( {
            content: '<?php echo $array['content']; ?>',
            position: {
            edge: '<?php echo $array['edge']; ?>',
            align: '<?php echo $array['align']; ?>'
         },
            close: function() {
               $.post( ajaxurl, {
                  pointer: '<?php echo $pointer; ?>',
                  action: 'dismiss-wp-pointer'
               } );
            }
         } ).pointer( 'open' );
         <?php
      }
   }
   ?>
} )(jQuery);
/* ]]> */
</script>
   <?php
}

function grandportfolio_admin_pointers() {
   $dismissed = explode( ',', (string) get_user_meta( get_current_user_id(), 'dismissed_wp_pointers', true ) );
   $prefix = 'grandportfolio_admin_pointers';

   //Page help pointers
   $content_builder_content = '<h3>Content Builder</h3>';
   $content_builder_content .= '<p>Basically you can use WordPress visual editor to create page content but theme also has another way to create page content. By using Content Builder, you would be ale to drag&drop each content block without coding knowledge. Click here to enable Content Builder.</p>';
   
   $page_options_content = '<h3>Page Options</h3>';
   $page_options_content .= '<p>You can customise various options for this page including menu styling, page templates etc.</p>';
   
   $page_featured_image_content = '<h3>Page Featured Image</h3>';
   $page_featured_image_content .= '<p>Upload or select featured image for this page to displays it as background header.</p>';
   
   //Post help pointers
   $post_options_content = '<h3>Post Options</h3>';
   $post_options_content .= '<p>You can customise various options for this post including its layout and featured content type.</p>';
   
   $post_featured_image_content = '<h3>Post Featured Image (*Required)</h3>';
   $post_featured_image_content .= '<p>Upload or select featured image for this post to displays it as post image on blog, archive, category, tag and search pages.</p>';
   
   //Gallery help pointers
   $gallery_images_content = '<h3>Gallery Images</h3>';
   $gallery_images_content .= '<p>Upload or select for this gallery. You can select multiple images to upload using SHIFT or CTRL keys.</p>';
   
   $gallery_options_content = '<h3>Gallery Options</h3>';
   $gallery_options_content .= '<p>You can customise various options for this gallery including gallery template, password and gallery images file.</p>';
   
   $gallery_featured_image_content = '<h3>Gallery Featured Image (*Required)</h3>';
   $gallery_featured_image_content .= '<p>Upload or select featured image for this gallery to displays it as gallery image on gallery archive pages. If featured image is not selected, this gallery will not display on gallery archive page.</p>';
   
   //Portfolio help pointers
   $portfolio_options_content = '<h3>Portfolio Options</h3>';
   $portfolio_options_content .= '<p>You can customise various options for this portfolio including content type etc.</p>';
   
   $portfolio_featured_image_content = '<h3>Portfolio Featured Image (*Required)</h3>';
   $portfolio_featured_image_content .= '<p>Upload or select featured image for this portfolio to displays it as portfolio image on portfolio archive pages.</p>';
   
   //Event help pointers
   $event_options_content = '<h3>Event Options</h3>';
   $event_options_content .= '<p>You can customise various options for this event including date, time, location etc.</p>';
   
   $event_featured_image_content = '<h3>Event Featured Image</h3>';
   $event_featured_image_content .= '<p>Upload or select featured image for this event to displays it as background header and event image on event archive pages.</p>';
   
   //Testimonials help pointers
   $testimonials_options_content = '<h3>Testimonials Options</h3>';
   $testimonials_options_content .= '<p>You can customise various options for this testimonial including customer name, position, company etc.</p>';
   
   $testimonials_featured_image_content = '<h3>Testimonials Featured Image</h3>';
   $testimonials_featured_image_content .= '<p>Upload or select featured image for this testimonial to displays it as customer photo.</p>';
   
   //Client help pointers
   $clients_options_content = '<h3>Client Options</h3>';
   $clients_options_content .= '<p>You can customise various options for this client including password protected and client galleries.</p>';
   
   $clients_featured_image_content = '<h3>Client Featured Image</h3>';
   $clients_featured_image_content .= '<p>Upload or select featured image for this client to displays it as client photo.</p>';
   
   $clients_cover_image_content = '<h3>Client Cover Image</h3>';
   $clients_cover_image_content .= '<p>Upload or select cover image for this client to displays it as background header for client page.</p>';
   
   //Team Member help pointers
   $team_options_content = '<h3>Team Member Options</h3>';
   $team_options_content .= '<p>You can customise various options for this team member including position and social profiles URL.</p>';
   
   $team_featured_image_content = '<h3>Team Member Featured Image</h3>';
   $team_featured_image_content .= '<p>Upload or select featured image for this team member to displays it as team member photo.</p>';

   $tg_pointer_arr = array(
   
   	  //Page help pointers
      $prefix . '_content_builder' => array(
         'content' => $content_builder_content,
         'anchor_id' => '#enable_builder',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_content_builder', $dismissed ) )
      ),
      
      $prefix . '_page_options' => array(
         'content' => $page_options_content,
         'anchor_id' => 'body.post-type-page #page_option_page_menu_transparent',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_page_options', $dismissed ) )
      ),
      
      $prefix . '_page_featured_image' => array(
         'content' => $page_featured_image_content,
         'anchor_id' => 'body.post-type-page #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_page_featured_image', $dismissed ) )
      ),
      
      //Post help pointers
      $prefix . '_post_options' => array(
         'content' => $post_options_content,
         'anchor_id' => 'body.post-type-post #post_option_post_layout',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_post_options', $dismissed ) )
      ),
      
      $prefix . '_post_featured_image' => array(
         'content' => $post_featured_image_content,
         'anchor_id' => 'body.post-type-post #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_post_featured_image', $dismissed ) )
      ),
      
      //Gallery help pointers
      $prefix . '_gallery_images' => array(
         'content' => $gallery_images_content,
         'anchor_id' => 'body.post-type-galleries #wpsimplegallery_container',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_gallery_images', $dismissed ) )
      ),
      
      $prefix . '_gallery_options' => array(
         'content' => $gallery_options_content,
         'anchor_id' => 'body.post-type-galleries #metabox .inside',
         'edge' => 'left',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_gallery_options', $dismissed ) )
      ),
      
      $prefix . '_gallery_featured_image' => array(
         'content' => $gallery_featured_image_content,
         'anchor_id' => 'body.post-type-galleries #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_gallery_featured_image', $dismissed ) )
      ),
      
      //Portfolio help pointers
      $prefix . '_portfolio_options' => array(
         'content' => $portfolio_options_content,
         'anchor_id' => 'body.post-type-portfolios #metabox',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_portfolio_options', $dismissed ) )
      ),
      
      $prefix . '_portfolio_featured_image' => array(
         'content' => $portfolio_featured_image_content,
         'anchor_id' => 'body.post-type-portfolios #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_portfolio_featured_image', $dismissed ) )
      ),
      
      //Event help pointers
      $prefix . '_event_options' => array(
         'content' => $event_options_content,
         'anchor_id' => 'body.post-type-events #metabox .inside',
         'edge' => 'left',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_event_options', $dismissed ) )
      ),
      
      $prefix . '_event_featured_image' => array(
         'content' => $event_featured_image_content,
         'anchor_id' => 'body.post-type-events #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_event_featured_image', $dismissed ) )
      ),
      
      //Testimonials help pointers
      $prefix . '_testimonials_options' => array(
         'content' => $testimonials_options_content,
         'anchor_id' => 'body.post-type-testimonials #metabox #post_option_testimonial_name',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_testimonials_options', $dismissed ) )
      ),
      
      $prefix . '_testimonials_featured_image' => array(
         'content' => $event_featured_image_content,
         'anchor_id' => 'body.post-type-testimonials #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_testimonials_featured_image', $dismissed ) )
      ),
      
      //Client help pointers
      $prefix . '_clients_options' => array(
         'content' => $clients_options_content,
         'anchor_id' => 'body.post-type-clients #metabox #post_option_client_password',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_clients_options', $dismissed ) )
      ),
      
      $prefix . '_clients_featured_image' => array(
         'content' => $event_featured_image_content,
         'anchor_id' => 'body.post-type-clients #set-post-thumbnail',
         'edge' => 'bottom',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_clients_featured_image', $dismissed ) )
      ),
      
      $prefix . '_clients_cover_image' => array(
         'content' => $clients_cover_image_content,
         'anchor_id' => 'body.post-type-clients #set-clients-cover-image-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_clients_cover_image', $dismissed ) )
      ),
      
      //Team Member help pointers
      $prefix . '_team_options' => array(
         'content' => $team_options_content,
         'anchor_id' => 'body.post-type-team #metabox #post_option_team_position',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_team_options', $dismissed ) )
      ),
      
      $prefix . '_team_featured_image' => array(
         'content' => $team_featured_image_content,
         'anchor_id' => 'body.post-type-team #set-post-thumbnail',
         'edge' => 'top',
         'align' => 'left',
         'active' => ( ! in_array( $prefix . '_team_featured_image', $dismissed ) )
      ),
   );

   return $tg_pointer_arr;
}
?>