<?php
/*
*Plugin Name: Chargily Pay™
*Plugin URI: https://chargily.com/
*Description: Accept CIB and EDAHABIA cards on your WooCommerce store.
*Author: Chargily
Author URI: https://chargily.com/
*Version: 2.0.8
*Text Domain: chargilytextdomain
*Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! defined( 'chargilytextdomain' ) ) {
    define( 'chargilytextdomain', 'chargilytextdomain' );
}

function chargily_load_textdomain() {
    load_plugin_textdomain( chargilytextdomain, false, basename( dirname( __FILE__ ) ) . '/languages/' );
}
add_action( 'plugins_loaded', 'chargily_load_textdomain' );

include ( plugin_dir_path( __FILE__ ) . 'templates/method-v2/API-v2.php');

// Plugin action links
function wc_chargily_gateway_plugin_action_links( $links ) {
    $plugin_links = array(
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=chargily_pay' ) . '">' . __( 'Settings', CHARGILY_TEXT_DOMAIN ) . '</a>'
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_chargily_gateway_plugin_action_links' );

// Webhook For API V2
function chargilyv2_add_rewrite_rule() {
	add_rewrite_rule('^chargilyv2-webhook/?$', 'wp-content/plugins/chargily-pay/templates/method-v2/API-v2_webhook.php', 'top');
}
add_action('init', 'chargilyv2_add_rewrite_rule');

add_action('wp_enqueue_scripts', 'chargily_css_loader_front');
function chargily_css_loader_front() {
    if ( is_checkout() ) {
        wp_enqueue_style('chargily-style-front', plugins_url('/assets/css/css-front.css?v=28', __FILE__));
		 if (is_rtl()) {
        	wp_enqueue_style('rtl-style',  plugins_url('/assets/css/css-front-rtl.css?v=28', __FILE__));
    	}
    }
}

function chargily_js_loader_front() {
    wp_enqueue_script( 'chargily-script-front', plugins_url('/assets/js/js-front.js?v=28', __FILE__), array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'chargily_js_loader_front' );

function wc_chargilyv2_set_default_payment_gateway( $gateways ) {
    if ( isset( $gateways['chargily_pay'] ) ) {
        $chargily_gateway = $gateways['chargily_pay'];
        unset( $gateways['chargily_pay'] );
        $gateways = array_merge( array( 'chargily_pay' => $chargily_gateway ), $gateways );
    }
    return $gateways;
}
add_filter( 'woocommerce_available_payment_gateways', 'wc_chargilyv2_set_default_payment_gateway', 999 );

add_action('woocommerce_blocks_loaded', 'register_chargily_pay_blocks');
function register_chargily_pay_blocks() {
    if (!class_exists('Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType')) {
        return;
    }
    require_once plugin_dir_path(__FILE__) . '/templates/method-v2/class-wc-chargily-pay-blocks.php';
    add_action(
        'woocommerce_blocks_payment_method_type_registration',
        function($payment_method_registry) {
            $payment_method_registry->register(new WC_Chargily_Pay_Blocks());
        }
    );
}

function chargily_copy_language_files() {
    $source_path = plugin_dir_path( __FILE__ ) . 'languages/';
    $destination_path = WP_CONTENT_DIR . '/languages/plugins/';
    if ( ! file_exists( $destination_path ) ) {
        wp_mkdir_p( $destination_path );
    }
    $language_files = [
        'chargily-woocommerce-gateway-ar.mo',
        'chargily-woocommerce-gateway-ar.po',
        'chargily-woocommerce-gateway-fr_FR.mo',
        'chargily-woocommerce-gateway-fr_FR.po',
    ];
    foreach ( $language_files as $file ) {
        $source_file = $source_path . $file;
        $destination_file = $destination_path . $file;

        if ( file_exists( $source_file ) ) {
            copy( $source_file, $destination_file );
        }
    }
}
register_activation_hook( __FILE__, 'chargily_copy_language_files' );
add_action( 'upgrader_process_complete', 'chargily_copy_language_files', 10, 2 );
