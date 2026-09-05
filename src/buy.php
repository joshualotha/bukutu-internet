<?php
/**
 * Buku Tu Internet - Public Buy Page (No Registration Required)
 * 
 * This page shows all available internet packages.
 * Users select a package, enter phone number, and pay via Pesapal.
 * No account registration needed - account is created automatically.
 */

define("APP_URL", "https://test.africanpishonsafaris.co.tz");

$db_host = "localhost";
$db_user = "afrihwam_africanpishonsafaris";
$db_pass = "a#n#jmFnbiaQ";
$db_name = "afrihwam_test";

// DB Connection
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);
if ($mysqli->connect_error) die("Database connection error");

// Get active plans with bandwidth info
$plans = $mysqli->query("
    SELECT p.id, p.name_plan, p.price, p.validity, p.validity_unit,
           b.name_bw, CONCAT(b.rate_down, ' ', b.rate_down_unit) as speed
    FROM tbl_plans p
    LEFT JOIN tbl_bandwidth b ON p.id_bw = b.id
    WHERE p.enabled = 1 AND p.type = 'Hotspot' AND p.prepaid = 'yes'
    ORDER BY b.rate_down ASC, p.validity ASC
");

// Group by speed tier
$grouped = [];
while ($row = $plans->fetch_assoc()) {
    $speed = $row['name_bw'] ?: 'Standard';
    if (!isset($grouped[$speed])) $grouped[$speed] = [];
    $grouped[$speed][] = $row;
}

// Get company config
$configs = $mysqli->query("SELECT setting, value FROM tbl_appconfig WHERE setting IN ('CompanyName', 'currency_code')");
$cfg = [];
while ($c = $configs->fetch_assoc()) $cfg[$c['setting']] = $c['value'];

$company = $cfg['CompanyName'] ?? 'Buku Tu Internet';
$currency_code = $cfg['currency_code'] ?? 'TZS';

// Get MAC and IP from URL (passed by MikroTik hotspot redirect)
$mac = $_GET['nux-mac'] ?? $_GET['mac'] ?? '';
$ip = $_GET['nux-ip'] ?? $_GET['ip'] ?? '';
$router = $_GET['nux-router'] ?? '1';

// Sanitize MAC for use as username
$mac_username = 'device_' . str_replace(':', '', strtoupper($mac));

// Handle purchase
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['plan_id'])) {
    $plan_id = (int)$_POST['plan_id'];
    $phone = trim($_POST['phone'] ?? '');
    $device_mac = trim($_POST['device_mac'] ?? '');
    $manual_mac = trim($_POST['device_mac_manual'] ?? '');
    if ($manual_mac && !$device_mac) $device_mac = $manual_mac;

    if (empty($phone)) {
        $error = 'Please enter your phone number for payment';
    } elseif (strlen($phone) < 10) {
        $error = 'Please enter a valid phone number';
    } elseif (empty($device_mac) && empty($mac)) {
        $error = 'Please connect to the WiFi network first (WiFi SSID: MikroTik-A21AC2)';
    } else {
        // Use provided MAC or URL MAC
        $device_mac = $device_mac ?: $mac;
        // Use MAC address as username for device identification
        $username = 'device_' . str_replace(':', '', strtoupper($device_mac));
        
        // Find or create customer by MAC username
        $user = $mysqli->query("SELECT * FROM tbl_customers WHERE username = '$username'")->fetch_assoc();
        if (!$user) {
            $mysqli->query("INSERT INTO tbl_customers 
                (username, password, fullname, phonenumber, email, service_type, status, created_at) 
                VALUES 
                ('$username', '" . md5($device_mac) . "', 'Device " . substr($device_mac, -5) . "', '$phone', '$username@hotspot.local', 'Hotspot', 'Active', NOW())");
            $user_id = $mysqli->insert_id;
            $user = $mysqli->query("SELECT * FROM tbl_customers WHERE id = $user_id")->fetch_assoc();
        } else {
            $user_id = $user['id'];
            // Update phone number if changed
            $mysqli->query("UPDATE tbl_customers SET phonenumber = '$phone' WHERE id = $user_id");
        }

        // Get plan and router info
        $plan = $mysqli->query("SELECT * FROM tbl_plans WHERE id = $plan_id")->fetch_assoc();
        $router_result = $mysqli->query("SELECT id, name FROM tbl_routers WHERE enabled = 1 LIMIT 1");
        $router_info = $router_result->fetch_assoc();

        if (!$plan) {
            $error = 'Plan not found';
        } elseif (!$router_info) {
            $error = 'No router configured';
        } else {
            // Create payment gateway record
            $invoice = 'INV-' . strtoupper(substr(md5(time() . $username), 0, 8));
            $mysqli->query("INSERT INTO tbl_payment_gateway 
                (username, user_id, gateway, plan_id, plan_name, routers_id, routers, price, trx_invoice, status, created_date, expired_date) 
                VALUES 
                ('$username', $user_id, 'pesapal', $plan_id, '{$plan['name_plan']}', 
                 {$router_info['id']}, '{$router_info['name']}', '{$plan['price']}', 
                 '$invoice', 1, NOW(), DATE_ADD(NOW(), INTERVAL 1 HOUR))");

            $pg_id = $mysqli->insert_id;

            // Build transaction array for Pesapal plugin
            $trx = $mysqli->query("SELECT * FROM tbl_payment_gateway WHERE id = $pg_id")->fetch_assoc();

            // Get Pesapal config
            $pesapal_config = $mysqli->query("SELECT setting, value FROM tbl_appconfig WHERE setting LIKE 'pesapal%'");
            $pcfg = [];
            while ($row = $pesapal_config->fetch_assoc()) $pcfg[$row['setting']] = $row['value'];

            $env = $pcfg['pesapal_environment'] ?? 'sandbox';
            $is_live = ($env === 'live' || $env === 'production' || $env === 'Live' || $env === 'Production');
            $server = $is_live ? 'https://pay.pesapal.com/v3/' : 'https://cybqa.pesapal.com/pesapalv3/';
            $key = $pcfg['pesapal_consumer_key'] ?? '';
            $secret = $pcfg['pesapal_consumer_secret'] ?? '';
            $ipn = $pcfg['pesapal_ipn_id'] ?? '';
            $currency = $pcfg['pesapal_currency'] ?? 'TZS';
            $country = $pcfg['pesapal_country_code'] ?? 'TZ';
            $brand = $pcfg['pesapal_brand_name'] ?? 'Buku Tu Internet';

            // Step 1: Get OAuth token
            $ch = curl_init($server . 'api/Auth/RequestToken');
            curl_setopt_array($ch, [
                CURLOPT_POST => true,
                CURLOPT_POSTFIELDS => json_encode(['consumer_key' => $key, 'consumer_secret' => $secret]),
                CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 15,
            ]);
            $auth_response = json_decode(curl_exec($ch), true);
            $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($http_code !== 200 || empty($auth_response['token'])) {
                $error = 'Payment service unavailable. Please try again. (Auth error)';
            } else {
                $token = $auth_response['token'];

                // Step 2: Submit order
                $order_data = [
                    'id' => 'BTI-' . $pg_id . '-' . time(),
                    'currency' => $currency,
                    'amount' => (float) $trx['price'],
                    'description' => $trx['plan_name'],
                    'callback_url' => APP_URL . '/callback/pesapal',
                    'cancellation_url' => APP_URL . '/buy.php',
                    'redirect_mode' => 'TOP_WINDOW',
                    'notification_id' => $ipn,
                    'branch' => $brand,
                    'billing_address' => [
                        'email_address' => $username . '@hotspot.local',
                        'phone_number' => $phone,
                        'country_code' => $country,
                        'first_name' => $user['fullname'] ?? 'Customer',
                        'last_name' => '',
                        'line_1' => '',
                        'city' => '',
                        'state' => '',
                        'postal_code' => '',
                        'zip_code' => '',
                    ],
                ];

                $ch2 = curl_init($server . 'api/Transactions/SubmitOrderRequest');
                curl_setopt_array($ch2, [
                    CURLOPT_POST => true,
                    CURLOPT_POSTFIELDS => json_encode($order_data),
                    CURLOPT_HTTPHEADER => [
                        'Content-Type: application/json',
                        'Accept: application/json',
                        'Authorization: Bearer ' . $token,
                    ],
                    CURLOPT_RETURNTRANSFER => true,
                    CURLOPT_TIMEOUT => 15,
                ]);
                $order_response = json_decode(curl_exec($ch2), true);
                $order_http = curl_getinfo($ch2, CURLINFO_HTTP_CODE);
                curl_close($ch2);

                if (!empty($order_response['redirect_url'])) {
                    // Update payment record with tracking ID
                    $tracking_id = $order_response['order_tracking_id'] ?? '';
                    $redirect_url = $order_response['redirect_url'];
                    $mysqli->query("UPDATE tbl_payment_gateway SET 
                        gateway_trx_id = '$tracking_id',
                        pg_url_payment = '$redirect_url',
                        pg_request = '" . $mysqli->real_escape_string(json_encode($order_response)) . "'
                        WHERE id = $pg_id");

                    // Redirect to Pesapal payment page
                    header('Location: ' . $redirect_url);
                    exit;
                } else {
                    $error = 'Failed to create payment. Please try again.';
                    error_log('Pesapal order error: ' . json_encode($order_response));
                }
            }
        }
    }
}

// Format currency
function fmt($amount) {
    return number_format($amount);
}

// Handle AJAX request (returns only the package list HTML)
if (isset($_GET['ajax'])) {
    // Output only the plan cards
    foreach ($grouped as $speed => $plansList) {
        echo '<div class="speed-section"><div class="speed-title">⚡ ' . htmlspecialchars($speed) . '</div>';
        foreach ($plansList as $plan) {
            $pname = htmlspecialchars(addslashes($plan['name_plan']));
            echo '<div class="plan-card">';
            echo '<div class="plan-info"><h3>' . htmlspecialchars($plan['name_plan']) . '</h3>';
            echo '<span>' . $plan['validity'] . ' ' . $plan['validity_unit'] . '</span></div>';
            echo '<div class="plan-right"><div class="plan-price">' . fmt($plan['price']) . ' <span class="plan-unit">' . $currency_code . '</span></div>';
            echo '<button class="buy-btn" onclick="buyNow(' . $plan['id'] . ', \'' . $pname . '\', ' . $plan['price'] . ')">Buy Now</button>';
            echo '</div></div>';
        }
        echo '</div>';
    }
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($company) ?> - Internet Packages</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { 
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif; 
            background: #f0f2f5; 
            color: #333; 
            min-height: 100vh;
        }
        .header { 
            background: linear-gradient(135deg, #1a73e8, #0d47a1); 
            color: white; 
            padding: 30px 20px; 
            text-align: center;
            box-shadow: 0 2px 10px rgba(0,0,0,0.15);
        }
        .header h1 { font-size: 26px; margin-bottom: 5px; }
        .header p { opacity: 0.85; font-size: 14px; margin-top: 5px; }
        .container { max-width: 700px; margin: 20px auto; padding: 0 15px; }
        .speed-section { margin-bottom: 25px; }
        .speed-title { 
            background: white; 
            padding: 14px 20px; 
            border-radius: 12px 12px 0 0; 
            border-left: 4px solid #1a73e8; 
            font-weight: 700; 
            font-size: 17px; 
            box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        }
        .plan-card { 
            background: white; 
            padding: 16px 20px; 
            border-bottom: 1px solid #f0f0f0; 
            display: flex; 
            align-items: center; 
            justify-content: space-between; 
            flex-wrap: wrap;
            gap: 10px;
        }
        .plan-card:last-child { 
            border-radius: 0 0 12px 12px; 
            border-bottom: none; 
        }
        .plan-info h3 { font-size: 16px; margin-bottom: 3px; }
        .plan-info span { font-size: 13px; color: #888; }
        .plan-right { text-align: right; min-width: 130px; }
        .plan-price { font-size: 22px; font-weight: 700; color: #1a73e8; }
        .plan-unit { font-size: 12px; color: #999; }
        .buy-btn { 
            background: #1a73e8; 
            color: white; 
            border: none; 
            padding: 10px 24px; 
            border-radius: 8px; 
            font-size: 14px; 
            font-weight: 600; 
            cursor: pointer; 
            transition: background 0.2s;
            margin-top: 5px;
        }
        .buy-btn:hover { background: #1557b0; }
        .phone-input { 
            width: 100%; 
            padding: 14px 16px; 
            font-size: 16px; 
            border: 2px solid #ddd; 
            border-radius: 10px; 
            margin-bottom: 12px;
        }
        .phone-input:focus { border-color: #1a73e8; outline: none; }

        /* Modal */
        .modal { 
            display: none; 
            position: fixed; 
            top: 0; left: 0; 
            width: 100%; height: 100%; 
            background: rgba(0,0,0,0.5); 
            z-index: 1000; 
            align-items: center; 
            justify-content: center; 
        }
        .modal.show { display: flex; }
        .modal-content { 
            background: white; 
            border-radius: 15px; 
            padding: 30px 25px; 
            max-width: 380px; 
            width: 90%; 
            text-align: center;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-content h2 { margin-bottom: 5px; font-size: 20px; }
        .modal-content .plan-name { color: #1a73e8; font-weight: 700; font-size: 18px; }
        .modal-content .plan-price-modal { font-size: 30px; font-weight: 700; margin: 10px 0 20px; color: #333; }
        .btn-group { display: flex; gap: 10px; margin-top: 15px; justify-content: center; }
        .close-btn { 
            background: #eee; 
            color: #555; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 8px; 
            font-size: 15px; 
            cursor: pointer; 
        }
        .confirm-btn { 
            background: #1a73e8; 
            color: white; 
            border: none; 
            padding: 12px 28px; 
            border-radius: 8px; 
            font-size: 15px; 
            font-weight: 700; 
            cursor: pointer; 
        }
        .confirm-btn:hover { background: #1557b0; }

        .footer { text-align: center; padding: 30px 20px; color: #aaa; font-size: 12px; }
        .error-box { 
            background: #fff0f0; 
            color: #c00; 
            padding: 14px; 
            border-radius: 10px; 
            margin-bottom: 15px; 
            text-align: center;
            border: 1px solid #fcc;
            font-size: 14px;
        }
        .help-text {
            font-size: 12px;
            color: #999;
            margin-top: 5px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1><?= htmlspecialchars($company) ?></h1>
        <p>Select a package and get connected instantly</p>
        <?php if ($mac): ?>
        <p style="font-size:12px;opacity:0.7;margin-top:8px">📱 Device: <strong><?= htmlspecialchars($mac) ?></strong></p>
        <?php else: ?>
        <p style="font-size:12px;background:#ff9800;color:white;padding:8px;border-radius:8px;margin-top:10px">⚠️ Please connect to WiFi <strong>MikroTik-A21AC2</strong> to buy internet</p>
        <?php endif; ?>
    </div>

    <div class="container">
        <?php if ($error): ?>
            <div class="error-box"><?= htmlspecialchars($error) ?></div>
        <?php endif; ?>

        <?php if (empty($grouped)): ?>
            <div class="error-box">No packages available yet. Please contact support.</div>
        <?php endif; ?>

        <?php foreach ($grouped as $speed => $plansList): ?>
        <div class="speed-section">
            <div class="speed-title">⚡ <?= htmlspecialchars($speed) ?></div>
            <?php foreach ($plansList as $plan): ?>
            <div class="plan-card">
                <div class="plan-info">
                    <h3><?= htmlspecialchars($plan['name_plan']) ?></h3>
                    <span><?= $plan['validity'] ?> <?= $plan['validity_unit'] ?></span>
                </div>
                <div class="plan-right">
                    <div class="plan-price"><?= fmt($plan['price']) ?> <span class="plan-unit"><?= $currency_code ?></span></div>
                    <button class="buy-btn" onclick="openModal(<?= $plan['id'] ?>, '<?= addslashes(htmlspecialchars($plan['name_plan'])) ?>', <?= $plan['price'] ?>)">
                        Buy Now
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endforeach; ?>

        <div class="footer">Powered by Buku Tu Internet</div>
    </div>

    <!-- Payment Modal -->
    <div class="modal" id="paymentModal">
        <div class="modal-content">
            <h2>Buy Package</h2>
            <div class="plan-name" id="modalPlanName"></div>
            <div class="plan-price-modal" id="modalPlanPrice"></div>
            <form method="POST">
                <input type="hidden" name="plan_id" id="modalPlanId">
                <input type="hidden" name="device_mac" id="modalDeviceMac" value="<?= htmlspecialchars($mac) ?>">
                <input type="text" name="phone" class="phone-input" placeholder="Phone number (e.g. 0712345678)" required autofocus>
                <?php if (!$mac): ?>
                <input type="text" name="device_mac_manual" class="phone-input" placeholder="Device MAC address (e.g. AA:BB:CC:DD:EE:FF)" style="font-size:12px">
                <?php endif; ?>
                <div class="help-text">You will receive payment confirmation on this number</div>
                <div class="btn-group">
                    <button type="button" class="close-btn" onclick="closeModal()">Cancel</button>
                    <button type="submit" class="confirm-btn">Pay with Pesapal →</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(planId, planName, price) {
            document.getElementById('modalPlanId').value = planId;
            document.getElementById('modalPlanName').textContent = planName;
            document.getElementById('modalPlanPrice').textContent = '<?= $currency_code ?> ' + price.toLocaleString();
            document.getElementById('paymentModal').classList.add('show');
            setTimeout(function() {
                document.querySelector('.phone-input').focus();
            }, 100);
        }
        function closeModal() {
            document.getElementById('paymentModal').classList.remove('show');
        }
        // Close modal on outside click
        document.getElementById('paymentModal').addEventListener('click', function(e) {
            if (e.target === this) closeModal();
        });
    </script>
</body>
</html>
