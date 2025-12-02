<?php
require_once __DIR__ . '/../vendor/Midtrans.php';

\Midtrans\Config::$serverKey = 'SB-Mid-server-BWpCtc-7XaahjqodV5XqRarm'; // Get from Midtrans dashboard
\Midtrans\Config::$clientKey = 'SB-Mid-client-Q2T8cXXCO3m0NBuW'; // Get from Midtrans dashboard
\Midtrans\Config::$isProduction = false; // false = sandbox
\Midtrans\Config::$isSanitized = true;
\Midtrans\Config::$is3ds = true;

// Disable SSL verification for development (sandbox mode)
\Midtrans\Config::$curlOptions = [
    CURLOPT_HTTPHEADER => [],
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_SSL_VERIFYHOST => false,
];
?>
