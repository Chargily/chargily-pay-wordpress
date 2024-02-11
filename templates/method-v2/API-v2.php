<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

// Add the gateway to WC Available Gateways
function wc_chargilyv2_add_to_gateways( $gateways ) {
    $gateways[] = 'WC_chargily_pay';
    return $gateways;
}
add_filter( 'woocommerce_payment_gateways', 'wc_chargilyv2_add_to_gateways' );

function wc_chargily_pay_init() {

    class WC_chargily_pay extends WC_Payment_Gateway {

        public function __construct() {
            $this->id                 = 'chargily_pay';
            $this->icon               = apply_filters('woocommerce_chargilyv2_icon', '/wp-content/plugins/chargily-pay/assets/img/edahabia-card-cib.svg');
            $this->has_fields         = false;
            $this->method_title       = __( 'Chargily Pay™', '' );
            $this->method_description = __( 'Allow your customers to make payments using their Edahabia and CIB cards using Chargily Pay™ V2.', '' );

            // Load the settings.
            $this->init_form_fields();
            $this->init_settings();

            // Define user set variables
            $this->title        = $this->get_option( 'title' );
            $this->description  = $this->get_option( 'description' );
            
            // Actions
            add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
            add_action( 'woocommerce_receipt_' . $this->id, array( $this, 'receipt_page' ) );
            // Customer Emails
            add_action( 'woocommerce_email_before_order_table', array( $this, 'email_instructions' ), 10, 3 );
            // admin api notices
            add_action('admin_notices', array($this, 'display_chargily_admin_notices'));
        }

        public function init_form_fields() {
            $this->form_fields = array(
                'enabled' => array(
			'title'       => __('Enable/Disable', CHARGILY_TEXT_DOMAIN),
			'label'       => __('Enable Chargily Pay', CHARGILY_TEXT_DOMAIN),
			'type'        => 'checkbox',
			'description' => '',
			'default'     => 'no'
			),
			'test_mode' => array(
			'title'       => __('Test mode', CHARGILY_TEXT_DOMAIN),
			'label'       => __('Enable Test Mode', CHARGILY_TEXT_DOMAIN),
			'type'        => 'checkbox',
			'description' => __('If enabled, you will use Chargily Pay in Test Mode.', CHARGILY_TEXT_DOMAIN),
			'default'     => 'yes',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_key_v2_test' => array(
			'title'       => __('Test Public key', CHARGILY_TEXT_DOMAIN),
			'type'        => 'password',
			'description' => __('Enter your Chargily Test API key.', CHARGILY_TEXT_DOMAIN),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_secret_v2_test' => array(
			'title'       => __('Test Secret key', CHARGILY_TEXT_DOMAIN),
			'type'        => 'password',
			'description' => __('Enter your Chargily Test API secret.', CHARGILY_TEXT_DOMAIN),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_authorization_v2_test' => array(
			'title'       => __('Check API keys', CHARGILY_TEXT_DOMAIN),
			'type'        => 'button',
			'description' => __('Check your API keys.', CHARGILY_TEXT_DOMAIN),
			'default'     => 'Check connection',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_key_v2_live' => array(
			'title'       => __('Live Public key', CHARGILY_TEXT_DOMAIN),
			'type'        => 'password',
			'description' => __('Enter your Chargily Live API key.', CHARGILY_TEXT_DOMAIN),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_secret_v2_live' => array(
			'title'       => __('Live Secret key', CHARGILY_TEXT_DOMAIN),
			'type'        => 'password',
			'description' => __('Enter your Chargily Live API secret.', CHARGILY_TEXT_DOMAIN),
			'default'     => '',
			'desc_tip'    => true,
			),
			'Chargily_Gateway_api_authorization_v2_live' => array(
			'title'       => __('Check API keys', CHARGILY_TEXT_DOMAIN),
			'type'        => 'button',
			'description' => __('Check your API keys.', CHARGILY_TEXT_DOMAIN),
			'default'     => 'Check connection',
			'desc_tip'    => true,
			),
			'title' => array(
			'title'       => __('Title', CHARGILY_TEXT_DOMAIN),
			'type'        => 'text',
			'description' => __('This controls the title which the user sees during checkout.', CHARGILY_TEXT_DOMAIN),
			'default'     => __('Chargily Pay™ (EDAHABIA/CIB)', CHARGILY_TEXT_DOMAIN),
			'desc_tip'    => true,
			),
			'description' => array(
			'title'       => __('Description', CHARGILY_TEXT_DOMAIN),
			'type'        => 'textarea',
			'description' => __('This controls the description which the user sees during checkout.', CHARGILY_TEXT_DOMAIN),
			'default'     => __('🔒 Secure e-payment gateway.', CHARGILY_TEXT_DOMAIN),
			'desc_tip'    => true,
			),
			'instructions' => array(
			'title'       => __('On the thanks page', CHARGILY_TEXT_DOMAIN),
			'type'        => 'textarea',
			'placeholder' => __('thank you, the product will come soon.', CHARGILY_TEXT_DOMAIN),
			'description' => __('Place the message you want to appear on the thank you page after completing the purchase of the product.', CHARGILY_TEXT_DOMAIN),
			'default'     => __('', CHARGILY_TEXT_DOMAIN),
			'desc_tip'    => true,
			),
			'pass_fees_to_customer' => array(
			'title'       => __('Pass Fees To Customer', 'CHARGILY_TEXT_DOMAIN'),
			'label'       => __('Pass Fees To Customer', 'CHARGILY_TEXT_DOMAIN'),
			'type'        => 'checkbox',
			'description' => __('If enabled, Chargily Pay fees will be paid by your customers.', 'CHARGILY_TEXT_DOMAIN'),
			'default'     => 'yes', 
			),
			'create_products' => array(
			'title'       => __('Create Products', 'CHARGILY_TEXT_DOMAIN'),
			'label'       => __('Enable product creation on Chargily Pay.', 'CHARGILY_TEXT_DOMAIN'),
			'type'        => 'checkbox',
			'description' => __('If enabled, products will be created on Chargily Pay upon checkout.', 'CHARGILY_TEXT_DOMAIN'),
			'default'     => 'no'
			),
	    );
	}
		
		 public function admin_options() {
        		?>
			 <div style=" margin: 24px auto 0px; max-width: 1032px;">
				 <link rel="stylesheet" href="/wp-content/plugins/chargily-pay/assets/css/css-back.css?v=1.0">
				 <div class="css-q70wzv et1p4me2" style="display: flex;flex-flow: column;margin-bottom: 24px;  flex-direction: row;">
					 <div style="float: left; width: 30%;">
						 <div class="css-1p8kjge et1p4me1" bis_skin_checked="1">
							 <h2><?php echo esc_html__( 'General', 'CHARGILY_TEXT_DOMAIN' ); ?></h2>
							 <p><?php echo esc_html__( 'Activate or deactivate Chargily Pay on your store, input your API keys, and activate test mode to simulate purchases without real money.', 'CHARGILY_TEXT_DOMAIN' ); ?></p>
							 <p><a class="components-external-link" href="https://dev.chargily.com/pay-v2/api-keys" target="_blank" rel="external noreferrer noopener">
								 <?php echo esc_html__( 'Find out where to find your API keys', 'CHARGILY_TEXT_DOMAIN' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'CHARGILY_TEXT_DOMAIN' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
							 <p><a class="components-external-link" href="https://dev.chargily.com/pay-v2/test-and-live-mode" target="_blank" rel="external noreferrer noopener">
								<?php echo esc_html__( 'Find out where to find your API keys', 'CHARGILY_TEXT_DOMAIN' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'CHARGILY_TEXT_DOMAIN' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
							 <p><a class="components-external-link" href="https://chargi.link/WaPay" target="_blank" rel="external noreferrer noopener">
								 <?php echo esc_html__( 'Get support', 'CHARGILY_TEXT_DOMAIN' ); ?>
								 <span data-wp-c16t="true" data-wp-component="VisuallyHidden" class="components-visually-hidden css-0 e19lxcc00" style="">
								 (<?php echo esc_html__( 'opens in a new tab', 'CHARGILY_TEXT_DOMAIN' ); ?>)
								 </span>
								 <img src="/wp-content/plugins/chargily-pay/assets/img/link-out.svg" alt="link">
							 </a></p>
						 </div>
					 </div>
					 <div style="float: right; width: 90%;">
						 <div class="css-mkkf9p et1p4me0">
							 <div class="components-surface components-card css-cn3xcj e1ul4wtb1 css-1pd4mph e19lxcc00">
								 <div class="css-10klw3m e19lxcc00">
									 <div class="components-card__body components-card-body css-hqx46f eezfi080 css-188a3xf e19lxcc00">
										 <h2><?php _e('Chargily Pay™ Settings', 'chargily_text_domain'); ?></h2>
										 <table class="form-table">
                    									<?php $this->generate_settings_html(); ?>
                    								</table>
									 </div>
								 </div>
							 </div>
						 </div>	
					 </div>
				 </div>
			 </div>
		<?php
		}
		
		public function payment_fields() {
			$test_mode = $this->get_option('test_mode') === 'yes';
			$live_api_key = $this->get_option('Chargily_Gateway_api_key_v2_live');
			$live_api_secret = $this->get_option('Chargily_Gateway_api_secret_v2_live');
			$test_api_key = $this->get_option('Chargily_Gateway_api_key_v2_test');
			$test_api_secret = $this->get_option('Chargily_Gateway_api_secret_v2_test');

			echo '<div class="Chargily-container">';

			if ($test_mode) {
			    // We are in test mode
			    if (empty($test_api_key) || empty($test_api_secret)) {
			        // Test API keys are missing
			        echo '<div class="">
			                <p>' . __('You are in Test Mode but your Test API keys are missing.', 'CHARGILY_TEXT_DOMAIN') . ' 
			                <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your Test API keys.', 'CHARGILY_TEXT_DOMAIN') . '</a></p>
			              </div>';
			    } else {
			        // Test API keys are present
			        echo '<div class=""><p>' . __('Chargily Pay™: Test Mode is enabled.', 'CHARGILY_TEXT_DOMAIN') . '</p></div>';
			        // Display payment options
			echo '<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_edahabia" value="EDAHABIA" checked="checked" onclick="updateCookieValue(this)">
			
			  <label for="chargilyv2_edahabia" aria-label="royal" class="Chargily">
			  <span style="display: flex; align-items: center;"> <div style="opacity: 0;">card :</div><p>' . __('EDAHABIA', 'CHARGILY_TEXT_DOMAIN') . '</p></span>
			  
			  <div class="Chargily-card-text" style=""></div>
			  <img src="/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_cib" value="CIB" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_cib" aria-label="Silver" class="Chargily">
			  <span style="display: flex; align-items: center;"><div style="opacity: 0;">card :</div>
			   <p style="margin-top: 1.59em;">CIB </p><div style="opacity: 0;">-</div><p> Card</p></span>
			  <div class="Chargily-card-text" style=""></div>
			  <img src="/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB" style=""></img>
			  </label>
			</div>
			  
			<br>
			<a href="https://chargily.com/business/pay" target="_blank" style="/*font-weight:bold;*/ color:black;">
			  Powered By
			  <img src="/wp-content/plugins/chargily-pay/assets/img/logo.svg" alt="chargily" style="/*width:42px;height:42px;*/">
			  </a>
			   <p>' . __('🔒 Secure e-payment gateway.', 'CHARGILY_TEXT_DOMAIN') . '</p>';
			}
			} else {
			    // We are in live mode
			    if (empty($live_api_key) || empty($live_api_secret)) {
			        // Live API keys are missing
			        echo '<div class="">
			                <p>' . __('You are in Live Mode but your Live API keys are missing.', 'CHARGILY_TEXT_DOMAIN') . ' 
			                <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your Live API keys.', 'CHARGILY_TEXT_DOMAIN') . '</a></p>
			              </div>';
			    } else {
			        // Live API keys are present
			        // Display payment options
			       echo '<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_edahabia" value="EDAHABIA" checked="checked" onclick="updateCookieValue(this)">
			
			  <label for="chargilyv2_edahabia" aria-label="royal" class="Chargily">
			  <span style="display: flex; align-items: center;"> <div style="opacity: 0;">card :</div><p>' . __('EDAHABIA', 'CHARGILY_TEXT_DOMAIN') . '</p></span>
			  
			  <div class="Chargily-card-text" style=""></div>
			  <img src="/wp-content/plugins/chargily-pay/assets/img/edahabia-card.svg" alt="EDAHABIA" style="border-radius: 4px;"></img>
			  </label>
			</div>
			
			<div class="Chargily-option">
			  <input type="radio" name="chargilyv2_payment_method" id="chargilyv2_cib" value="CIB" onclick="updateCookieValue(this)">
			  <label for="chargilyv2_cib" aria-label="Silver" class="Chargily">
			  <span style="display: flex; align-items: center;"><div style="opacity: 0;">card :</div>
			   <p style="margin-top: 1.59em;">CIB </p><div style="opacity: 0;">-</div><p> Card</p></span>
			  <div class="Chargily-card-text" style=""></div>
			  <img src="/wp-content/plugins/chargily-pay/assets/img/cib-card.svg" alt="CIB" style=""></img>
			  </label>
			</div>
			  
			<br>
			<a href="https://chargily.com/business/pay" target="_blank" style="/*font-weight:bold;*/ color:black;">
			  Powered By
			  <img src="/wp-content/plugins/chargily-pay/assets/img/logo.svg" alt="chargily" style="/*width:42px;height:42px;*/">
			  </a>
			   <p>' . __('🔒 Secure e-payment gateway.', 'CHARGILY_TEXT_DOMAIN') . '</p>';
			}
			}
			
			echo '</div>';
		}
		
		private function get_api_credentials() {
			$test_mode = $this->get_option('test_mode') === 'yes';
			if ($test_mode) {
				return array(
					'api_key' => $this->get_option('Chargily_Gateway_api_key_v2_test'),
					'api_secret' => $this->get_option('Chargily_Gateway_api_secret_v2_test')
				);
			} else {
				return array(
					'api_key' => $this->get_option('Chargily_Gateway_api_key_v2_live'),
					'api_secret' => $this->get_option('Chargily_Gateway_api_secret_v2_live')
				);
			}
		}
		
		
		private function encrypt($data, $key) {
			$iv = openssl_random_pseudo_bytes(openssl_cipher_iv_length('aes-256-cbc'));
			$encrypted = openssl_encrypt($data, 'aes-256-cbc', $key, 0, $iv);
			return base64_encode($encrypted . '::' . $iv);
		}
		
		private function decrypt($data, $key) {
			list($encrypted_data, $iv) = array_pad(explode('::', base64_decode($data), 2), 2, null);
			if($iv === null) {
				throw new Exception('The IV is missing from the encrypted data!');
			}
			return openssl_decrypt($encrypted_data, 'aes-256-cbc', $key, 0, $iv);
		}

		private function get_encryption_key() {
			$secret_key = get_option('chargily_customers_secret_key');
			if (empty($secret_key)) {
				$secret_key = bin2hex(openssl_random_pseudo_bytes(32));
				update_option('chargily_customers_secret_key', $secret_key);
			}
			return $secret_key;
		}

		
		public function process_payment( $order_id ) {
			$credentials = $this->get_api_credentials();
			$order = wc_get_order( $order_id );
			//$payment_method = isset($_POST['chargilyv2_payment_method']) ? wc_clean($_POST['chargilyv2_payment_method']) : 'EDAHABIA';
			if (isset($_COOKIE['chargily_payment_method'])) {
				$selected_payment_method = isset($_COOKIE['chargily_payment_method']) ? wc_clean($_COOKIE['chargily_payment_method']) : 'EDAHABIA';
				$payment_method = $selected_payment_method;
			} else {
				$payment_method = 'EDAHABIA';
			}
			
			$pass_fees_to_customer_settings = $this->get_option('pass_fees_to_customer') === 'yes';

			if ($pass_fees_to_customer_settings) {
				 $pass_fees_to_customer = 1; // إذا كان الإعداد 'yes'، نمرر الرسوم إلى العميل
			} else {
				$pass_fees_to_customer = 0; // إذا لم يكن 'yes'، لا نمرر الرسوم
			}
			
			$encryption_key = $this->get_encryption_key();
			$user_id = get_current_user_id();
			$chargily_customers_id = null;

			if ($user_id) {
				$chargily_customers_id = get_user_meta($user_id, 'chargily_customers_id', true);
				if (!$chargily_customers_id) {
					$user_data = array(
						"name" => $order->get_billing_first_name(),
						"email" => $order->get_billing_email(),
						"phone" => $order->get_billing_phone(),
						"address" => array(
							"country" => $order->get_billing_country(),
							"state" => $order->get_billing_state(),
							"address" => $order->get_billing_address_1()
						)
					);
					$chargily_customers_id = $this->create_chargily_customer($user_data);
					if (is_wp_error($chargily_customers_id)) {
						wc_add_notice( $chargily_customers_id->get_error_message(), 'error' );
						return;
					}
					update_user_meta($user_id, 'chargily_customers_id', $chargily_customers_id);
				}
			} else {
				if (isset($_COOKIE['chargily_customers_id'])) {
					$decrypted_customer_id = $this->decrypt($_COOKIE['chargily_customers_id'], $encryption_key);
					$chargily_customers_id = $decrypted_customer_id;
				} else {
					$user_data = array(
						"name" => $order->get_billing_first_name(),
						"email" => $order->get_billing_email(),
						"phone" => $order->get_billing_phone(),
						"address" => array(
							"country" => $order->get_billing_country(),
							"state" => $order->get_billing_state(),
							"city" => $order->get_billing_city(),
							"postcode" => $order->get_billing_postcode(),
							"address_1" => $order->get_billing_address_1(),
							"address_2" => $order->get_billing_address_2()
						)
					);

					$chargily_customers_id = $this->create_chargily_customer($user_data);

					if (!is_wp_error($chargily_customers_id)) {
						$encrypted_customer_id = $this->encrypt($chargily_customers_id, $encryption_key);
						setcookie('chargily_customers_id', $encrypted_customer_id, time() + (365 * 24 * 60 * 60), "/");
					}
				}
			}

			$baseURL = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST'];
			$webhookEndpoint = $baseURL . '/wp-content/plugins/chargily-pay/templates/method-v2/API-v2_webhook.php';

			$create_products = $this->get_option('create_products') === 'yes';

			if ($create_products) {
				$items = $order->get_items();
				$items_data = array();
				foreach ($items as $item) {
					$product_id = $item->get_product_id();
					$product_name = $item->get_name();
					$product_quantity = $item->get_quantity();
					$product_total = $item->get_total();
					
					$product = wc_get_product( $product_id );
					$product_image_id = $product->get_image_id();
					$product_image_url = wp_get_attachment_image_url( $product_image_id, 'full' );
					$product_image_urls = $product_image_url ? array($product_image_url) : array();

					$created_product_id = $this->create_chargily_product(array(
						'name' => $product_name,
						"images" => $product_image_urls,
						"metadata" => array(
							"order_id" => (string)$order_id,
							"item_id" => (string)$product_id,
						),
					));

					if (is_wp_error($created_product_id)) {
						wc_add_notice($created_product_id->get_error_message(), 'error');
						return;
					}

					$created_price_id = $this->create_chargily_product_price($product_id, array(
						'amount' => $product_total,
						'currency' => 'dzd',
						'product_id' => $created_product_id
					));

					if (is_wp_error($created_price_id)) {
						wc_add_notice($created_price_id->get_error_message(), 'error');
						return;
					}

					$items_data[] = array(
						'price' => $created_price_id,
						'quantity' => (string)$product_quantity
					);
				}

				$payload = array(
					"metadata" => array("order_id" => (string)$order_id),
					"items" => $items_data,
					'payment_method'  => $payment_method,
					'customer_id'  => $chargily_customers_id,
					'pass_fees_to_customer'  => $pass_fees_to_customer,
					'success_url'     => $this->get_return_url( $order ),
					'failure_url'     => $order->get_cancel_order_url(),
					'webhook_endpoint' => $webhookEndpoint,
				);
			} else {
				$payload = array(
					"metadata" => array("order_id" => (string)$order_id),
					'amount'          => $order->get_total(),
					'currency'        => 'dzd',
					'payment_method'  => $payment_method,
					'customer_id'  => $chargily_customers_id,
					'pass_fees_to_customer'  => $pass_fees_to_customer,
					'success_url'     => $this->get_return_url( $order ),
					'failure_url'     => $order->get_cancel_order_url(),
					'webhook_endpoint' => $webhookEndpoint,
				);
			}


			if ($chargily_customers_id) {
				$payload['customer_id'] = $chargily_customers_id;
			}

			$response = $this->create_chargilyv2_checkout($payload);

			if (is_wp_error($response)) {
				wc_add_notice($response->get_error_message(), 'error');
				return;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['checkout_url'])) {
				$order->update_status('pending', __('Awaiting Chargily payment', 'CHARGILY_TEXT_DOMAIN'));
				wc_reduce_stock_levels($order_id);
				WC()->cart->empty_cart();

				return array(
					'result'   => 'success',
					'redirect' => $body['checkout_url']
				);
			} else {
				$error_message = isset($body['message']) ? $body['message'] : __(
					'An error occurred while processing your payment. Please try again.', 'CHARGILY_TEXT_DOMAIN');
				wc_add_notice($error_message, 'error');
				return;
			}
		}

		
		private function create_chargily_customer($user_data) {
			$credentials = $this->get_api_credentials();
			$api_url = $this->get_option( 'test_mode' ) === 'yes'
				? 'https://pay.chargily.net/test/api/v2/customers'
				: 'https://pay.chargily.net/api/api/v2/customers';

			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);

			$response = wp_remote_post( $api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode( $user_data ),
				'timeout'   => 45,
				'sslverify' => false,
			) );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			$body = json_decode( wp_remote_retrieve_body( $response ), true );
			if ( isset( $body['id'] ) ) {
				return $body['id'];
			} else {
				return new WP_Error( 'chargily_customer_creation_failed', __( 'Failed to create customer in Chargily.', 'CHARGILY_TEXT_DOMAIN' ) );
			}
		}

		private function create_chargily_product($product_data) {
			$credentials = $this->get_api_credentials();
			$product_id = $product_data['metadata']['item_id'];
			$product_meta_key = 'chargily_products_id_' . $product_id;
			$existing_product_id = get_post_meta($product_id, $product_meta_key, true);
			if ($existing_product_id) {
				return $existing_product_id;
			}

			$api_url = $this->get_option('test_mode') === 'yes'
				? 'https://pay.chargily.net/test/api/v2/products'
				: 'https://pay.chargily.net/api/api/v2/products';

			$credentials = $this->get_api_credentials();

			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);

			$response = wp_remote_post($api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode($product_data),
				'timeout'   => 45,
				'sslverify' => false,
			));

			if (is_wp_error($response)) {
				return $response;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['id'])) {
				update_post_meta($product_id, $product_meta_key, $body['id']);
				return $body['id'];
			} else {
				return new WP_Error('chargily_product_creation_failed', __('Failed to create product in Chargily.', 'CHARGILY_TEXT_DOMAIN'));
			}
		}

		
		private function create_chargily_product_price($product_id, $price_data) {
			$price_meta_key = 'chargily_products_price_id_' . $product_id;
			$existing_price_id = get_post_meta($product_id, $price_meta_key, true);
			if ($existing_price_id) {
				return $existing_price_id;
			}

			$api_url = $this->get_option('test_mode') === 'yes'
				? 'https://pay.chargily.net/test/api/v2/prices'
				: 'https://pay.chargily.net/api/api/v2/prices';

			$credentials = $this->get_api_credentials();

			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);

			$response = wp_remote_post($api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode($price_data),
				'timeout'   => 45,
				'sslverify' => false,
			));

			if (is_wp_error($response)) {
				return $response;
			}

			$body = json_decode(wp_remote_retrieve_body($response), true);
			if (isset($body['id'])) {
				update_post_meta($product_id, $price_meta_key, $body['id']);
				return $body['id'];
			} else {
				return new WP_Error('chargily_price_creation_failed', __('Failed to create price in Chargily.', 'CHARGILY_TEXT_DOMAIN'));
			}
		}
		
	        private function create_chargilyv2_checkout( $payload ) {
			
			$credentials = $this->get_api_credentials();
			$api_url = $this->get_option( 'test_mode' ) === 'yes'
			? 'https://pay.chargily.net/test/api/v2/checkouts'
			: 'https://pay.chargily.net/api/api/v2/checkouts';
	    
			$headers = array(
				'Authorization' => 'Bearer ' . $credentials['api_secret'],
				'Content-Type'  => 'application/json',
			);
	
			$response = wp_remote_post( $api_url, array(
				'method'    => 'POST',
				'headers'   => $headers,
				'body'      => json_encode( $payload ),
				'timeout'   => 45,
				'sslverify' => false,
			) );
			return $response;
		}
			
	        public function receipt_page( $order ) {
	            echo '<p>' . __( 'Thank you for your order, please click the button below to pay with Chargily.', 'CHARGILY_TEXT_DOMAIN' ) . '</p>';
	        }
	
	    		
	    public function thankyou_page() {
		    if ( $this->instructions ) {
			    echo wpautop( wptexturize( $this->instructions ) );
		    }
	    }
	
	        public function email_instructions( $order, $sent_to_admin, $plain_text = false ) {
	            if ( $this->instructions && ! $sent_to_admin && $this->id === $order->get_payment_method() && $order->has_status( 'pending' ) ) {
	                echo wpautop( wptexturize( $this->instructions ) ) . PHP_EOL;
	            }
	        }

		public function display_chargily_admin_notices() {
			
			if ((!empty($this->get_option('Chargily_Gateway_api_key_v2_live')) 
				 && !empty($this->get_option('Chargily_Gateway_api_secret_v2_live'))) ||
				(!empty($this->get_option('Chargily_Gateway_api_key_v2_test')) 
				 && !empty($this->get_option('Chargily_Gateway_api_secret_v2_test')))) {
				//
				} else {
					echo '<div class="notice notice-error">
					<p>' . __('Just one more step to complete the setup of Chargily Pay™ and begin accepting payments.', 'CHARGILY_TEXT_DOMAIN') . ' <a href="/wp-admin/admin.php?page=wc-settings&tab=checkout&section=chargily_pay">' . __('Enter your API keys.', 'CHARGILY_TEXT_DOMAIN') . '</a></p></div>';
				}

			// Check for test mode
			if ($this->get_option('test_mode') === 'yes') {
				echo '<div class="notice notice-warning"><p>
				' . __('Chargily Pay™: Test Mode is enabled.', 'CHARGILY_TEXT_DOMAIN') . '
				</p></div>';
			}
		}
		
		// END WC Chargily V2
    }
}
// The class itself
add_action( 'plugins_loaded', 'wc_chargily_pay_init', 11 );


