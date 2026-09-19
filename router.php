<?php
// Simple router for PHP built-in web server
$uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

// If static file in public/ exists and is not a php file, serve it directly
$publicPath = __DIR__ . '/public' . $uri;
if ($uri !== '/' && file_exists($publicPath) && !is_dir($publicPath)) {
    $ext = pathinfo($publicPath, PATHINFO_EXTENSION);
    if ($ext !== 'php') {
        return false; // Let PHP built-in server handle static asset
    }
}

// Otherwise pass to public/index.php
require __DIR__ . '/public/index.php';
