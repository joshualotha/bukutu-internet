<?php

/**
 * PHPNuxBill Pesapal Payment Gateway Plugin
 * 
 * Pesapal v3 API Integration
 * Supports: Uganda, Tanzania, Kenya
 * Payment methods: Mobile Money, Card, Airtel Money, MTN Mobile Money
 *
 * @package    Buku Tu Internet
 * @author     Buku Tu Internet
 * @link       https://pay.pesapal.com/v3
 */

function pesapal_validate_config()
{
    global $config;
    $required = ['pesapal_consumer_key', 'pesapal_consumer_secret', 'pesapal_environment'];
    foreach ($required as $key) {
        if (empty($config[$key])) {
            Message::sendTelegram("Pesapal payment gateway not configured");
            r2(U . 'order/package', 'w', Lang::T("Admin has not yet setup Pesapal payment gateway, please tell admin"));
        }
    }
}

function pesapal_show_config()
{
    global $ui, $_L;
    $ui->assign('_title', 'Pesapal - Payment Gateway');
    $ui->assign('cur', json_decode(file_get_contents('system/paymentgateway/pesapal_currency.json'), true));
    $ui->assign('methods', json_decode(file_get_contents('system/paymentgateway/pesapal_methods.json'), true));
    $ui->assign('_L', $_L);
    $ui->display('pesapal.tpl');
}

function pesapal_save_config()
{
    global $admin, $_L;

    $fields = [
        'pesapal_consumer_key'    => _post('pesapal_consumer_key'),
        'pesapal_consumer_secret' => _post('pesapal_consumer_secret'),
        'pesapal_environment'     => _post('pesapal_environment'),
        'pesapal_currency'        => _post('pesapal_currency'),
        'pesapal_ipn_id'          => _post('pesapal_ipn_id'),
        'pesapal_brand_name'      => _post('pesapal_brand_name') ?: 'Buku Tu Internet',
        'pesapal_country_code'    => _post('pesapal_country_code') ?: 'UG',
    ];

    foreach ($fields as $setting => $value) {
        $d = ORM::for_table('tbl_appconfig')->where('setting', $setting)->find_one();
        if ($d) {
            $d->value = $value;
            $d->save();
        } else {
            $d = ORM::for_table('tbl_appconfig')->create();
            $d->setting = $setting;
            $d->value = $value;
            $d->save();
        }
    }

    // Auto-register IPN URL with Pesapal if credentials changed
    if (!empty($fields['pesapal_consumer_key']) && !empty($fields['pesapal_consumer_secret'])) {
        pesapal_register_ipn();
    }

    _log('[' . $admin['username'] . ']: Pesapal ' . $_L['Settings_Saved_Successfully'], 'Admin', $admin['id']);
    r2(U . 'paymentgateway/pesapal', 's', $_L['Settings_Saved_Successfully']);
}

/**
 * Get OAuth2 access token from Pesapal
 */
function pesapal_get_token()
{
    global $config;

    $cacheKey = 'pesapal_token';
    $token = Cache::get($cacheKey);
    if ($token) {
        return $token;
    }

    $url = pesapal_get_server() . 'api/Auth/RequestToken';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Basic ' . base64_encode($config['pesapal_consumer_key'] . ':' . $config['pesapal_consumer_secret']),
    ];

    $result = Http::postJsonData($url, [], $headers);
    $response = json_decode($result, true);

    if (empty($response['token'])) {
        Message::sendTelegram("Pesapal: Failed to get access token\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        throw new \Exception('Failed to authenticate with Pesapal');
    }

    $expiry = !empty($response['expiryDate']) 
        ? strtotime($response['expiryDate']) - time() - 60 
        : 300;

    Cache::set($cacheKey, $response['token'], max(60, $expiry));

    return $response['token'];
}

/**
 * Register IPN URL with Pesapal
 */
