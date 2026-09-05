<?php
/**
 * Buku Tu Internet - Pesapal Payment Callback
 * Verifies payment, activates user on MikroTik via tunnel
 */
define('APP_URL', 'https://test.africanpishonsafaris.co.tz');

// Direct DB connection for payment verification
$mysqli = new mysqli('localhost', 'afrihwam_africanpishonsafaris', 'a#n#jmFnbiaQ', 'afrihwam_test');
if ($mysqli->connect_error) {
    header('Location: ' . APP_URL . '/buy.php?error=db');
    exit;
}

// Get Pesapal config
$cfg = [];
$res = $mysqli->query("SELECT setting, value FROM tbl_appconfig WHERE setting LIKE 'pesapal%' OR setting = 'base_url'");
while ($row = $res->fetch_assoc()) $cfg[$row['setting']] = $row['value'];

$env = $cfg['pesapal_environment'] ?? 'sandbox';
$is_live = in_array(strtolower($env), ['live', 'production']);
$server = $is_live ? 'https://pay.pesapal.com/v3/' : 'https://cybqa.pesapal.com/pesapalv3/';
$key = $cfg['pesapal_consumer_key'] ?? '';
$secret = $cfg['pesapal_consumer_secret'] ?? '';
$base_url = $cfg['base_url'] ?? APP_URL;

// Get parameters from Pesapal redirect
$tracking_id = $_GET['OrderTrackingId'] ?? $_GET['orderTrackingId'] ?? null;
$merchant_ref = $_GET['OrderMerchantReference'] ?? $_GET['OrderMerchantReference'] ?? null;

if (empty($tracking_id) && empty($merchant_ref)) {
    header('Location: ' . $base_url . '/buy.php');
    exit;
}

/**
 * Get OAuth token from Pesapal
 */
function pesa_token($server, $key, $secret) {
    $ch = curl_init($server . 'api/Auth/RequestToken');
    curl_setopt_array($ch, [
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode(['consumer_key' => $key, 'consumer_secret' => $secret]),
        CURLOPT_HTTPHEADER => ['Content-Type: application/json', 'Accept: application/json'],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
    ]);
    $r = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $r['token'] ?? null;
}

/**
 * Verify payment status with Pesapal
 */
function pesa_status($server, $token, $tracking_id) {
    $ch = curl_init($server . 'api/Transactions/GetTransactionStatus?orderTrackingId=' . urlencode($tracking_id));
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ['Accept: application/json', 'Authorization: Bearer ' . $token],
        CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 15,
    ]);
    $r = json_decode(curl_exec($ch), true);
    curl_close($ch);
    return $r;
}

