<?php

/**
 * Pesapal IPN (Instant Payment Notification) Endpoint
 * 
 * Place this file at: src/callback/pesapal_ipn.php
 * Or register the route in your web server config.
 * 
 * Pesapal sends POST requests to this URL after payment is processed.
 */

// Bootstrap PHPNuxBill
require_once '../init.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Handle IPN
pesapal_ipn_handler();
