<?php
use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

final class WC_Chargily_Pay_Blocks extends AbstractPaymentMethodType {
    protected $name = 'chargily_pay';

    public function initialize() {
        $this->settings = get_option('woocommerce_chargily_pay_settings', []);
    }

    public function is_active() {
        return 'yes' === $this->settings['enabled'];
    }

    public function get_payment_method_script_handles() {
        wp_register_script(
            'chargily-pay-blocks-integration',
            plugins_url('../../assets/js/checkout.js', __FILE__),
            ['wc-blocks-registry', 'wc-settings', 'wp-element', 'wp-i18n'],
            filemtime(plugin_dir_path(__FILE__) . '../../assets/js/checkout.js'),
            true
        );

        return ['chargily-pay-blocks-integration'];
    }

    public function get_payment_method_data() {
        return [
            'title'       => $this->settings['title'],
            'description' => $this->settings['description'],
        ];
    }
}
