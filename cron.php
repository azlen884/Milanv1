<?php
/**
 * Milan Dating - Server Cron & Bot Task Runner
 *
 * Usage via crontab / cPanel:
 *   * * * * * php /path/to/project/cron.php >> /path/to/bot.log 2>&1
 *
 * Or trigger via web:
 *   curl -s "https://yourdomain.com/cron?key=YOUR_SECRET_KEY"
 */

// If invoked from browser without key, forward to router
if (php_sapi_name() !== 'cli') {
    require_once __DIR__ . '/public/index.php';
    exit;
}

// CLI Execution Mode
$startTime = microtime(true);
echo "[" . date('Y-m-d H:i:s') . "] Starting Milan Dating Cron...\n";

require_once __DIR__ . '/config/app.php';
require_once __DIR__ . '/app/Helpers/Database.php';
require_once __DIR__ . '/app/Services/BotService.php';

use App\Helpers\Database;
use App\Services\BotService;

try {
    // 1. Process automated bot messages
    echo "Processing automated bot messages for eligible free users...\n";
    $botResult = BotService::runAllEligible(50);
    echo "-> Bot System Status: " . ($botResult['status'] ?? 'unknown') . "\n";
    echo "-> Users Checked: " . ($botResult['users_processed'] ?? 0) . "\n";
    echo "-> Messages Sent: " . ($botResult['messages_sent'] ?? 0) . "\n";
    echo "-> Execution Duration: " . ($botResult['duration_ms'] ?? 0) . "ms\n";

    // 2. Expire past subscriptions
    $expiredSubs = Database::execute(
        "UPDATE subscriptions 
         SET status = 'expired' 
         WHERE status = 'active' AND expires_at <= NOW()"
    );
    if ($expiredSubs > 0) {
        echo "-> Expired {$expiredSubs} outdated subscriptions.\n";
    }

    // 3. Deactivate expired boosts
    $deactivatedBoosts = Database::execute(
        "UPDATE boosts 
         SET status = 'expired' 
         WHERE status = 'active' AND expires_at <= NOW()"
    );
    if ($deactivatedBoosts > 0) {
        echo "-> Deactivated {$deactivatedBoosts} expired profile boosts.\n";
    }

    $totalTimeMs = (int)round((microtime(true) - $startTime) * 1000);
    echo "[" . date('Y-m-d H:i:s') . "] Cron completed successfully in {$totalTimeMs}ms.\n";
    exit(0);

} catch (\Throwable $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Cron failed with error: " . $e->getMessage() . "\n";
    error_log("Cron CLI error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    exit(1);
}
