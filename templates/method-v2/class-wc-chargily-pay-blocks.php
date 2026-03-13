<?php

if (!defined('ABSPATH')) {
    exit;
}

// templates\method-v2\class-wc-chargily-pay-blocks.php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Chargily_Pay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'chargily_pay';
    protected $settings;

    public function initialize() {
        $this->settings = get_option('woocommerce_chargily_pay_settings', []);
        // $this->settings = array_map('sanitize_text_field', $this->settings);
    }

    public function is_active() {
        return isset($this->settings['enabled']) && 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'chargily-pay-blocks-integration',
            plugins_url('../../assets/js/checkout.js', __FILE__),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n', 'wp-hooks'],
            filemtime(plugin_dir_path(__FILE__) . '../../assets/js/checkout.js'),
            true
        );
        
        $test_mode = isset($this->settings['test_mode']) && 'yes' === $this->settings['test_mode'];
        
        $payment_methods = isset($this->settings['selected_payment_methods']) ? $this->settings['selected_payment_methods'] : ['EDAHABIA','CIB','QR'];
        
        if (is_string($payment_methods)) {
            $payment_methods = maybe_unserialize($payment_methods);
            if (!is_array($payment_methods)) {
                $payment_methods = [];
            }
        }
        
		$valid_methods = ['EDAHABIA', 'CIB', 'QR'];
        $payment_methods = array_intersect($payment_methods, $valid_methods);
        
        if (empty($payment_methods)) {
            $payment_methods = ['EDAHABIA', 'CIB', 'QR'];
        }
        
        $testMode = $test_mode ? 'yes' : 'no';
        $liveApiKeyPresent = !empty($this->settings['Chargily_Gateway_api_key_v2_live']) ? 'yes' : '';
        $liveApiSecretPresent = !empty($this->settings['Chargily_Gateway_api_secret_v2_live']) ? 'yes' : '';
        $testApiKeyPresent = !empty($this->settings['Chargily_Gateway_api_key_v2_test']) ? 'yes' : '';
        $testApiSecretPresent = !empty($this->settings['Chargily_Gateway_api_secret_v2_test']) ? 'yes' : '';
        
        wp_localize_script(
            'chargily-pay-blocks-integration',
            'chargilySettings',
            [
                'title'       => isset($this->settings['title']) ? esc_html($this->settings['title']) : '',
                'description' => isset($this->settings['description']) ? esc_html($this->settings['description']) : '',
                'show_payment_methods' => isset($this->settings['show_payment_methods']) ? esc_html($this->settings['show_payment_methods']) : 'yes',
                'payment_methods' => $payment_methods,
                'testMode' => $testMode,
                'liveApiKeyPresent' => $liveApiKeyPresent,
                'liveApiSecretPresent' => $liveApiSecretPresent,
                'testApiKeyPresent' => $testApiKeyPresent,
                'testApiSecretPresent' => $testApiSecretPresent,
				'assetsUrl' => plugins_url('../../assets/', __FILE__),
            ]
        );
        
        return ['chargily-pay-blocks-integration'];
    }

    public function get_payment_method_data() {
        $title = isset($this->settings['title']) ? esc_html($this->settings['title']) : __('Chargily Pay™', 'chargilytextdomain');
        $description = isset($this->settings['description']) ? esc_html($this->settings['description']) : '';
        $show_payment_methods = isset($this->settings['show_payment_methods']) ? $this->settings['show_payment_methods'] : 'yes';
        
        $payment_methods = isset($this->settings['selected_payment_methods']) ? $this->settings['selected_payment_methods'] : ['EDAHABIA','CIB','QR'];
        
        if (is_string($payment_methods)) {
            $payment_methods = maybe_unserialize($payment_methods);
            if (!is_array($payment_methods)) {
                $payment_methods = [];
            }
        }
        
        $valid_methods = ['EDAHABIA', 'CIB', 'QR'];
        $payment_methods = array_intersect($payment_methods, $valid_methods);
        
        if (empty($payment_methods)) {
            $payment_methods = ['EDAHABIA', 'CIB', 'QR'];
        }
        
        $test_mode = isset($this->settings['test_mode']) && 'yes' === $this->settings['test_mode'];

        return [
            'title'       => $title,
            'description' => $description,
            'show_payment_methods' => $show_payment_methods,
            'payment_methods' => $payment_methods,
            'testMode' => $test_mode,
            'liveApiKeyPresent' => !empty($this->settings['Chargily_Gateway_api_key_v2_live']),
            'liveApiSecretPresent' => !empty($this->settings['Chargily_Gateway_api_secret_v2_live']),
            'testApiKeyPresent' => !empty($this->settings['Chargily_Gateway_api_key_v2_test']),
            'testApiSecretPresent' => !empty($this->settings['Chargily_Gateway_api_secret_v2_test']),
			'assetsUrl' => plugins_url('../../assets/', __FILE__),
        ];
    }
}
