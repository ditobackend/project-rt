<?php
/**
 * Midtrans PHP SDK Loader (Fixed for PHP 8+)
 */

// Pastikan PHP versi minimal 5.4 (bukan 8.3)
if (version_compare(PHP_VERSION, '5.4.0', '<')) {
    throw new Exception('PHP version >= 5.4.0 is required for Midtrans SDK.');
}

// Pastikan cURL aktif
if (!function_exists('curl_init') || !function_exists('curl_exec')) {
    throw new Exception('Midtrans needs the CURL PHP extension.');
}

// Pastikan JSON aktif (built-in di PHP 8+)
if (!function_exists('json_decode')) {
    throw new Exception('Midtrans needs the JSON PHP extension.');
}

// === Load main files === //
require_once __DIR__ . '/Midtrans/Config.php';
require_once __DIR__ . '/Midtrans/Transaction.php';
require_once __DIR__ . '/Midtrans/ApiRequestor.php';
require_once __DIR__ . '/Midtrans/Notification.php';
require_once __DIR__ . '/Midtrans/CoreApi.php';
require_once __DIR__ . '/Midtrans/Snap.php';
require_once __DIR__ . '/Midtrans/Sanitizer.php';

// === Load Snap BI (optional) === //
require_once __DIR__ . '/SnapBi/SnapBi.php';
require_once __DIR__ . '/SnapBi/SnapBiApiRequestor.php';
require_once __DIR__ . '/SnapBi/SnapBiConfig.php';
