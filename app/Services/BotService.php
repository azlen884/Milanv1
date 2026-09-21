<?php

namespace App\Services;

use App\Helpers\Database;

class BotService {
    private static array $lastChecked = [];

    /**
     * Check if bot system is globally enabled in settings
     */
    public static function isSystemEnabled(): bool {
        try {
            $setting = Database::one(
                "SELECT setting_value FROM settings WHERE setting_key = 'bot_system_enabled' LIMIT 1"
            );
            return !empty($setting) && (string)$setting['setting_value'] === '1';
        } catch (\Throwable $e) {
            return false;
        }
    }

    /**
     * Process bot messages for a specific user (only free users; strictly stops for paid users)
     *
     * @param int $userId Target user ID
     * @param bool $forceBypassThrottle If true, skip the 60-second in-memory throttle (e.g. from cron)
     * @return array Result of processing
     */
    public static function processForUser(int $userId, bool $forceBypassThrottle = false): array {
        if ($userId <= 0) {
            return ['sent' => 0, 'reason' => 'invalid_user_id'];
        }

        // 1. Check master system toggle
        if (!self::isSystemEnabled()) {
            return ['sent' => 0, 'reason' => 'bot_system_disabled'];
        }

        // 2. Throttle checks per user (prevent duplicate hits on rapid page loads)
        $now = time();
        if (!$forceBypassThrottle) {
            if (isset(self::$lastChecked[$userId]) && ($now - self::$lastChecked[$userId] < 60)) {
                return ['sent' => 0, 'reason' => 'throttled'];
            }
            if (isset($_SESSION['last_bot_check_' . $userId]) && ($now - (int)$_SESSION['last_bot_check_' . $userId] < 60)) {
                return ['sent' => 0, 'reason' => 'session_throttled'];
            }
        }
        self::$lastChecked[$userId] = $now;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_bot_check_' . $userId] = $now;
        }

        // 3. User eligibility check: ensure user is active, not an admin, not a bot
        $user = Database::one(
            "SELECT u.id, u.created_at, u.status, u.is_admin
             FROM users u
             WHERE u.id = :uid
             LIMIT 1",
            [':uid' => $userId]
        );

        if (!$user || $user['status'] !== 'active' || (int)$user['is_admin'] === 1) {
            return ['sent' => 0, 'reason' => 'user_not_eligible'];
        }

        // Ensure user is not an admin bot account
        $isBot = Database::one("SELECT id FROM admin_bots WHERE user_id = :uid LIMIT 1", [':uid' => $userId]);
        if ($isBot) {
            return ['sent' => 0, 'reason' => 'user_is_bot'];
        }

        // 4. CRITICAL: Stop all bot messages if the user has an active paid subscription
        $paidSub = Database::one(
            "SELECT id FROM subscriptions 
             WHERE user_id = :uid 
               AND status = 'active' 
               AND plan_id > 1 
               AND expires_at > NOW() 
             LIMIT 1",
            [':uid' => $userId]
        );

        if ($paidSub) {
            return ['sent' => 0, 'reason' => 'paid_subscriber'];
        }

        // 5. Calculate elapsed minutes since registration
        $regTime = strtotime($user['created_at']);
        $elapsedMinutes = max(0, (int)floor(($now - $regTime) / 60));

        // 6. Find due messages from active bots that have not yet been delivered to this user
        // Limit to 1 message per execution to prevent flooding the user
        $dueMessages = Database::query(
            "SELECT bm.id as bot_message_id, bm.bot_id, bm.message_text, bm.delay_minutes,
                    b.user_id as bot_user_id, b.name as bot_name, b.avatar_url
             FROM admin_bot_messages bm
             JOIN admin_bots b ON bm.bot_id = b.id
             WHERE b.is_active = 1
               AND bm.delay_minutes <= :elapsed
               AND NOT EXISTS (
                   SELECT 1 FROM admin_bot_deliveries bd 
                   WHERE bd.bot_message_id = bm.id AND bd.user_id = :uid
               )
             ORDER BY bm.delay_minutes ASC, bm.message_order ASC
             LIMIT 1",
            [
                ':elapsed' => $elapsedMinutes,
                ':uid' => $userId,
            ]
        );

        if (empty($dueMessages)) {
            return ['sent' => 0, 'reason' => 'no_due_messages'];
        }

        $sentCount = 0;
        $sentDetails = [];

