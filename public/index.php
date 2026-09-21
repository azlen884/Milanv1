<?php
/**
 * Milan Dating - Application Entry Point
 * Plain PHP 8.x + MySQL
 */

// 1. PSR-4 Style Autoloader for App\ Namespace
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relativeClass = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});

// 2. Load Configuration and Environment
require_once dirname(__DIR__) . '/config/app.php';

// Configure Error Reporting based on Environment
if (config('app.debug', false)) {
    ini_set('display_errors', '1');
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 2.1 Safe Startup / Database Diagnostic
try {
    \App\Helpers\Database::checkDriverAvailability();
} catch (\Throwable $e) {
    error_log('[Server Configuration Error] ' . $e->getMessage());
    if (php_sapi_name() === 'cli') {
        fwrite(STDERR, "[FATAL] " . $e->getMessage() . "\n");
        exit(1);
    }
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Server Configuration Error | Milan Dating</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-lg w-full bg-white rounded-3xl p-8 border border-slate-200 text-center shadow-sm">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 font-bold text-xl">!</div>
            <h1 class="text-xl font-bold text-slate-900 mb-2">PHP PDO Extension Required</h1>
            <p class="text-xs text-slate-600 leading-relaxed mb-4">The application requires the native PHP PDO extension and the MySQL PDO driver (pdo_mysql), but they are not loaded in the active PHP runtime.</p>
            <div class="bg-slate-50 rounded-2xl p-4 text-left border border-slate-100 text-xs text-slate-700 font-mono space-y-1">
                <p class="font-semibold text-slate-800">Server Administrator Action Required:</p>
                <p>1. In cPanel / CloudLinux PHP Selector, verify <span class="text-rose-600 font-bold">pdo</span> and <span class="text-rose-600 font-bold">pdo_mysql</span> are checked for the active PHP version.</p>
                <p>2. In your loaded php.ini (e.g. /opt/alt/php81/etc/php.ini), ensure extension=pdo.so and extension=pdo_mysql.so directives are enabled.</p>
                <p>3. If running LiteSpeed (lsphp), ensure the LiteSpeed external app configuration references the matching PHP binary and restart LiteSpeed / PHP service.</p>
            </div>
        </div>
    </body>
    </html>';
    exit(1);
}

// 3. Start Secure Session
\App\Helpers\Session::start();

// 4. Request Normalization
$rawUri = $_SERVER['REQUEST_URI'] ?? '/';
$parsedPath = parse_url($rawUri, PHP_URL_PATH) ?: '/';
$path = rtrim($parsedPath, '/');
if ($path === '') {
    $path = '/';
}
$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
if ($method === 'HEAD') {
    $method = 'GET';
}

// 5. Maintenance Mode Gate (Admins always exempted)
try {
    $maintRow = \App\Helpers\Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $isMaintenance = ($maintRow['setting_value'] ?? '0') === '1';
    if ($isMaintenance && strpos($path, '/admin') !== 0) {
        http_response_code(503);
        echo '<!DOCTYPE html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport" content="width=device-width, initial-scale=1.0">
            <title>Maintenance | Milan Dating</title>
            <script src="https://cdn.tailwindcss.com"></script>
        </head>
        <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
            <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-slate-200 text-center shadow-sm">
                <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 font-bold text-xl">M</div>
                <h1 class="text-xl font-bold text-slate-900 mb-2">Scheduled Platform Maintenance</h1>
                <p class="text-xs text-slate-500 leading-relaxed">Milan Dating is currently undergoing brief scheduled maintenance to enhance your experience. We will be back online shortly.</p>
                <div class="mt-6 pt-6 border-t border-slate-100 text-[11px] text-slate-400">
                    If you are an administrator, please <a href="/admin/login" class="text-rose-600 font-semibold underline">sign in here</a>.
                </div>
            </div>
        </body>
        </html>';
        exit;
    }
} catch (\Throwable $e) {
    error_log("Database maintenance check error: " . $e->getMessage());
    if (!headers_sent()) {
        http_response_code(500);
    }
    echo '<!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <title>Database Unavailable | Milan Dating</title>
        <script src="https://cdn.tailwindcss.com"></script>
    </head>
    <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
        <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-slate-200 text-center shadow-sm">
            <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 font-bold text-xl">!</div>
            <h1 class="text-xl font-bold text-slate-900 mb-2">Database Service Unavailable</h1>
            <p class="text-xs text-slate-500 leading-relaxed">Could not verify platform maintenance state. Please verify database connectivity and configuration.</p>
        </div>
    </body>
    </html>';
    exit(1);
}

// 6. Application Routes Definition
$routes = [
    'GET' => [
        '/' => [\App\Controllers\LandingController::class, 'index'],
        '/register' => [\App\Controllers\AuthController::class, 'showRegister'],
        '/login' => [\App\Controllers\AuthController::class, 'showLogin'],
        '/logout' => [\App\Controllers\AuthController::class, 'logout'],
        '/home' => [\App\Controllers\HomeController::class, 'index'],
        '/discover' => [\App\Controllers\DiscoverController::class, 'index'],
        '/profile' => [\App\Controllers\ProfileController::class, 'show'],
        '/matches' => [\App\Controllers\MatchesController::class, 'index'],
        '/messages' => [\App\Controllers\ChatController::class, 'index'],
        '/messages/start' => [\App\Controllers\ChatController::class, 'start'],
        '/messages/poll' => [\App\Controllers\ChatController::class, 'poll'],
        '/api/chat/poll' => [\App\Controllers\ChatController::class, 'poll'],
        '/visitors' => [\App\Controllers\VisitorsController::class, 'index'],
        '/upgrade' => [\App\Controllers\SubscriptionController::class, 'upgrade'],
        '/subscription' => [\App\Controllers\SubscriptionController::class, 'show'],
        '/settings' => [\App\Controllers\SettingsController::class, 'index'],
        '/api/call/check' => [\App\Controllers\CallController::class, 'check'],
        '/api/heartbeat' => [\App\Controllers\CallController::class, 'heartbeat'],
        '/cron' => [\App\Controllers\CronController::class, 'run'],
        '/api/cron/bots' => [\App\Controllers\CronController::class, 'runBots'],

        // Admin Routes
        '/admin' => [\App\Controllers\AdminController::class, 'dashboard'],
        '/admin/dashboard' => [\App\Controllers\AdminController::class, 'dashboard'],
        '/admin/analytics' => [\App\Controllers\AdminController::class, 'analytics'],
        '/admin/login' => [\App\Controllers\AdminController::class, 'showLogin'],
        '/admin/logout' => [\App\Controllers\AdminController::class, 'logout'],
        '/admin/users' => [\App\Controllers\AdminController::class, 'users'],
        '/admin/subscriptions' => [\App\Controllers\AdminController::class, 'subscriptions'],
        '/admin/payments' => [\App\Controllers\AdminController::class, 'payments'],
        '/admin/razorpay' => [\App\Controllers\AdminController::class, 'razorpay'],
        '/admin/boost' => [\App\Controllers\AdminController::class, 'boost'],
        '/admin/bots' => [\App\Controllers\AdminController::class, 'bots'],
        '/admin/kyc' => [\App\Controllers\AdminController::class, 'kyc'],
        '/admin/kyc/view' => [\App\Controllers\AdminController::class, 'viewKycDocument'],
        '/admin/reports' => [\App\Controllers\AdminController::class, 'reports'],
        '/admin/discovery' => [\App\Controllers\AdminController::class, 'discovery'],
        '/admin/landing-profiles' => [\App\Controllers\AdminController::class, 'landingProfiles'],
        '/admin/content' => [\App\Controllers\AdminController::class, 'content'],
        '/admin/settings' => [\App\Controllers\AdminController::class, 'settings'],
        '/admin/audit-logs' => [\App\Controllers\AdminController::class, 'auditLogs'],
        '/admin/system' => [\App\Controllers\AdminController::class, 'system'],
    ],
    'POST' => [
        '/register' => [\App\Controllers\AuthController::class, 'register'],
        '/login' => [\App\Controllers\AuthController::class, 'login'],
        '/logout' => [\App\Controllers\AuthController::class, 'logout'],
        '/profile/block' => [\App\Controllers\ProfileController::class, 'block'],
        '/api/profile/block' => [\App\Controllers\ProfileController::class, 'block'],
        '/profile/report' => [\App\Controllers\ProfileController::class, 'report'],
        '/api/profile/report' => [\App\Controllers\ProfileController::class, 'report'],
        '/interest/send' => [\App\Controllers\InterestController::class, 'send'],
        '/api/interest/send' => [\App\Controllers\InterestController::class, 'send'],
        '/messages/send' => [\App\Controllers\ChatController::class, 'sendText'],
        '/api/chat/send-text' => [\App\Controllers\ChatController::class, 'sendText'],
        '/api/chat/typing' => [\App\Controllers\ChatController::class, 'typing'],
        '/api/call/start' => [\App\Controllers\CallController::class, 'start'],
        '/api/call/respond' => [\App\Controllers\CallController::class, 'respond'],
        '/api/call/candidate' => [\App\Controllers\CallController::class, 'sendCandidate'],
        '/api/call/end' => [\App\Controllers\CallController::class, 'end'],
        '/payment/create-order' => [\App\Controllers\SubscriptionController::class, 'createOrder'],
        '/api/subscription/create-order' => [\App\Controllers\SubscriptionController::class, 'createOrder'],
        '/payment/verify' => [\App\Controllers\SubscriptionController::class, 'verifyPayment'],
        '/api/subscription/verify-payment' => [\App\Controllers\SubscriptionController::class, 'verifyPayment'],
        '/api/subscription/record-failure' => [\App\Controllers\SubscriptionController::class, 'recordFailure'],
        '/boost/activate' => [\App\Controllers\SubscriptionController::class, 'activateBoost'],
        '/api/subscription/activate-boost' => [\App\Controllers\SubscriptionController::class, 'activateBoost'],
        '/api/razorpay/webhook' => [\App\Controllers\SubscriptionController::class, 'webhook'],

        // Settings POST Handlers
        '/settings/profile' => [\App\Controllers\SettingsController::class, 'updateProfile'],
        '/settings/photo/upload' => [\App\Controllers\SettingsController::class, 'uploadPhoto'],
        '/api/photos/upload' => [\App\Controllers\SettingsController::class, 'uploadPhoto'],
        '/settings/photo/delete' => [\App\Controllers\SettingsController::class, 'deletePhoto'],
        '/api/photos/delete' => [\App\Controllers\SettingsController::class, 'deletePhoto'],
        '/settings/photo/primary' => [\App\Controllers\SettingsController::class, 'setPrimaryPhoto'],
        '/api/photos/set-primary' => [\App\Controllers\SettingsController::class, 'setPrimaryPhoto'],
        '/settings/interests' => [\App\Controllers\SettingsController::class, 'updateInterests'],
        '/settings/preferences' => [\App\Controllers\SettingsController::class, 'updatePreferences'],
        '/settings/privacy' => [\App\Controllers\SettingsController::class, 'updatePrivacy'],
        '/settings/kyc' => [\App\Controllers\SettingsController::class, 'submitKyc'],
        '/settings/unblock' => [\App\Controllers\SettingsController::class, 'unblock'],
        '/api/profile/unblock' => [\App\Controllers\SettingsController::class, 'unblock'],
        '/settings/password' => [\App\Controllers\SettingsController::class, 'updatePassword'],
        '/settings/account/delete' => [\App\Controllers\AuthController::class, 'deleteAccount'],

        // Admin POST Handlers
        '/admin/login' => [\App\Controllers\AdminController::class, 'login'],
        '/admin/logout' => [\App\Controllers\AdminController::class, 'logout'],
        '/admin/user/action' => [\App\Controllers\AdminController::class, 'userAction'],
        '/admin/user/toggle-ban' => [\App\Controllers\AdminController::class, 'toggleBan'],
        '/admin/user/verify-kyc' => [\App\Controllers\AdminController::class, 'verifyKycManual'],
        '/admin/user/delete' => [\App\Controllers\AdminController::class, 'deleteUser'],
        '/admin/subscriptions/update' => [\App\Controllers\AdminController::class, 'updatePlan'],
        '/admin/razorpay/update' => [\App\Controllers\AdminController::class, 'updateRazorpay'],
        '/admin/razorpay/test' => [\App\Controllers\AdminController::class, 'testRazorpay'],
        '/admin/boost/update' => [\App\Controllers\AdminController::class, 'updateBoostSettings'],
        '/admin/bots/create' => [\App\Controllers\AdminController::class, 'createBot'],
        '/admin/bots/toggle' => [\App\Controllers\AdminController::class, 'toggleBot'],
        '/admin/bots/system-toggle' => [\App\Controllers\AdminController::class, 'toggleBotSystem'],
        '/admin/bots/delete' => [\App\Controllers\AdminController::class, 'deleteBot'],
        '/admin/bots/message/add' => [\App\Controllers\AdminController::class, 'addBotMessage'],
        '/admin/bots/message/update' => [\App\Controllers\AdminController::class, 'updateBotMessage'],
        '/admin/bots/message/delete' => [\App\Controllers\AdminController::class, 'deleteBotMessage'],
        '/admin/bots/cron-trigger' => [\App\Controllers\AdminController::class, 'triggerBotCron'],
        '/admin/kyc/review' => [\App\Controllers\AdminController::class, 'reviewKyc'],
        '/admin/kyc/toggle-global' => [\App\Controllers\AdminController::class, 'toggleGlobalKyc'],
        '/admin/reports/resolve' => [\App\Controllers\AdminController::class, 'resolveReport'],
        '/admin/report/dismiss' => [\App\Controllers\AdminController::class, 'dismissReport'],
        '/admin/discovery/update' => [\App\Controllers\AdminController::class, 'updateDiscoverySettings'],
        '/admin/interests/save' => [\App\Controllers\AdminController::class, 'saveInterest'],
        '/admin/interests/delete' => [\App\Controllers\AdminController::class, 'deleteInterest'],
        '/admin/landing-profiles/save' => [\App\Controllers\AdminController::class, 'saveLandingProfile'],
        '/admin/landing-profiles/delete' => [\App\Controllers\AdminController::class, 'deleteLandingProfile'],
        '/admin/content/save' => [\App\Controllers\AdminController::class, 'saveContent'],
        '/admin/settings/update' => [\App\Controllers\AdminController::class, 'updateSettings'],
        '/admin/system/maintenance' => [\App\Controllers\AdminController::class, 'toggleMaintenance'],
        '/admin/system/cleanup' => [\App\Controllers\AdminController::class, 'cleanupSystem'],
    ]
];

// 7. Route Dispatching
if (isset($routes[$method][$path])) {
    [$controllerClass, $action] = $routes[$method][$path];
    $controller = new $controllerClass();
    $controller->$action();
    exit;
}

// 8. 404 Fallback Handling
http_response_code(404);

// For API, AJAX, or JSON requests, return JSON error payload instead of HTML redirect
$isApi = str_starts_with($path, '/api/') 
    || str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')
    || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

if ($isApi) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['success' => false, 'error' => 'Resource or endpoint not found.'], JSON_UNESCAPED_SLASHES);
    exit;
}

if (\App\Helpers\Auth::check()) {
    header('Location: /home');
    exit;
} else {
    header('Location: /');
    exit;
}
