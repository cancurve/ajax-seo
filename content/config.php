<?php
//
// Global configuration
//

// Debug mode
if (in_array($_SERVER['REMOTE_ADDR'], array('127.0.0.1'))) {
    // Development
    error_reporting(E_ALL);
    $debug = true;
    $ver   = rand();
    $min   = null;
} else {
    // Production
    error_reporting(0);
    $debug = false;
    $ver   = 20131209;
    $min   = '.min';
}

// Use filename-based versioning to avoid CDN cache issue
$ver = '-' . $ver;

// Prevent XSS and SQL injection
$host = $_SERVER['SERVER_NAME'];
if ($host !== $_SERVER['HTTP_HOST']) {
    http_response_code(400);
    header('X-Robots-Tag: none'); // Robots meta tag and X-Robots-Tag HTTP header specifications https://developers.google.com/webmasters/control-crawl-index/docs/robots_meta_tag
    header('Content-Type: text/plain');
    exit('400 Bad Request');
}

// Compatibility
$comp = true;
$fix  = null;
// Check if Apache mod_rewrite enabled
if (function_exists('apache_get_modules') && !in_array('mod_rewrite', apache_get_modules())) {
    $comp = false;
    $fix .= "\n* enable Apache mod_rewrite";
}
$phpv = PHP_VERSION;
// Add latest PHP functions
if (version_compare($phpv, '5.4', '<')) include 'content/function.http-response-code.php';
// PHP 5.2 backward compatibility
if (version_compare($phpv, '5.2', '>=')) {
    // date.timezone settings required since PHP 5.3
    if (version_compare($phpv, '5.3', '>=') && !ini_get('date.timezone')) date_default_timezone_set('Etc/GMT');
    // Compress output with Gzip, PHP 5.4.4 bug https://bugs.php.net/bug.php?id=55544
    if (version_compare($phpv, '5.4.5', '>') && !ob_start('ob_gzhandler')) ob_start();
} else {
    $comp = false;
    $fix .= "\n* upgrade to PHP 5.2 or later";
}
if (!$comp) {
    http_response_code(503);
    header('Retry-After: 3600'); // 1 hour
    header('X-Robots-Tag: none');
    header('Content-Type: text/plain');
    exit('Your server is outdated' . $fix);
}

// Common variables
$scheme      = $_SERVER['SERVER_PORT'] == 443 ? 'https' : 'http';
$request_uri = rawurldecode($_SERVER['REQUEST_URI']);
$uri         = $scheme . '://' . $host . $request_uri;
$url         = isset($_GET['url']) ? $_GET['url'] : null;
// Absolute path
$path = str_replace('\\', '/', pathinfo($_SERVER['SCRIPT_NAME'], PATHINFO_DIRNAME));
$path = $path == '/' ? $path : $path . '/';