// Main: verify payment
try {
    $token = pesa_token($server, $key, $secret);
    if (!$token) throw new Exception('Auth failed');

    // Find payment record
    if ($tracking_id) {
        $d = $mysqli->query("SELECT * FROM tbl_payment_gateway WHERE gateway_trx_id = '$tracking_id' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    } else {
        $d = $mysqli->query("SELECT * FROM tbl_payment_gateway WHERE pg_request LIKE '%$merchant_ref%' ORDER BY id DESC LIMIT 1")->fetch_assoc();
    }

    if (!$d) throw new Exception('Payment not found');

    // Already processed?
    if ($d['status'] == 2) {
        header('Location: ' . $base_url . '/buy.php?paid=1');
        exit;
    }

    // Check payment status
    $status = pesa_status($server, $token, $tracking_id ?: $d['gateway_trx_id']);
    $payment_status = $status['payment_status_description'] ?? $status['status'] ?? '';

    if (strtoupper($payment_status) === 'COMPLETED') {
        // Mark payment as paid
        $mysqli->query("UPDATE tbl_payment_gateway SET status = 2, paid_date = NOW(), pg_paid_response = '" . $mysqli->real_escape_string(json_encode($status)) . "' WHERE id = {$d['id']}");

        // Get plan details
        $plan = $mysqli->query("SELECT p.*, b.rate_down, b.rate_down_unit, b.rate_up, b.rate_up_unit FROM tbl_plans p LEFT JOIN tbl_bandwidth b ON p.id_bw = b.id WHERE p.id = {$d['plan_id']}")->fetch_assoc();
        $customer = $mysqli->query("SELECT * FROM tbl_customers WHERE username = '{$d['username']}'")->fetch_assoc();

        if ($plan && $customer) {
            // Calculate expiration
            $validity = (int)$plan['validity'];
            $unit = $plan['validity_unit'];
            $hours = 0;
            switch ($unit) {
                case 'Mins': $hours = $validity / 60; break;
                case 'Hrs': $hours = $validity; break;
                case 'Days': $hours = $validity * 24; break;
                case 'Months': $hours = $validity * 720; break;
                default: $hours = 1;
            }
            $seconds = (int)($hours * 3600);
            $expiration = date('Y-m-d', strtotime("+{$hours} hours"));
            $exp_time = date('H:i:s', strtotime("+{$hours} hours"));
            $uptime = gmdate("H:i:s", $seconds);

            // Create recharge record
            $mysqli->query("INSERT INTO tbl_user_recharges (customer_id, username, plan_id, namebp, recharged_on, recharged_time, expiration, time, status, method, routers, type) VALUES ({$customer['id']}, '{$d['username']}', {$d['plan_id']}, '{$d['plan_name']}', CURDATE(), CURTIME(), '$expiration', '$exp_time', 'on', 'Pesapal', '{$d['routers']}', 'Hotspot')");

            // Build rate limit
            $rate = '';
            if ($plan['rate_up']) $rate = $plan['rate_up'] . $plan['rate_up_unit'] . '/' . $plan['rate_down'] . $plan['rate_down_unit'];

            // Connect to MikroTik via tunnel
            $router = $mysqli->query("SELECT * FROM tbl_routers WHERE enabled = 1 LIMIT 1")->fetch_assoc();
            $r_ip = '127.0.0.1';
            $r_port = 18728;
            $r_user = $router['username'];
            $r_pass = $router['password'];

            // Connect to MikroTik via tunnel and create hotspot user
            $r_ip = '127.0.0.1';
            $r_port = 18728;
            $r_user = $router['username'];
            $r_pass = $router['password'];

            // Remove existing user if any
            $fp = @fsockopen($r_ip, $r_port, $errno, $errstr, 10);
            if ($fp) {
                // Login
                $login = "/login\n=name=" . $r_user . "\n=password=" . $r_pass;
                foreach (explode("\n", $login) as $w) fwrite($fp, pack("N", strlen($w)) . $w);
                fwrite($fp, pack("N", 0));
                stream_set_timeout($fp, 5);
                
                // Read login response
                $len_data = fread($fp, 4);
                if (strlen($len_data) >= 4) {
                    $len = unpack("N", $len_data)[1];
                    $resp = fread($fp, $len);
                    
                    // Handle challenge-response if old API
                    if (strpos($resp, '=ret=') !== false && strpos($resp, '!done') === false) {
                        preg_match('/=ret=([0-9a-f]+)/', $resp, $m);
                        $hash = md5(chr(0) . $r_pass . pack("H*", $m[1]));
                        $login2 = "/login\n=name=" . $r_user . "\n=response=00" . $hash;
                        foreach (explode("\n", $login2) as $w) fwrite($fp, pack("N", strlen($w)) . $w);
                        fwrite($fp, pack("N", 0));
                        fread($fp, 4); fread($fp, unpack("N", fread($fp, 4))[1] ?? 0);
                    }
                }

                // Try to remove existing user
                $find = "/ip/hotspot/user/print\n?name=" . $d['username'];
                foreach (explode("\n", $find) as $w) fwrite($fp, pack("N", strlen($w)) . $w);
                fwrite($fp, pack("N", 0));
                
                $len_data = fread($fp, 4);
                if (strlen($len_data) >= 4) {
                    $len = unpack("N", $len_data)[1];
                    $rdata = fread($fp, $len);
                    if (strpos($rdata, '=.id=') !== false) {
                        preg_match('/=.id=([^\n]+)/', $rdata, $m2);
                        if (!empty($m2[1])) {
                            $remove = "/ip/hotspot/user/remove\n=numbers=" . $m2[1];
                            foreach (explode("\n", $remove) as $w) fwrite($fp, pack("N", strlen($w)) . $w);
                            fwrite($fp, pack("N", 0));
                            fread($fp, 4);
                            if (strlen(fread($fp, 4) ?? '') >= 4) fread($fp, unpack("N", fread($fp, 4))[1] ?? 0);
                        }
                    }
                }
                // Consume remaining response
                while (true) {
                    $len_data = fread($fp, 4);
                    if (strlen($len_data) < 4) break;
                    $l = unpack("N", $len_data)[1];
                    $w = fread($fp, $l);
                    if ($w === '!done' || $w === '!trap') break;
                }

                // Add new user
                $add = "/ip/hotspot/user/add\n=name=" . $d['username'] . "\n=password=" . $d['username'] . "\n=server=hotspot1";
                if ($seconds > 0) $add .= "\n=limit-uptime=" . $uptime;
                if ($rate) $add .= "\n=rate-limit=" . $rate;
                $add .= "\n=comment=Pesapal-" . $plan['name_plan'];
                
                foreach (explode("\n", $add) as $w) fwrite($fp, pack("N", strlen($w)) . $w);
                fwrite($fp, pack("N", 0));
                
                // Read add response
                $len_data = fread($fp, 4);
                if (strlen($len_data) >= 4) {
                    $l = unpack("N", $len_data)[1];
                    $w = fread($fp, $l);
                    $mt_ok = (strpos($w, '!done') !== false);
                }
                
                fclose($fp);
            }
        }

        // Show success page
        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Payment Successful - Buku Tu Internet</title>';
        echo '<style>*{margin:0;padding:0;box-sizing:border-box}body{font-family:-apple-system,BlinkMacSystemFont,sans-serif;background:#f0f2f5;display:flex;align-items:center;justify-content:center;min-height:100vh}.card{background:#fff;border-radius:16px;padding:40px 30px;text-align:center;max-width:420px;width:90%;box-shadow:0 5px 25px rgba(0,0,0,.1)}.check{font-size:64px;margin-bottom:15px}h1{color:#1a73e8;font-size:22px;margin-bottom:8px}p{color:#666;font-size:15px;margin-bottom:20px}.info{background:#f8f9fa;border-radius:10px;padding:15px;margin:10px 0;text-align:left}.info .lbl{color:#999;font-size:12px}.info .val{color:#333;font-weight:600;font-size:16px;margin-top:2px}.btn{display:inline-block;background:#1a73e8;color:white;padding:14px 30px;border-radius:10px;text-decoration:none;font-weight:600;font-size:16px;margin-top:15px}</style></head><body><div class="card">';
        echo '<div class="check">✅</div><h1>Payment Successful!</h1><p>Your internet is now active</p>';
        echo '<div class="info"><div class="lbl">Package</div><div class="val">' . htmlspecialchars($d['plan_name']) . '</div></div>';
        echo '<div class="info"><div class="lbl">Amount Paid</div><div class="val">' . number_format($d['price']) . ' TZS</div></div>';
        echo '<div class="info"><div class="lbl">Expires</div><div class="val">' . $expiration . ' at ' . $exp_time . '</div></div>';
        echo '<a href="http://10.5.50.1/status" class="btn">Start Browsing →</a>';
        echo '<p style="font-size:12px;color:#aaa;margin-top:15px">Reconnect to WiFi for seamless access</p>';
        echo '</div></body></html>';
        exit;
    } else {
        // Payment not completed
        header('Location: ' . $base_url . '/buy.php?status=' . urlencode($payment_status));
        exit;
    }
} catch (Exception $e) {
    error_log('Pesapal callback error: ' . $e->getMessage());
    header('Location: ' . $base_url . '/buy.php?error=callback');
    exit;
}
