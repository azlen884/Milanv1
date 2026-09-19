<?php

namespace App\Helpers;

class Auth {
    private static ?array $cachedUser = null;

    public static function check(): bool {
        return self::id() !== null;
    }

    public static function id(): ?int {
        $uid = Session::get('user_id');
        return $uid ? (int)$uid : null;
    }

    public static function login(int $userId): void {
        Session::regenerate();
        Session::set('user_id', $userId);
        self::$cachedUser = null;
        self::updateLastActive($userId);
    }

    public static function logout(): void {
        Session::remove('user_id');
        Session::destroy();
        self::$cachedUser = null;
    }

    public static function user(): ?array {
        $id = self::id();
        if (!$id) {
            return null;
        }

        if (self::$cachedUser !== null) {
            return self::$cachedUser;
        }

        $sql = "SELECT u.id, u.email, u.status, u.created_at, u.last_active_at,
                       p.name, p.dob, p.age, p.gender, p.city, p.bio, p.occupation, p.education,
                       p.height_cm, p.primary_photo, p.languages, p.kyc_status, p.opt_out_visitors, p.is_incognito
                FROM users u
                LEFT JOIN user_profiles p ON u.id = p.user_id
                WHERE u.id = :id AND u.status != 'banned' LIMIT 1";

        $user = Database::one($sql, [':id' => $id]);
        if (!$user) {
            self::logout();
            return null;
        }

        // Get active subscription
        $sub = self::getSubscription($id);
        $user['subscription'] = $sub;
        $user['is_paid'] = ($sub && $sub['code'] !== 'free');

        // Unread messages count
        $unread = Database::one(
            "SELECT COUNT(*) as cnt FROM messages WHERE receiver_id = :id AND is_read = 0",
            [':id' => $id]
        );
        $user['unread_messages'] = (int)($unread['cnt'] ?? 0);

        // Unread notifications count
        $unreadNotif = Database::one(
            "SELECT COUNT(*) as cnt FROM notifications WHERE user_id = :id AND is_read = 0",
            [':id' => $id]
        );
        $user['unread_notifications'] = (int)($unreadNotif['cnt'] ?? 0);

        self::$cachedUser = $user;
        return $user;
    }

    public static function updateLastActive(int $userId): void {
        Database::execute(
            "UPDATE users SET last_active_at = NOW() WHERE id = :id",
            [':id' => $userId]
        );
    }

    public static function getSubscription(int $userId): array {
        $sql = "SELECT s.id as subscription_id, s.starts_at, s.expires_at, s.status,
                       p.id as plan_id, p.code, p.name, p.price_inr, p.daily_messages_limit,
                       p.can_view_visitors, p.includes_boost
                FROM subscriptions s
                JOIN subscription_plans p ON s.plan_id = p.id
                WHERE s.user_id = :user_id 
                  AND s.status = 'active' 
                  AND s.expires_at > NOW()
                ORDER BY s.id DESC LIMIT 1";

        $sub = Database::one($sql, [':user_id' => $userId]);
        if ($sub) {
            return $sub;
        }

        // Fallback to Free plan if none active or expired
        $freePlan = Database::one("SELECT * FROM subscription_plans WHERE code = 'free' LIMIT 1");
        return [
            'subscription_id' => 0,
            'starts_at' => date('Y-m-d H:i:s'),
            'expires_at' => date('Y-m-d H:i:s', strtotime('+100 years')),
            'status' => 'active',
            'plan_id' => (int)($freePlan['id'] ?? 1),
            'code' => 'free',
            'name' => $freePlan['name'] ?? 'Free Plan',
            'price_inr' => 0,
            'daily_messages_limit' => 4,
            'can_view_visitors' => 0,
            'includes_boost' => 0,
        ];
    }

    public static function getDailySentMessagesCount(int $userId): int {
        // Today in Indian Standard Time / Server Date
        $todayStart = date('Y-m-d 00:00:00');
        $todayEnd = date('Y-m-d 23:59:59');

        $result = Database::one(
            "SELECT COUNT(*) as cnt FROM messages 
             WHERE sender_id = :user_id 
               AND created_at BETWEEN :start AND :end",
            [':user_id' => $userId, ':start' => $todayStart, ':end' => $todayEnd]
        );

        return (int)($result['cnt'] ?? 0);
    }

    public static function canSendMessage(int $userId): array {
        $sub = self::getSubscription($userId);
        
        // Paid plan has daily_messages_limit = 0 (unlimited)
        if ($sub['code'] !== 'free' || (int)$sub['daily_messages_limit'] === 0) {
            return [
                'allowed' => true,
                'is_paid' => true,
                'used_today' => self::getDailySentMessagesCount($userId),
                'limit' => 0,
            ];
        }

        // Free plan: strictly 4 sent messages per day
        $used = self::getDailySentMessagesCount($userId);
        $limit = (int)$sub['daily_messages_limit']; // 4

        return [
            'allowed' => ($used < $limit),
            'is_paid' => false,
            'used_today' => $used,
            'limit' => $limit,
            'remaining' => max(0, $limit - $used),
        ];
    }

    public static function hasActiveBoost(int $userId): bool {
        $boost = Database::one(
            "SELECT id FROM boosts WHERE user_id = :uid AND status = 'active' AND expires_at > NOW() LIMIT 1",
            [':uid' => $userId]
        );
        return $boost !== null;
    }
}
