<?php
/*
*Plugin Name: Chargily Pay™
*Plugin URI: https://epay.chargily.com/
*Description: Accept CIB and EDAHABIA cards on your WooCommerce store..
*Author: Chargily
Author URI: https://epay.chargily.com/
*Version: 1.0.0
*Text Domain: chargily-woocommerce-gateway
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

if ( ! defined( 'CHARGILY_TEXT_DOMAIN' ) ) {
    define( 'CHARGILY_TEXT_DOMAIN', 'chargily-woocommerce-gateway' );
}

include ( plugin_dir_path( __FILE__ ) . 'templates/method-v2/API-v2.php');

load_plugin_textdomain(CHARGILY_TEXT_DOMAIN, false, basename(dirname(__FILE__)) . '/languages/');

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
        wp_enqueue_style('chargily-style-front', plugins_url('/assets/css/css-front.css?v=1.1', __FILE__));
    }
}

function chargily_js_loader_front() {
    wp_enqueue_script( 'chargily-script-front', plugins_url('/assets/js/js-front.js?v=1.1', __FILE__), array('jquery'), null, true );
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
