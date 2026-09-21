<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\AdminAuth;
use App\Helpers\View;
use App\Services\BotService;

class CronController {

    /**
     * Main cron entry point: /cron?key=YOUR_SECRET_KEY
     */
    public function run(): void {
        $this->authorizeCron();

        // 1. Process bot automated messages
        $botResult = BotService::runAllEligible(50);

        // 2. Expire past subscriptions
        $expiredCount = 0;
        try {
            $expiredCount = Database::execute(
                "UPDATE subscriptions 
                 SET status = 'expired' 
                 WHERE status = 'active' AND expires_at <= NOW()"
            );
        } catch (\Throwable $e) {
            error_log('Subscription expiration error: ' . $e->getMessage());
        }

        // 3. Deactivate expired boosts
        $boostsDeactivated = 0;
        try {
            $boostsDeactivated = Database::execute(
                "UPDATE boosts 
                 SET status = 'expired' 
                 WHERE status = 'active' AND expires_at <= NOW()"
            );
        } catch (\Throwable $e) {
            error_log('Boost expiration error: ' . $e->getMessage());
        }

        $output = [
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'bots' => $botResult,
            'subscriptions_expired' => $expiredCount,
            'boosts_deactivated' => $boostsDeactivated,
        ];

        // Format response
        $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
        if (str_contains($accept, 'application/json') || isset($_GET['format']) && $_GET['format'] === 'json') {
            View::json($output);
        }

        header('Content-Type: text/plain; charset=utf-8');
        echo "=== Milan Dating Cron Executed Successfully ===\n";
        echo "Timestamp: " . $output['timestamp'] . "\n";
        echo "Bot Status: " . ($botResult['status'] ?? 'unknown') . "\n";
        echo "Users Processed: " . ($botResult['users_processed'] ?? 0) . "\n";
        echo "Messages Sent: " . ($botResult['messages_sent'] ?? 0) . "\n";
        echo "Execution Time: " . ($botResult['duration_ms'] ?? 0) . "ms\n";
        echo "Subscriptions Expired: {$expiredCount}\n";
        echo "Boosts Deactivated: {$boostsDeactivated}\n";
        exit;
    }

    /**
     * Dedicated bot cron route: /api/cron/bots?key=YOUR_SECRET_KEY
     */
    public function runBots(): void {
        $this->authorizeCron();

        $limit = max(1, min(200, (int)($_GET['limit'] ?? $_POST['limit'] ?? 50)));
        $result = BotService::runAllEligible($limit);

        View::json([
            'success' => true,
            'timestamp' => date('Y-m-d H:i:s'),
            'data' => $result,
        ]);
    }

    /**
     * Verify cron authorization token or admin session
     */
    private function authorizeCron(): void {
        // Allow logged in super admins to trigger directly from UI
        if (AdminAuth::check()) {
            return;
        }

        // Get key from query string, header, or POST
        $providedKey = $_GET['key'] ?? $_POST['key'] ?? $_SERVER['HTTP_X_CRON_KEY'] ?? '';

        $expectedKey = '';
        try {
            $setting = Database::one(
                "SELECT setting_value FROM settings WHERE setting_key = 'cron_secret_key' LIMIT 1"
            );
            $expectedKey = $setting['setting_value'] ?? 'milan_cron_secret_2026';
        } catch (\Throwable $e) {
            $expectedKey = 'milan_cron_secret_2026';
        }

        if (empty($providedKey) || !hash_equals($expectedKey, (string)$providedKey)) {
            header('HTTP/1.1 403 Forbidden');
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'error' => 'Unauthorized cron request. Provide a valid ?key= parameter.',
            ]);
            exit;
        }
    }
}
