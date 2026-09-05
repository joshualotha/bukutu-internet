<?php

/**
 * Pesapal Callback (Redirect) Endpoint
 * 
 * Place this file at: src/callback/pesapal.php
 * Pesapal redirects the customer back to this URL after payment.
 */

// Bootstrap PHPNuxBill
require_once '../init.php';

// Handle redirect
pesapal_payment_notification();
