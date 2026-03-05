<?php
require_once($_SERVER['DOCUMENT_ROOT'] . '/wp-load.php');

$url = 'https://maxusparts.co.uk/catalogue/spring-assembly-rear-leaf_1480/';

echo "Fetching: $url\n\n";

$response = wp_remote_get($url, [
    'timeout' => 30,
    'sslverify' => false,
    'user-agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'
]);

if (is_wp_error($response)) {
    echo "Error: " . $response->get_error_message() . "\n";
    exit;
}

$html = wp_remote_retrieve_body($response);
$code = wp_remote_retrieve_response_code($response);

echo "Response code: $code\n";
echo "HTML Length: " . strlen($html) . "\n\n";

// Look for title
if (preg_match('/<title>([^<]+)<\/title>/i', $html, $m)) {
    echo "Title: " . trim($m[1]) . "\n";
}

// Look for h1 and h2
if (preg_match('/<h1[^>]*>([^<]+)<\/h1>/is', $html, $m)) {
    echo "H1: " . trim($m[1]) . "\n";
}
if (preg_match('/<h2[^>]*>([^<]+)<\/h2>/is', $html, $m)) {
    echo "H2: " . trim($m[1]) . "\n";
}

// Look for price
if (preg_match_all('/£[\d,]+\.?\d*/i', $html, $m)) {
    echo "Prices: " . implode(', ', array_unique($m[0])) . "\n";
}

// Look for UPC
if (preg_match('/UPC[:\s]*([A-Z0-9]+)/i', $html, $m)) {
    echo "UPC: " . $m[1] . "\n";
}

// Look for stock
if (preg_match('/Stock[:\s]*(\d+)/i', $html, $m)) {
    echo "Stock: " . $m[1] . "\n";
}

echo "\n--- First 3000 chars of HTML ---\n";
echo substr($html, 0, 3000);
