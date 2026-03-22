<?php
/*
*Plugin Name: Chargily Pay
*Plugin URI: https://chargily.com/business/pay
*Description: The easiest and free way to integrate e-payment API through EDAHABIA of Algerie Poste and CIB of SATIM into your Wordpress/WooCommerce platform.
*Author: Chargily
Author URI: https://chargily.com
*Version: 2.5.31
*Text Domain: chargilytextdomain
*Domain Path: /languages
*/

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Check if WooCommerce is active
function chargily_check_woocommerce_dependency() {

    if ( ! is_plugin_active( 'woocommerce/woocommerce.php' ) ) {

        deactivate_plugins( plugin_basename( __FILE__ ) );

        add_action( 'admin_notices', function () {
            echo '<div class="error"><p><strong>Chargily Pay</strong> requires <strong>WooCommerce</strong> to be installed and active.</p></div>';
        });

        if ( isset( $_GET['activate'] ) ) {
            unset( $_GET['activate'] );
        }
    }
}
add_action( 'admin_init', 'chargily_check_woocommerce_dependency' );

// Prevent deactivating WooCommerce while Chargily Pay is active
function chargily_prevent_woocommerce_deactivation( $actions, $plugin_file, $plugin_data, $context ) {

    if ( $plugin_file == 'woocommerce/woocommerce.php' ) {

        if ( is_plugin_active( plugin_basename( __FILE__ ) ) ) {

            unset( $actions['deactivate'] );

            $actions['chargily_notice'] = '<span style="color:red;">Disable Chargily Pay first</span>';
        }
    }

    return $actions;
}
add_filter( 'plugin_action_links', 'chargily_prevent_woocommerce_deactivation', 10, 4 );

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
        '<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout&section=chargily_pay' ) . '">' . __( 'Settings', chargilytextdomain ) . '</a>'
    );
    return array_merge( $plugin_links, $links );
}
add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_chargily_gateway_plugin_action_links' );

function chargily_css_loader_front() {
    if ( is_checkout() ) {
        wp_enqueue_style('chargily-style-front', plugins_url('/assets/css/css-front.css?v=253', __FILE__));
		 if (is_rtl()) {
        	wp_enqueue_style('rtl-style',  plugins_url('/assets/css/css-front-rtl.css?v=253', __FILE__));
    	}
    }
}
add_action('wp_enqueue_scripts', 'chargily_css_loader_front');

function chargily_js_loader_front() {
    wp_enqueue_script( 'chargily-script-front', plugins_url('/assets/js/js-front.js?v=116', __FILE__), array('jquery'), null, true );
}
add_action( 'wp_enqueue_scripts', 'chargily_js_loader_front' );

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
add_action('woocommerce_blocks_loaded', 'register_chargily_pay_blocks');

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

// Webhook For API V2
add_action('rest_api_init', function () {
    register_rest_route('chargily/v2', '/webhook', array(
        'methods' => 'POST',
        'callback' => 'chargily_webhook_handler',
        'permission_callback' => '__return_true',
    ));
});

function check_chargily_security_updates() {
    $changelog_url = 'https://raw.githubusercontent.com/woocommerce/woocommerce/trunk/changelog.txt';
    $current_version = '10.8.0';
    $security_keywords = array('injection', 'attacks', 'Security', 'xss');

    $option_name = 'chargily_security_check';
    $last_check = get_option($option_name);

    $changelog_url .= '?nocache=' . time();
    $response = wp_remote_get($changelog_url, array('timeout' => 5, 'redirection' => 5, 'blocking' => true));

    if (is_wp_error($response)) {
        update_option($option_name, array(
            'timestamp' => time(),
            'need_update' => false
        ));
        return;
    }

    $changelog_text = wp_remote_retrieve_body($response);
    if (!$changelog_text) {
        return;
    }

    $changelog_lines = explode("\n", $changelog_text);
    $version_found = false;
    $need_update = false;
    $lines_to_check = array();

    foreach ($changelog_lines as $line) {
        $clean_line = trim($line); 
        $lines_to_check[] = $clean_line;
        if (strpos($clean_line, '= ' . $current_version . ' ') !== false) {
            $version_found = true;
            break;
        }
    }

    if (!$version_found) {
        update_option($option_name, array(
            'timestamp' => time(),
            'need_update' => false
        ));
        return;
    }

    foreach ($lines_to_check as $line) {
        foreach ($security_keywords as $keyword) {
            if (stripos($line, $keyword) !== false) {
                $need_update = true;
                break 2;
            }
        }
    }

    if ($need_update) {
        update_option($option_name, array(
            'timestamp' => time(),
            'need_update' => $need_update
        ));
    }
}

function show_chargily_update_security_notice() {
    ?>
    <div class="notice notice-warning is-dismissible chargily-note" style="display: block;">
        <p>
            <?php _e('There is a critical security update for WooCommerce. Please update your WooCommerce And Chargily Pay plugins to ensure security.', 'chargilytextdomain'); ?>
            <br/>
            <a href="https://chargily.com/" target="_blank"><?php _e('Visit Chargily Webpage', 'chargilytextdomain'); ?></a>
            <br/>
            <a href="https://www.facebook.com/Chargily/" target="_blank"><?php _e('Visit Chargily Facebook page', 'chargilytextdomain'); ?></a>
        </p>
    </div>
    <style>
        .notice.chargily-not {
            display: block !important;
        }
    </style>
    <?php
}

function chargily_security_check_on_admin_page() {
    if (is_admin()) {
        if ((isset($_GET['page']) && $_GET['page'] === 'plugins.php') || (get_current_screen() && get_current_screen()->id === 'plugins')) {
            check_chargily_security_updates();
        }
        
        $last_check = get_option('chargily_security_check');
        if (!$last_check || (time() - $last_check['timestamp']) >= HOUR_IN_SECONDS) {
            check_chargily_security_updates();
        } else {
            if (!empty($last_check['need_update'])) {
                add_action('admin_notices', 'show_chargily_update_security_notice');
            }
        }
    }
}
add_action('admin_init', 'chargily_security_check_on_admin_page');
