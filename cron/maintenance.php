<?php
/**
 * Milan Dating - CLI Maintenance & Scheduled Tasks
 * Plain PHP 8.x
 *
 * Usage via CLI / cPanel Cron:
 * php cron/maintenance.php
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Access Denied: This script may only be executed from the CLI.\n");
}

// 1. PSR-4 Autoloader
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

// 2. Load Configuration
require_once dirname(__DIR__) . '/config/app.php';

use App\Helpers\Database;

echo "====================================================\n";
echo " MILAN DATING - SCHEDULED MAINTENANCE & HEALTH CHECK \n";
echo "====================================================\n";
echo "Timestamp: " . date('Y-m-d H:i:s T') . "\n";
echo "PHP Version: " . PHP_VERSION . " (" . php_sapi_name() . ")\n";

// 3. Driver & Extension Diagnostic
try {
    Database::checkDriverAvailability();
    echo "[OK] Native PHP PDO & MySQL driver (pdo_mysql) verified.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[CRITICAL] Driver Diagnostic Failed: " . $e->getMessage() . "\n");
    exit(1);
}

// 4. Database Connection Verification
try {
    $db = Database::one("SELECT DATABASE() as db_name, VERSION() as ver");
    echo "[OK] Database Connected: " . ($db['db_name'] ?? 'unknown') . " (MySQL " . ($db['ver'] ?? 'unknown') . ")\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[CRITICAL] Database Connection Failed: " . $e->getMessage() . "\n");
    exit(1);
}

// 5. Maintenance Mode Check
try {
    $maint = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'");
    $status = ($maint['setting_value'] ?? '0') === '1' ? 'ACTIVE (Locked)' : 'INACTIVE (Online)';
    echo "[OK] Platform Maintenance Mode: {$status}\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[WARN] Maintenance mode setting query error: " . $e->getMessage() . "\n");
}

// 6. Expire Outdated Profile Boosts
try {
    $expiredCount = \App\Services\BoostService::expireOverdue();
    echo "[OK] Expired profile boosts pruned ({$expiredCount} updated).\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[WARN] Boost expiration task error: " . $e->getMessage() . "\n");
}

// 7. Prune Stale Rate Limit Windows (older than 24 hours)
try {
    Database::execute("DELETE FROM rate_limits WHERE reset_at < (UNIX_TIMESTAMP() - 86400)");
    echo "[OK] Stale rate limits cleaned up.\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "[WARN] Rate limit cleanup error: " . $e->getMessage() . "\n");
}

echo "----------------------------------------------------\n";
echo "Maintenance check completed successfully.\n";
echo "====================================================\n";
exit(0);
