<?php
// Configuration Loader for Plain PHP 8.x Milan Dating

// Load .env file if present
$envFile = dirname(__DIR__) . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || str_starts_with($line, '#')) {
            continue;
        }
        $parts = explode('=', $line, 2);
        if (count($parts) === 2) {
            $key = trim($parts[0]);
            $val = trim($parts[1]);
            // Remove surrounding quotes if present
            if ((str_starts_with($val, '"') && str_ends_with($val, '"')) ||
                (str_starts_with($val, "'") && str_ends_with($val, "'"))) {
                $val = substr($val, 1, -1);
            }
            if (!array_key_exists($key, $_SERVER) && !array_key_exists($key, $_ENV)) {
                putenv("{$key}={$val}");
                $_ENV[$key] = $val;
                $_SERVER[$key] = $val;
            }
        }
    }
}

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed {
        $val = getenv($key);
        if ($val === false) {
            return $_ENV[$key] ?? $_SERVER[$key] ?? $default;
        }
        return match (strtolower((string)$val)) {
            'true', '(true)' => true,
            'false', '(false)' => false,
            'empty', '(empty)' => '',
            'null', '(null)' => null,
            default => $val,
        };
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed {
        static $config = null;
        if ($config === null) {
            $config = [
                'app_name' => env('APP_NAME', 'Milan Dating'),
                'app_env' => env('APP_ENV', 'production'),
                'app_debug' => env('APP_DEBUG', false),
                'app_url' => rtrim(env('APP_URL', 'http://localhost:3000'), '/'),
                'app' => [
                    'name' => env('APP_NAME', 'Milan Dating'),
                    'env' => env('APP_ENV', 'production'),
                    'debug' => env('APP_DEBUG', false),
                    'url' => rtrim(env('APP_URL', 'http://localhost:3000'), '/'),
                ],
                'db' => [
                    'connection' => env('DB_CONNECTION', 'mysql'),
                    'host' => env('DB_HOST', '127.0.0.1'),
                    'port' => env('DB_PORT', 3306),
                    'database' => env('DB_DATABASE', 'dating_db'),
                    'username' => env('DB_USERNAME', 'dating_user'),
                    'password' => env('DB_PASSWORD', 'dating_pass_2026'),
                ],
                'razorpay' => [
                    'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_milan_dating_key'),
                    'key_secret' => env('RAZORPAY_KEY_SECRET', 'milan_razorpay_secret_key'),
                    'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'milan_webhook_secret_key'),
                ],
                'session' => [
                    'secret' => env('SESSION_SECRET', 'milan_super_secret_session_2026'),
                    'lifetime' => 86400 * 7,
                ],
            ];
        }

        $parts = explode('.', $key);
        $current = $config;
        foreach ($parts as $part) {
            if (!is_array($current) || !array_key_exists($part, $current)) {
                return $default;
            }
            $current = $current[$part];
        }
        return $current;
    }
}

return [
    'app_name' => env('APP_NAME', 'Milan Dating'),
    'app_env' => env('APP_ENV', 'production'),
    'app_debug' => env('APP_DEBUG', false),
    'app_url' => rtrim(env('APP_URL', 'http://localhost:3000'), '/'),
    
    'db' => [
        'connection' => env('DB_CONNECTION', 'mysql'),
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', 3306),
        'database' => env('DB_DATABASE', 'dating_db'),
        'username' => env('DB_USERNAME', 'dating_user'),
        'password' => env('DB_PASSWORD', 'dating_pass_2026'),
    ],

    'razorpay' => [
        'key_id' => env('RAZORPAY_KEY_ID', 'rzp_test_milan_dating_key'),
        'key_secret' => env('RAZORPAY_KEY_SECRET', 'milan_razorpay_secret_key'),
        'webhook_secret' => env('RAZORPAY_WEBHOOK_SECRET', 'milan_webhook_secret_key'),
    ],

    'session' => [
        'secret' => env('SESSION_SECRET', 'milan_super_secret_session_2026'),
        'lifetime' => 86400 * 7, // 7 days
    ],
];