function pesapal_register_ipn()
{
    global $config;

    try {
        $token = pesapal_get_token();
        $ipnUrl = rtrim($config['base_url'], '/') . '/callback/pesapal_ipn';

        $url = pesapal_get_server() . 'api/URLSetup/RegisterIPN';
        $headers = [
            'Content-Type: application/json',
            'Accept: application/json',
            'Authorization: Bearer ' . $token,
        ];
        $body = [
            'url'             => $ipnUrl,
            'ipn_notification_type' => 'POST',
        ];

        $result = Http::postJsonData($url, $body, $headers);
        $response = json_decode($result, true);

        if (!empty($response['ipn_id'])) {
            $d = ORM::for_table('tbl_appconfig')->where('setting', 'pesapal_ipn_id')->find_one();
            if ($d) {
                $d->value = $response['ipn_id'];
                $d->save();
            } else {
                $d = ORM::for_table('tbl_appconfig')->create();
                $d->setting = 'pesapal_ipn_id';
                $d->value = $response['ipn_id'];
                $d->save();
            }
            Message::sendTelegram("Pesapal IPN registered successfully. IPN ID: " . $response['ipn_id']);
        } else {
            Message::sendTelegram("Pesapal IPN registration response:\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        }
    } catch (\Exception $e) {
        Message::sendTelegram("Pesapal IPN registration failed: " . $e->getMessage());
    }
}

/**
 * Create a transaction on Pesapal and redirect user to payment page
 */
function pesapal_create_transaction($trx, $user)
{
    global $config;

    try {
        $token = pesapal_get_token();
    } catch (\Exception $e) {
        Message::sendTelegram("Pesapal auth failed: " . $e->getMessage());
        r2(U . 'order/package', 'e', Lang::T("Payment service temporarily unavailable. Please try again."));
    }

    $merchantRef = 'BTI-' . $trx['id'] . '-' . time();
    $callbackUrl = rtrim($config['base_url'], '/') . '/callback/pesapal';
    $cancelUrl   = rtrim($config['base_url'], '/') . '/order/package';
    $ipnId       = $config['pesapal_ipn_id'] ?? '';
    $currency    = $config['pesapal_currency'] ?? 'UGX';
    $countryCode = $config['pesapal_country_code'] ?? 'UG';
    $brandName   = $config['pesapal_brand_name'] ?? 'Buku Tu Internet';

    $orderData = [
        'id'               => $merchantRef,
        'currency'         => $currency,
        'amount'           => (float) $trx['price'],
        'description'      => $trx['plan_name'],
        'callback_url'     => $callbackUrl,
        'cancellation_url' => $cancelUrl,
        'redirect_mode'    => 'TOP_WINDOW',
        'notification_id'  => $ipnId,
        'branch'           => $brandName,
        'billing_address'  => [
            'email_address'  => !empty($user['email']) ? $user['email'] : $user['username'] . '@' . $_SERVER['HTTP_HOST'],
            'phone_number'   => !empty($user['phonenumber']) ? $user['phonenumber'] : '',
            'country_code'   => $countryCode,
            'first_name'     => $user['fullname'] ?: $user['username'],
            'last_name'      => '',
            'line_1'         => '',
            'city'           => '',
            'state'          => '',
            'postal_code'    => '',
            'zip_code'       => '',
        ],
    ];

    $url = pesapal_get_server() . 'api/Transactions/SubmitOrderRequest';
    $headers = [
        'Content-Type: application/json',
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ];

    $result = Http::postJsonData($url, $orderData, $headers);
    $response = json_decode($result, true);

    if (empty($response['redirect_url'])) {
        Message::sendTelegram("Pesapal order submission failed\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        r2(U . 'order/package', 'e', Lang::T("Failed to create transaction. Please try again."));
    }

    $d = ORM::for_table('tbl_payment_gateway')
        ->where('username', $user['username'])
        ->where('status', 1)
        ->find_one();
    $d->gateway_trx_id   = $response['order_tracking_id'] ?? '';
    $d->pg_url_payment   = $response['redirect_url'];
    $d->pg_request       = json_encode($response);
    $d->merchant_ref     = $merchantRef;
    $d->expired_date     = date('Y-m-d H:i:s', strtotime("+ 6 HOUR"));
    $d->save();

    header('Location: ' . $response['redirect_url']);
    exit();
}

/**
 * Handle redirect back from Pesapal after payment
 */
function pesapal_payment_notification()
{
    global $config;

    $orderTrackingId = $_GET['OrderTrackingId'] ?? $_GET['orderTrackingId'] ?? null;
    $merchantRef     = $_GET['OrderMerchantReference'] ?? $_GET['OrderMerchantReference'] ?? null;

    if (empty($orderTrackingId) && empty($merchantRef)) {
        Message::sendTelegram("Pesapal callback received without tracking ID or merchant reference\n\nGET: " . json_encode($_GET));
        r2(U . 'order/package', 'e', Lang::T("Invalid payment response."));
    }

    // Find the transaction by tracking ID or merchant reference
    if ($orderTrackingId) {
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('gateway_trx_id', $orderTrackingId)
            ->where('status', 1)
            ->find_one();
    }

    if (empty($d) && $merchantRef) {
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('merchant_ref', $merchantRef)
            ->where('status', 1)
            ->find_one();
    }

    if (empty($d)) {
        Message::sendTelegram("Pesapal callback: transaction not found\n\nTrackingID: $orderTrackingId\nMerchantRef: $merchantRef");
        r2(U . 'order/package', 'e', Lang::T("Transaction not found."));
    }

    // Verify payment status with Pesapal
    try {
        $token = pesapal_get_token();
    } catch (\Exception $e) {
        r2(U . 'order/view/' . $d['id'], 'w', Lang::T("Verifying payment... Please wait."));
    }

    $trackingId = $d['gateway_trx_id'] ?: $orderTrackingId;
    $url = pesapal_get_server() . 'api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($trackingId);
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ];

    $result = Http::getData($url, $headers);
    $response = json_decode($result, true);

    if (empty($response['payment_status_description'])) {
        Message::sendTelegram("Pesapal status check failed\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        r2(U . 'order/view/' . $d['id'], 'w', Lang::T("Still verifying payment... Please wait or check later."));
    }

    $status = strtoupper($response['payment_status_description']);

    if ($status === 'COMPLETED') {
        if (!Package::rechargeUser($d['user_id'], $d['routers'], $d['plan_id'], $d['gateway'], 'Pesapal')) {
            r2(U . 'order/view/' . $d['id'], 'd', Lang::T("Failed to activate your Package, please try again later."));
        }
        $d->pg_paid_response = json_encode($response);
        $d->payment_method = 'Pesapal';
        $d->payment_channel = $response['payment_method'] ?? 'Unknown';
        $d->paid_date = $response['payment_date'] ?? date('Y-m-d H:i:s');
        $d->status = 2;
        $d->save();

        Message::sendTelegram("Pesapal payment completed\n\nTrackingID: $trackingId\nAmount: " . $d['price'] . "\nUser: " . $d['username']);
        r2(U . 'order/view/' . $d['id'], 's', Lang::T("Payment successful! Your internet has been activated."));
    } elseif ($status === 'FAILED' || $status === 'CANCELLED') {
        $d->pg_paid_response = json_encode($response);
        $d->status = 3;
        $d->save();
        r2(U . 'order/package', 'e', Lang::T("Payment was not completed. Please try again."));
    } else {
        r2(U . 'order/view/' . $d['id'], 'w', Lang::T("Payment status: " . $status . ". Please wait or contact support."));
    }
}

/**
 * Check transaction status from Pesapal (for manual/scheduled verification)
 */
function pesapal_get_status($trx, $user)
{
    global $config;

    try {
        $token = pesapal_get_token();
    } catch (\Exception $e) {
        r2(U . 'order/view/' . $trx['id'], 'w', Lang::T("Verification service unavailable. Please try later."));
    }

    $trackingId = $trx['gateway_trx_id'];
    if (empty($trackingId)) {
        r2(U . 'order/view/' . $trx['id'], 'w', Lang::T("Transaction still being processed."));
    }

    $url = pesapal_get_server() . 'api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($trackingId);
    $headers = [
        'Accept: application/json',
        'Authorization: Bearer ' . $token,
    ];

    $result = Http::getData($url, $headers);
    $response = json_decode($result, true);

    if (empty($response['payment_status_description'])) {
        Message::sendTelegram("pesapal_get_status: unknown response\n\n" . json_encode($response, JSON_PRETTY_PRINT));
        r2(U . 'order/view/' . $trx['id'], 'w', Lang::T("Transaction still unpaid."));
    }

    $status = strtoupper($response['payment_status_description']);

    if ($status === 'COMPLETED' && $trx['status'] != 2) {
        if (!Package::rechargeUser($user['id'], $trx['routers'], $trx['plan_id'], $trx['gateway'], 'Pesapal')) {
            r2(U . 'order/view/' . $trx['id'], 'd', Lang::T("Failed to activate your Package, please try again later."));
        }
        $trx->pg_paid_response = json_encode($response);
        $trx->payment_method = 'Pesapal';
        $trx->payment_channel = $response['payment_method'] ?? 'Unknown';
        $trx->paid_date = $response['payment_date'] ?? date('Y-m-d H:i:s');
        $trx->status = 2;
        $trx->save();

        r2(U . 'order/view/' . $trx['id'], 's', Lang::T("Transaction successful."));
    } elseif ($status === 'FAILED' || $status === 'CANCELLED') {
        $trx->pg_paid_response = json_encode($response);
        $trx->status = 3;
        $trx->save();
        r2(U . 'order/view/' . $trx['id'], 'd', Lang::T("Transaction failed or cancelled."));
    } elseif ($status === 'EXPIRED') {
        $trx->pg_paid_response = json_encode($response);
        $trx->status = 3;
        $trx->save();
        r2(U . 'order/view/' . $trx['id'], 'd', Lang::T("Transaction expired."));
    } elseif ($trx['status'] == 2) {
        r2(U . 'order/view/' . $trx['id'], 'd', Lang::T("Transaction has been paid."));
    } else {
        Message::sendTelegram("pesapal_get_status: pending\n\nTrackingID: $trackingId\nStatus: $status");
        r2(U . 'order/view/' . $trx['id'], 'w', Lang::T("Payment pending. Status: " . $status));
    }
}

/**
 * Handle Pesapal IPN (server-to-server notification)
 * Called from: /callback/pesapal_ipn
 */
function pesapal_ipn_handler()
{
    global $config;

    $payload = file_get_contents('php://input');
    $data = json_decode($payload, true);

    // Log the IPN payload
    $log = ORM::for_table('tbl_pesapal_ipn_log')->create();
    $log->payload = $payload;
    $log->ipn_type = $data['ipn_notification_type'] ?? 'unknown';
    $log->order_tracking_id = $data['order_tracking_id'] ?? '';
    $log->order_merchant_reference = $data['order_merchant_reference'] ?? '';
    $log->status = $data['payment_status_description'] ?? '';
    $log->created_at = date('Y-m-d H:i:s');
    $log->save();

    $trackingId = $data['order_tracking_id'] ?? '';
    $merchantRef = $data['order_merchant_reference'] ?? '';
    $status = strtoupper($data['payment_status_description'] ?? '');

    if (empty($trackingId) && empty($merchantRef)) {
        http_response_code(400);
        echo json_encode(['error' => 'Missing tracking ID or merchant reference']);
        exit;
    }

    if ($status !== 'COMPLETED') {
        // Only activate on completed payments
        http_response_code(200);
        echo json_encode(['status' => 'received', 'message' => "Payment status: $status"]);
        exit;
    }

    // Find the transaction
    $d = null;
    if ($trackingId) {
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('gateway_trx_id', $trackingId)
            ->where('status', 1)
            ->find_one();
    }
    if (empty($d) && $merchantRef) {
        $d = ORM::for_table('tbl_payment_gateway')
            ->where('merchant_ref', $merchantRef)
            ->where('status', 1)
            ->find_one();
    }

    if (empty($d)) {
        http_response_code(200);
        echo json_encode(['status' => 'received', 'message' => 'Transaction not found or already processed']);
        exit;
    }

    // Activate user's package
    $user = ORM::for_table('tbl_user_recharges')->where('id', $d['user_id'])->find_one();
    if ($user) {
        if (Package::rechargeUser($d['user_id'], $d['routers'], $d['plan_id'], $d['gateway'], 'Pesapal')) {
            $d->pg_paid_response = json_encode($data);
            $d->payment_method = 'Pesapal';
            $d->payment_channel = $data['payment_method'] ?? 'Unknown';
            $d->paid_date = date('Y-m-d H:i:s');
            $d->status = 2;
            $d->save();

            Message::sendTelegram("Pesapal IPN: Payment activated\n\nTrackingID: $trackingId\nUser: " . $d['username']);
        }
    }

    http_response_code(200);
    echo json_encode(['status' => 'processed']);
    exit;
}

/**
 * Get Pesapal API base URL based on environment
 */
function pesapal_get_server()
{
    global $config;
    $env = $config['pesapal_environment'] ?? 'sandbox';
    if ($env === 'live') {
        return 'https://pay.pesapal.com/v3/';
    }
    // Sandbox (demo) environment
    return 'https://cybqa.pesapal.com/pesapalv3/';
}

/**
 * Display Pesapal payment button on order page
 */
function pesapal_show_button()
{
    return '<button type="submit" class="btn btn-success btn-block">
        <img src="system/paymentgateway/ui/pesapal_logo.png" style="height:20px;vertical-align:middle;">
        ' . Lang::T('Pay with Pesapal') . '
    </button>';
}

/**
 * Check if this gateway supports a specific currency
 */
function pesapal_supported_currency($currency)
{
    $currencies = json_decode(file_get_contents('system/paymentgateway/pesapal_currency.json'), true);
    return in_array($currency, array_keys($currencies));
}
