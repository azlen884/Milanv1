<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Auth;
use Exception;

class ChatService {
    /**
     * Get or create a conversation between two users
     */
    public static function getOrCreateConversation(int $userA, int $userB): ?array {
        if ($userA === $userB) {
            return null;
        }

        // Check if blocked
        $blocked = Database::one(
            "SELECT id FROM blocked_users 
             WHERE (blocker_id = :a AND blocked_id = :b) OR (blocker_id = :b AND blocked_id = :a)",
            [':a' => $userA, ':b' => $userB]
        );
        if ($blocked) {
            return null;
        }

        $u1 = min($userA, $userB);
        $u2 = max($userA, $userB);

        $conv = Database::one(
            "SELECT * FROM conversations WHERE user1_id = :u1 AND user2_id = :u2",
            [':u1' => $u1, ':u2' => $u2]
        );

        if (!$conv) {
            $id = Database::insert(
                "INSERT INTO conversations (user1_id, user2_id, last_message_at) VALUES (:u1, :u2, NOW())",
                [':u1' => $u1, ':u2' => $u2]
            );
            $conv = Database::one("SELECT * FROM conversations WHERE id = :id", [':id' => $id]);
        }

        return $conv;
    }

    /**
     * Send text message with strict server-side daily message limit enforcement
     */
    public static function sendTextMessage(int $senderId, int $conversationId, string $body): array {
        $body = trim($body);
        if ($body === '') {
            return ['success' => false, 'error' => 'Message body cannot be empty.'];
        }

        // Validate conversation & participant
        $conv = Database::one("SELECT * FROM conversations WHERE id = :id", [':id' => $conversationId]);
        if (!$conv || ($conv['user1_id'] !== $senderId && $conv['user2_id'] !== $senderId)) {
            return ['success' => false, 'error' => 'Invalid conversation.'];
        }

        $receiverId = ($conv['user1_id'] === $senderId) ? (int)$conv['user2_id'] : (int)$conv['user1_id'];

        // Check blocking
        $blocked = Database::one(
            "SELECT id FROM blocked_users 
             WHERE (blocker_id = :s AND blocked_id = :r) OR (blocker_id = :r AND blocked_id = :s)",
            [':s' => $senderId, ':r' => $receiverId]
        );
        if ($blocked) {
            return ['success' => false, 'error' => 'You cannot send messages to this user.'];
        }

        // STRICT DAILY LIMIT CHECK (Rule 11, 12, 19)
        $perm = Auth::canSendMessage($senderId);
        if (!$perm['allowed']) {
            return [
                'success' => false,
                'limit_exceeded' => true,
                'error' => 'Daily free message limit reached (4/4 messages used). Upgrade to Premium for unlimited messaging.',
                'remaining' => 0,
                'limit' => $perm['limit'],
                'used_today' => $perm['used_today'],
            ];
        }

        // Insert message
        $msgId = Database::insert(
            "INSERT INTO messages (conversation_id, sender_id, receiver_id, type, body, is_read, created_at) 
             VALUES (:cid, :sid, :rid, 'text', :body, 0, NOW())",
            [
                ':cid' => $conversationId,
                ':sid' => $senderId,
                ':rid' => $receiverId,
                ':body' => $body,
            ]
        );

        // Update conversation timestamp
        Database::execute(
            "UPDATE conversations SET last_message_at = NOW() WHERE id = :cid",
            [':cid' => $conversationId]
        );

        // Fetch sender name for notification
        $sender = Database::one("SELECT name FROM user_profiles WHERE user_id = :id", [':id' => $senderId]);
        Database::insert(
            "INSERT INTO notifications (user_id, type, title, body, link) 
             VALUES (:uid, 'message', 'New Message', :body, :link)",
            [
                ':uid' => $receiverId,
                ':body' => "{$sender['name']}: " . mb_substr($body, 0, 45) . (mb_strlen($body) > 45 ? '...' : ''),
                ':link' => "/chat?id={$conversationId}",
            ]
        );

        $newPerm = Auth::canSendMessage($senderId);

        return [
            'success' => true,
            'message_id' => $msgId,
            'is_paid' => $newPerm['is_paid'],
            'used_today' => $newPerm['used_today'],
            'limit' => $newPerm['limit'],
            'remaining' => $newPerm['remaining'] ?? 0,
        ];
    }

    /**
     * Mark conversation messages as read
     */
    public static function markAsRead(int $conversationId, int $currentUserId): void {
        Database::execute(
            "UPDATE messages SET is_read = 1 
             WHERE conversation_id = :cid AND receiver_id = :uid AND is_read = 0",
            [':cid' => $conversationId, ':uid' => $currentUserId]
        );
    }
}
