<?php
/**
 * Plugin Name: 	Woocommerce Print Connector
 * Plugin URI: 		https://ibiz.fr/wp-admin/admin.php?page=wc_print_settings
 * Description: 	Woocommerce Print Connector
 * Version: 		1.0.0
 * Author: 			ibiz.fr
 * Author URI: 		ibiz.fr
 * Text Domain: 	wc-print
 * since 23/10/2024
 * version 1.0.0
 */



if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

// Define WC_PRINT_PLUGIN_FILE.
if ( ! defined( 'WC_PRINT_PLUGIN_FILE' ) ) {
	define( 'WC_PRINT_PLUGIN_FILE', __FILE__ );
}

if ( ! defined( 'WC_PRINT_PLUGIN_DIRE' ) ) {
	define( 'WC_PRINT_PLUGIN_DIRE', dirname( __FILE__ ) . '/' );
}

if ( ! defined( 'WC_PRINT_PLUGIN_URL' ) ) {
	define( 'WC_PRINT_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
}

// Check PHP version.
if ( version_compare( PHP_VERSION, '5.3.0' ) < 0 ) {
    add_action( 'admin_notices', function (){
        // Translators: PHP version.
        $message = sprintf( __( "You need to be running PHP 5.3+ for Woocommerce Print to work. You're on %s.", 'wc-print' ), PHP_VERSION );

        echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
    } );

    return false;
}
include_once( ABSPATH . 'wp-admin/includes/plugin.php' );


// Check Woocommerce Installation.
if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
    add_action( 'admin_notices', function (){
        // Translators: PHP version.
        $message = sprintf( __( "Woocommerce Print requires %s to be installed and active.", 'wc-print' ), "WooCommerce" );
        echo '<div class="error"><p>' . esc_html( $message ) . '</p></div>';
    } );
    return false;
}
include_once dirname( __FILE__ ) . '/global-functions.php';

/**
 * The code that runs during plugin activation.
 */
function on_activate_wc_print() {
    require_once plugin_dir_path( __FILE__ ) . 'classes/core/class-wc-print-installer.php';
    WC_Print_Installer::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function on_deactivate_wc_print() {
    require_once plugin_dir_path( __FILE__ ) . 'classes/core/class-wc-print-installer.php';
    WC_Print_Installer::deactivate();
}
register_activation_hook( __FILE__, 'on_activate_wc_print' );
register_deactivation_hook( __FILE__, 'on_deactivate_wc_print' );
// Include the main Woocommerce Print class.
if ( ! class_exists( 'WP_Print' ) ) {
	include_once dirname( __FILE__ ) . '/classes/class-wc-print.php';
}

/**
 * Main instance of WooCommerce Print.
 *
 * Returns the main instance of WC to prevent the need to use globals.
 *
 * @since  2.1
 * @return Woocommerce Print
 */
function wc_print() {
	return WC_Print::instance();
}
$GLOBALS['wc_print'] = wc_print();
