<?php

/**
 * Load WordPress environment
 */
$parse_uri = explode('wp-content', $_SERVER['SCRIPT_FILENAME']);
require_once($parse_uri[0] . 'wp-load.php');

// Get the settings array from the WooCommerce settings
$chargily_settings = get_option('woocommerce_chargily_pay_settings');
$response_type = $chargily_settings['response_type'] ?? 'completed';

// Check if the settings are available
if (!empty($chargily_settings)) {
    // Check if test mode is enabled
    if (isset($chargily_settings['test_mode']) && $chargily_settings['test_mode'] === 'yes') {
        // Use test API secret key
        $apiSecretKey = $chargily_settings['Chargily_Gateway_api_secret_v2_test'] ?? '';
    } else {
        // Use live API secret key
        $apiSecretKey = $chargily_settings['Chargily_Gateway_api_secret_v2_live'] ?? '';
    }

    // Check if the API secret key is not empty
    if (empty($apiSecretKey)) {
        // Handle the error if API secret key is empty
        //error_log('Chargily API Secret is not set in the settings.');
        header("HTTP/1.1 500 Internal Server Error");
        //echo 'Internal Server Error. The API Secret is not configured.';
        exit;
    }
} else {
    // Handle the error if settings are not available
    //error_log('Chargily settings are not available.');
    header("HTTP/1.1 500 Internal Server Error");
   // echo 'Internal Server Error. Chargily settings are not available.';
    exit;
}


// Extracting the 'signature' header from the HTTP request
$signature = isset($_SERVER['HTTP_SIGNATURE']) ? $_SERVER['HTTP_SIGNATURE'] : null;

// Getting the raw payload from the request body
$payload = file_get_contents('php://input');

// If there is no signature, exit the script
if (!$signature) {
    header("HTTP/1.1 400 Bad Request");
    //echo 'No signature provided.';
    exit;
}

// Calculate the signature
$computedSignature = hash_hmac('sha256', $payload, $apiSecretKey);

// If the calculated signature doesn't match the received signature, exit the script
if (!hash_equals($signature, $computedSignature)) {
    header("HTTP/1.1 400 Bad Request");
    //echo 'Invalid signature.';
    exit;
}

// If the signatures match, proceed to decode the JSON payload
$data_array = json_decode($payload, true);

/**
 * Function to update the order status based on webhook data
 */
function update_order_status($data) {	
    // Check if metadata exists and contains order_id
    if (isset($data['data']['metadata']['order_id'])) {
        $order_id = $data['data']['metadata']['woocommerce_order_id'];
    } else {
        // Handle error if order_id is not found
        //error_log('Order ID not found in metadata');
        return;
    }

    $status = $data['data']['status']; // Payment status

    // Load WooCommerce environment
    if (!function_exists('wc_get_order')) {
        include_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');
    }

    // Get the order using the ID
    $order = wc_get_order($order_id);

    if ($order) {
		$chargily_settings = get_option('woocommerce_chargily_pay_settings');
        $response_type = $chargily_settings['response_type'] ?? 'completed';
        switch ($status) {
           case 'paid':
                if ($order->has_status(array('pending', 'processing'))) {
                    $order->payment_complete();
                    $order->update_status($response_type, __('Payment successfully received.', 'woocommerce'));
                    $order->save();
                }
                break;
            case 'canceled':
                if (!$order->has_status('cancelled')) {
			$order->update_status('cancelled', __('Payment has been cancelled.', 'woocommerce'));
			$order->save();
                }
                break;
            case 'failed':
                if (!$order->has_status('failed')) {
			$order->update_status('failed', __('Payment has been failed.', 'woocommerce'));
			$order->save();
                }
                break;
			case 'expired':
                if (!$order->has_status('expired')) {
			$order->update_status('expired', __('Payment has expired.', 'woocommerce'));
			$order->save();
                }
                break;
            default:
                $order->add_order_note(sprintf(
                    __('Received unknown payment status from Chargily: %s', 'woocommerce'),
                    $status
                ));
                break;
        }
        // Save the changes to the order
        $order->save();
    }
}


/**
 * Listen for POST requests and log the data
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Check if the data contains 'entity' and the value is 'event'
    if (isset($data_array['entity']) && $data_array['entity'] === 'event') {
        update_order_status($data_array, $response_type);
    }

    // Send a response back to acknowledge receipt of the webhook
    header('Content-Type: application/json');
    echo json_encode(['status' => 'success', 'message' => 'Webhook data received and processed.']);
    exit;
} else {
    // If the request is not POST, send an error response
    header("HTTP/1.1 400 Bad Request");
    //echo 'Invalid request method.';
    exit;
}

// Respond with a 200 OK status code to let us know that you've received the webhook
http_response_code(200);
?>