function chargilyv2_admin_inline_scripts() {
	if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			$screen = get_current_screen();
			if ($screen->id === 'woocommerce_page_wc-settings') {
				?>
				<script type="text/javascript">
					jQuery(document).ready(function($) {
						toggleApiFields($('#woocommerce_chargily_pay_test_mode').is(':checked'));

						$('#woocommerce_chargily_pay_test_mode').on('change', function() {
							toggleApiFields($(this).is(':checked'));
						});

						$('input[type="password"]').each(function() {
							var $passwordField = $(this);
							$passwordField.after('<button type="button" class="button toggle-password" data-toggle="' + $passwordField.attr('id') + '" aria-label="<?php esc_attr_e('Show password', 'woocommerce'); ?>"><span class="dashicons dashicons-visibility"></span></button>');
						});

						// Toggle password visibility
						$('body').on('click', '.toggle-password', function(e) {
							e.preventDefault();
							var $this = $(this),
								$password_field = $('#' + $this.data('toggle'));

							if ($password_field.attr('type') === 'password') {
								$password_field.attr('type', 'text');
								$this.find('.dashicons').removeClass('dashicons-visibility').addClass('dashicons-hidden');
							} else {
								$password_field.attr('type', 'password');
								$this.find('.dashicons').removeClass('dashicons-hidden').addClass('dashicons-visibility');
							}
						});

						function toggleApiFields(isTestMode) {
							$('.form-table tr').each(function() {
								var row = $(this);
								if (
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_key_v2_test' ||
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_test' || 
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test'
								   ) {
									isTestMode ? row.show() : row.hide();
								}
								if (
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_key_v2_live' ||
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_live' || 
							row.find('input, select').attr('id') === 'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live'

								   ) {
									isTestMode ? row.hide() : row.show();
								}
							});
						}

						$('#woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test').on('click', function() {
							var token = $('#woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_test').val();
							checkConnection(token, 'test');
						});

						$('#woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live').on('click', function() {
							var token = $('#woocommerce_chargily_pay_Chargily_Gateway_api_secret_v2_live').val();
							checkConnection(token, 'live');
						});

						function checkConnection(token, mode) {
							var url = mode === 'test' ? 'https://pay.chargily.net/test/api/v2/balance' : 'https://pay.chargily.net/api/api/v2/balance';
							$.ajax({
								url: ajaxurl,
								type: 'POST',
								data: {
									'action': 'check_chargily_connection',
									'token': token,
									'mode': mode
								},
								success: function(response) {
									if (response.success && response.data && response.data.message) {
										alert(response.data.message);
									} else {
										alert('Unknown response, try later');
									}
								},
								error: function(jqXHR, textStatus, errorThrown) {
									alert('خطأ في الاتصال بالخادم: ' + textStatus);
								}
							});
						}

						var inputElement = document.getElementById('woocommerce_chargily_pay_title');

						inputElement.setAttribute('readonly', true);
						
						var button_authorization_v2_test = document.getElementById(
							'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_test');
						if (button_authorization_v2_test) {
						  button_authorization_v2_test.value = 'Check connection';
						}
						
						var button_authorization_v2_live = document.getElementById(
							'woocommerce_chargily_pay_Chargily_Gateway_api_authorization_v2_live');
						if (button_authorization_v2_live) {
						  button_authorization_v2_live.value = 'Check connection';
						}
					});
				</script>
				<style>
				 
				</style>
				<?php
			}
		}
	}
}
add_action('admin_footer', 'chargilyv2_admin_inline_scripts');

