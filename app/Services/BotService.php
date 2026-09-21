<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Security;

class BotService {
    private static array $lastChecked = [];

    /**
     * Process bot messages for a specific user (only free users; stops for paid users)
     */
    public static function processForUser(int $userId): void {
        if ($userId <= 0) {
            return;
        }

        // Throttle checks to at most once every 60 seconds per user
        $now = time();
        if (isset(self::$lastChecked[$userId]) && ($now - self::$lastChecked[$userId] < 60)) {
            return;
        }
        if (isset($_SESSION['last_bot_check_' . $userId]) && ($now - (int)$_SESSION['last_bot_check_' . $userId] < 60)) {
            return;
        }
        self::$lastChecked[$userId] = $now;
        if (session_status() === PHP_SESSION_ACTIVE) {
            $_SESSION['last_bot_check_' . $userId] = $now;
        }

        // CRITICAL: Stop all bot messages if the user has an active paid subscription
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
            return; // Paid subscriber: strictly zero bot messages
        }

        // Get user account registration time
        $user = Database::one("SELECT id, created_at, status FROM users WHERE id = :uid", [':uid' => $userId]);
        if (!$user || $user['status'] !== 'active') {
            return;
        }

        $regTime = strtotime($user['created_at']);
        $elapsedMinutes = max(0, (int)floor((time() - $regTime) / 60));

        // Find due messages from active bots that have not yet been delivered to this user
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
             ORDER BY bm.delay_minutes ASC, bm.message_order ASC",
            [
                ':elapsed' => $elapsedMinutes,
                ':uid' => $userId,
            ]
        );

        if (empty($dueMessages)) {
            return;
        }

        foreach ($dueMessages as $item) {
            $botUserId = (int)$item['bot_user_id'];
            if ($botUserId === $userId) {
                continue;
            }

            // Verify or create conversation
            $u1 = min($userId, $botUserId);
            $u2 = max($userId, $botUserId);

            $conv = Database::one(
                "SELECT id FROM conversations WHERE user1_id = :u1 AND user2_id = :u2 LIMIT 1",
                [':u1' => $u1, ':u2' => $u2]
            );

            if (!$conv) {
                $convId = Database::insert(
                    "INSERT INTO conversations (user1_id, user2_id, created_at, updated_at) VALUES (:u1, :u2, NOW(), NOW())",
                    [':u1' => $u1, ':u2' => $u2]
                );
            } else {
                $convId = (int)$conv['id'];
            }

            // Insert real chat message from bot to user
            $messageId = Database::insert(
                "INSERT INTO messages (conversation_id, sender_id, type, content, is_read, created_at)
                 VALUES (:cid, :sid, 'text', :content, 0, NOW())",
                [
                    ':cid' => $convId,
                    ':sid' => $botUserId,
                    ':content' => $item['message_text'],
                ]
            );

            // Update conversation timestamp
            Database::execute(
                "UPDATE conversations SET updated_at = NOW() WHERE id = :cid",
                [':cid' => $convId]
            );

            // Record delivery
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

            // Keep bot last active up-to-date
            Database::execute(
                "UPDATE users SET last_active_at = NOW() WHERE id = :bid",
                [':bid' => $botUserId]
            );
        }
    }
}
