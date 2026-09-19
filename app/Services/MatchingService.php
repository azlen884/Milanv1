<?php

namespace App\Services;

use App\Helpers\Database;

class MatchingService {
    /**
     * Send Interest from sender to receiver
     * Returns: ['success' => bool, 'is_match' => bool, 'message' => string]
     */
    public static function sendInterest(int $senderId, int $receiverId): array {
        if ($senderId === $receiverId) {
            return ['success' => false, 'is_match' => false, 'message' => 'Cannot express interest to yourself.'];
        }

        // Check if receiver has blocked sender or vice versa
        $isBlocked = Database::one(
            "SELECT id FROM blocked_users 
             WHERE (blocker_id = :s AND blocked_id = :r) OR (blocker_id = :r AND blocked_id = :s)",
            [':s' => $senderId, ':r' => $receiverId]
        );
        if ($isBlocked) {
            return ['success' => false, 'is_match' => false, 'message' => 'Unable to connect with this profile.'];
        }

        // Check if already sent
        $existing = Database::one(
            "SELECT id, status FROM user_interactions WHERE sender_id = :s AND receiver_id = :r",
            [':s' => $senderId, ':r' => $receiverId]
        );
        if ($existing) {
            return ['success' => false, 'is_match' => false, 'message' => 'Interest already sent.'];
        }

        // Record interaction
        Database::insert(
            "INSERT INTO user_interactions (sender_id, receiver_id, status) VALUES (:s, :r, 'pending')",
            [':s' => $senderId, ':r' => $receiverId]
        );

        // Check if the receiver had already expressed interest in sender (mutual interest!)
        $reciprocal = Database::one(
            "SELECT id FROM user_interactions WHERE sender_id = :r AND receiver_id = :s",
            [':r' => $receiverId, ':s' => $senderId]
        );

        if ($reciprocal) {
            // Mutual interest confirmed -> create real Match!
            $u1 = min($senderId, $receiverId);
            $u2 = max($senderId, $receiverId);

            Database::execute(
                "INSERT IGNORE INTO matches (user1_id, user2_id, matched_at) VALUES (:u1, :u2, NOW())",
                [':u1' => $u1, ':u2' => $u2]
            );

            // Create initial conversation if not exists
            Database::execute(
                "INSERT IGNORE INTO conversations (user1_id, user2_id, last_message_at) VALUES (:u1, :u2, NOW())",
                [':u1' => $u1, ':u2' => $u2]
            );

            // Update statuses to accepted
            Database::execute(
                "UPDATE user_interactions SET status = 'accepted' 
                 WHERE (sender_id = :s AND receiver_id = :r) OR (sender_id = :r AND receiver_id = :s)",
                [':s' => $senderId, ':r' => $receiverId]
            );

            // Notify both users
            $senderProfile = Database::one("SELECT name FROM user_profiles WHERE user_id = :id", [':id' => $senderId]);
            $receiverProfile = Database::one("SELECT name FROM user_profiles WHERE user_id = :id", [':id' => $receiverId]);
            $sName = !empty($senderProfile['name']) ? $senderProfile['name'] : 'A member';
            $rName = !empty($receiverProfile['name']) ? $receiverProfile['name'] : 'A member';

            Database::insert(
                "INSERT INTO notifications (user_id, type, title, body, link) 
                 VALUES (:uid, 'match', \"It's a Match!\", :body, '/matches')",
                [
                    ':uid' => $senderId,
                    ':body' => "You and {$rName} liked each other! Start a conversation.",
                ]
            );

            Database::insert(
                "INSERT INTO notifications (user_id, type, title, body, link) 
                 VALUES (:uid, 'match', \"It's a Match!\", :body, '/matches')",
                [
                    ':uid' => $receiverId,
                    ':body' => "You and {$sName} liked each other! Start a conversation.",
                ]
            );

            return ['success' => true, 'is_match' => true, 'message' => "It's a Match!"];
        }

        // Notify receiver of new Interest
        $senderProfile = Database::one("SELECT name FROM user_profiles WHERE user_id = :id", [':id' => $senderId]);
        $sName = !empty($senderProfile['name']) ? $senderProfile['name'] : 'A member';
        Database::insert(
            "INSERT INTO notifications (user_id, type, title, body, link) 
             VALUES (:uid, 'interest', 'New Interest Received', :body, '/matches?tab=received')",
            [
                ':uid' => $receiverId,
                ':body' => "{$sName} expressed interest in your profile.",
            ]
        );

        return ['success' => true, 'is_match' => false, 'message' => 'Interest sent successfully!'];
    }

    public static function getMatches(int $userId): array {
        $sql = "SELECT m.id as match_id, m.matched_at,
                       CASE WHEN m.user1_id = :uid THEN m.user2_id ELSE m.user1_id END as other_user_id,
                       p.name, p.age, p.city, p.primary_photo, p.occupation, p.kyc_status,
                       u.last_active_at,
                       c.id as conversation_id
                FROM matches m
                JOIN users u ON u.id = (CASE WHEN m.user1_id = :uid THEN m.user2_id ELSE m.user1_id END)
                JOIN user_profiles p ON p.user_id = u.id
                LEFT JOIN conversations c ON ((c.user1_id = m.user1_id AND c.user2_id = m.user2_id) OR (c.user1_id = m.user2_id AND c.user2_id = m.user1_id))
                WHERE (m.user1_id = :uid OR m.user2_id = :uid)
                  AND u.status = 'active'
                ORDER BY m.matched_at DESC";

        return Database::query($sql, [':uid' => $userId]);
    }

    public static function getInterestsReceived(int $userId): array {
        $sql = "SELECT ui.id as interaction_id, ui.created_at, ui.status,
                       p.user_id, p.name, p.age, p.city, p.primary_photo, p.occupation, p.kyc_status, p.bio
                FROM user_interactions ui
                JOIN users u ON u.id = ui.sender_id
                JOIN user_profiles p ON p.user_id = u.id
                WHERE ui.receiver_id = :uid 
                  AND ui.status = 'pending'
                  AND u.status = 'active'
                  AND ui.sender_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)
                ORDER BY ui.created_at DESC";

        return Database::query($sql, [':uid' => $userId]);
    }

    public static function getInterestsSent(int $userId): array {
        $sql = "SELECT ui.id as interaction_id, ui.created_at, ui.status,
                       p.user_id, p.name, p.age, p.city, p.primary_photo, p.occupation, p.kyc_status
                FROM user_interactions ui
                JOIN users u ON u.id = ui.receiver_id
                JOIN user_profiles p ON p.user_id = u.id
                WHERE ui.sender_id = :uid
                  AND u.status = 'active'
                ORDER BY ui.created_at DESC";

        return Database::query($sql, [':uid' => $userId]);
    }
}