add_action('init', 'register_custom_order_status');
function register_custom_order_status() {
    register_post_status('wc-expired', array(
        'label'                     => _x('Expired', 'Order status', 'CHARGILY_TEXT_DOMAIN'),
        'public'                    => true,
        'exclude_from_search'       => false,
        'show_in_admin_all_list'    => true,
        'show_in_admin_status_list' => true,
        'label_count'               => _n_noop('Expired (%s)', 'Expired (%s)', 'CHARGILY_TEXT_DOMAIN')
    ));
}

add_filter('wc_order_statuses', 'add_custom_order_status');
function add_custom_order_status($order_statuses) {
    $new_order_statuses = array();
    foreach ($order_statuses as $key => $status) {
        $new_order_statuses[$key] = $status;
        if ('wc-processing' === $key) {
            $new_order_statuses['wc-expired'] = _x('Expired', 'Order status', 'CHARGILY_TEXT_DOMAIN');
        }
    }
    return $new_order_statuses;
}

add_filter('bulk_actions-edit-shop_order', 'custom_dropdown_bulk_actions_shop_order');
function custom_dropdown_bulk_actions_shop_order($actions) {
    $actions['mark_expired'] = __('Change status to expired', 'CHARGILY_TEXT_DOMAIN');
    return $actions;
}

