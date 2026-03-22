<?php
/**
 * @package   Barn2\woocommerce-multiple-email-recipients
 * @author    Barn2 Plugins <info@barn2.com>
 * @license   GPL-3.0
 * @copyright Barn2 Media Ltd
 *
 * @wordpress-plugin
 * Plugin Name:     WooCommerce Multiple Email Recipients
 * Plugin URI:      https://barn2.com/woocommerce-multiple-email-recipients/
 * Update URI:      https://barn2.com/woocommerce-multiple-email-recipients/
 * Description:     Stores multiple email addresses for each customer.
 * Version:         1.2.12
 * Author:          Barn2 Plugins
 * Author URI:      https://barn2.com
 * Text Domain:     woocommerce-multiple-email-recipients
 * Domain Path:     /languages
 *
 * Requires PHP: 7.4
 * Requires at least: 6.1
 * Requires plugins: woocommerce
 * WC requires at least:  7.2
 * WC tested up to: 9.4.3
 *
 * Copyright:       Barn2 Media Ltd
 * License:         GNU General Public License v3.0
 * License URI:     http://www.gnu.org/licenses/gpl-3.0.html
 */
namespace Barn2\Plugin\WC_Multiple_Email_Recipients;

// Prevent direct access.
if ( ! \defined( 'ABSPATH' ) ) {
	exit;
}

const PLUGIN_VERSION = '1.2.12';
const PLUGIN_FILE    = __FILE__;

// Include autoloader.
require_once __DIR__ . '/vendor/autoload.php';

/**
 * Helper function to access the shared plugin instance.
 *
 * @return Plugin
 */
function wmer() {
	return Plugin_Factory::create( PLUGIN_FILE, PLUGIN_VERSION );
}

// Load the plugin.
wmer()->register();