        foreach ($dueMessages as $item) {
            $botUserId = (int)$item['bot_user_id'];
            if ($botUserId === $userId || $botUserId <= 0) {
                continue;
            }

            // Deduplication safeguard: Verify not already delivered
            $alreadyDelivered = Database::one(
                "SELECT id FROM admin_bot_deliveries 
                 WHERE bot_message_id = :bmid AND user_id = :uid 
                 LIMIT 1",
                [
                    ':bmid' => $item['bot_message_id'],
                    ':uid' => $userId,
                ]
            );
            if ($alreadyDelivered) {
                continue;
            }

            // Verify or create conversation between bot and user
            $u1 = min($userId, $botUserId);
            $u2 = max($userId, $botUserId);

            $conv = Database::one(
                "SELECT id FROM conversations WHERE user1_id = :u1 AND user2_id = :u2 LIMIT 1",
                [':u1' => $u1, ':u2' => $u2]
            );

            if (!$conv) {
                $convId = Database::insert(
                    "INSERT INTO conversations (user1_id, user2_id, last_message_at, created_at, updated_at) 
                     VALUES (:u1, :u2, NOW(), NOW(), NOW())",
                    [':u1' => $u1, ':u2' => $u2]
                );
            } else {
                $convId = (int)$conv['id'];
            }

            // Insert real chat message from bot to user with proper schema
            $messageId = Database::insert(
                "INSERT INTO messages (conversation_id, sender_id, receiver_id, type, body, is_read, created_at)
                 VALUES (:cid, :sid, :rid, 'text', :body, 0, NOW())",
                [
                    ':cid' => $convId,
                    ':sid' => $botUserId,
                    ':rid' => $userId,
                    ':body' => $item['message_text'],
                ]
            );

            // Update conversation last_message_at
            Database::execute(
                "UPDATE conversations SET last_message_at = NOW() WHERE id = :cid",
                [':cid' => $convId]
            );

            // Record delivery in admin_bot_deliveries
            Database::insert(
                "INSERT INTO admin_bot_deliveries (bot_id, bot_message_id, user_id, message_id, sent_at)
                 VALUES (:bid, :bmid, :uid, :mid, NOW())",
                [
                    ':bid' => $item['bot_id'],
                    ':bmid' => $item['bot_message_id'],
                    ':uid' => $userId,
                    ':mid' => $messageId,
                ]
            );

            // Create notification so the user sees a new message alert
            $botName = htmlspecialchars($item['bot_name'] ?? 'Someone');
            $preview = mb_substr($item['message_text'], 0, 45) . (mb_strlen($item['message_text']) > 45 ? '...' : '');
            Database::insert(
                "INSERT INTO notifications (user_id, type, title, body, link, created_at) 
                 VALUES (:uid, 'message', 'New Message', :body, :link, NOW())",
                [
                    ':uid' => $userId,
                    ':body' => "{$botName}: {$preview}",
                    ':link' => "/chat?id={$convId}",
                ]
            );

            // Update bot user's last_active_at naturally
            Database::execute(
                "UPDATE users SET last_active_at = NOW() WHERE id = :bid",
                [':bid' => $botUserId]
            );

            $sentCount++;
            $sentDetails[] = [
                'bot_id' => $item['bot_id'],
                'bot_name' => $item['bot_name'],
                'bot_message_id' => $item['bot_message_id'],
                'message_id' => $messageId,
                'conversation_id' => $convId,
            ];
        }

        return [
            'sent' => $sentCount,
            'details' => $sentDetails,
            'reason' => $sentCount > 0 ? 'success' : 'nothing_sent',
        ];
    }

    /**
     * Batch process bot messages for all eligible free users (Cron entry point)
     *
     * @param int $limit Max users to check per run
     * @return array Execution summary
     */
    public static function runAllEligible(int $limit = 50): array {
        $startTime = microtime(true);

        if (!self::isSystemEnabled()) {
            $durationMs = (int)round((microtime(true) - $startTime) * 1000);
            self::logCronRun('disabled', 0, 0, $durationMs, 'Bot system is globally disabled in settings.');
            return [
                'success' => false,
                'status' => 'disabled',
                'message' => 'Bot system is globally disabled in settings.',
                'messages_sent' => 0,
                'users_processed' => 0,
                'duration_ms' => $durationMs,
            ];
        }

        // Find active non-admin, non-bot users who do not have an active paid subscription
        $limitInt = max(1, min(500, (int)$limit));
        $eligibleUsers = Database::query(
            "SELECT u.id, u.created_at
             FROM users u
             WHERE u.status = 'active'
               AND u.is_admin = 0
               AND NOT EXISTS (SELECT 1 FROM admin_bots b WHERE b.user_id = u.id)
               AND NOT EXISTS (
                   SELECT 1 FROM subscriptions s 
                   WHERE s.user_id = u.id 
                     AND s.status = 'active' 
                     AND s.plan_id > 1 
                     AND s.expires_at > NOW()
               )
             ORDER BY u.last_active_at DESC
             LIMIT {$limitInt}"
        );

        $totalSent = 0;
        $processedCount = 0;

        foreach ($eligibleUsers as $u) {
            $processedCount++;
            $res = self::processForUser((int)$u['id'], true);
            $totalSent += ($res['sent'] ?? 0);
        }

        $durationMs = (int)round((microtime(true) - $startTime) * 1000);
        $logOutput = "Processed {$processedCount} eligible users. Sent {$totalSent} automated messages.";

        self::logCronRun('success', $totalSent, $processedCount, $durationMs, $logOutput);

        return [
            'success' => true,
            'status' => 'success',
            'message' => $logOutput,
            'messages_sent' => $totalSent,
            'users_processed' => $processedCount,
            'duration_ms' => $durationMs,
        ];
    }

    /**
     * Record execution result in bot_cron_logs table
     */
    private static function logCronRun(string $status, int $sent, int $processed, int $durationMs, string $output): void {
        try {
            Database::insert(
                "INSERT INTO bot_cron_logs (status, messages_sent, users_processed, duration_ms, log_output, executed_at)
                 VALUES (:st, :sent, :proc, :dur, :out, NOW())",
                [
                    ':st' => $status,
                    ':sent' => $sent,
                    ':proc' => $processed,
                    ':dur' => $durationMs,
                    ':out' => $output,
                ]
            );
        } catch (\Throwable $e) {
            error_log('Failed to log bot cron run: ' . $e->getMessage());
        }
    }
}