function chargilyv2_enqueue_payment_scripts() {
    if (is_checkout()) {
        ?>
        <script type="text/javascript">
            jQuery(function($) {
                $('form.checkout').on('submit', function(e) {
                    var selectedv2_payment_method = $('input[name="chargilyv2_payment_method"]:checked').val();
                    $('<input>').attr({
                        type: 'hidden',
                        name: 'chargilyv2_payment_method',
                        value: selectedv2_payment_method
                    }).appendTo('form.checkout');
                });
            });
        </script>
        <?php
    }
}
add_action('wp_footer', 'chargilyv2_enqueue_payment_scripts');


add_action('wp_ajax_check_chargily_connection', 'check_chargily_connection_callback');
function check_chargily_connection_callback() {
    if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			$token = isset($_POST['token']) ? sanitize_text_field($_POST['token']) : '';
			$mode = isset($_POST['mode']) ? sanitize_text_field($_POST['mode']) : 'test';
			$url = $mode === 'test' ? 'https://pay.chargily.net/test/api/v2/balance' : 'https://pay.chargily.net/api/api/v2/balance';

			$ch = curl_init();
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
			curl_setopt($ch, CURLOPT_HTTPHEADER, array(
				'Authorization: Bearer ' . $token
			));

			$response = curl_exec($ch);
			$httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
			curl_close($ch);

			if ($httpcode == 200) {
				wp_send_json_success(array('message' => 'Correct API keys.'));
			} elseif ($httpcode == 401) {
				wp_send_json_success(array('message' => 'Wrong API keys.'));
			} else {
				wp_send_json_error(array('message' => 'Unknown response, try later'));
			}
		}
	}
}


add_action('woocommerce_update_options_payment_gateways_chargily_pay', 'update_chargily_pay_settings');

function update_chargily_pay_settings() {
	if ( is_admin() ) {
		if (current_user_can('administrator') || current_user_can('shop_manager')) {
			$test_mode = 'yes' === get_option('woocommerce_chargily_pay_settings')['test_mode'];
			$live_api_key_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_key_v2_live']);
			$live_api_secret_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_secret_v2_live']);
			$test_api_key_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_key_v2_test']);
			$test_api_secret_present = !empty(get_option('woocommerce_chargily_pay_settings')['Chargily_Gateway_api_secret_v2_test']);

			$data = array(
				'testMode' => $test_mode,
				'liveApiKeyPresent' => $live_api_key_present,
				'liveApiSecretPresent' => $live_api_secret_present,
				'testApiKeyPresent' => $test_api_key_present,
				'testApiSecretPresent' => $test_api_secret_present,
			);
			$file_path = plugin_dir_path(__FILE__) . 'chargily_data.json';
			file_put_contents($file_path, json_encode($data));
		}
	}
}
