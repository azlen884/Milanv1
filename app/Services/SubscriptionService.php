<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Auth;

class SubscriptionService {
    public static function assignFreePlan(int $userId): int {
        $freePlan = Database::one("SELECT id FROM subscription_plans WHERE code = 'free' LIMIT 1");
        if (!$freePlan) {
            return 0;
        }

        $planId = (int)$freePlan['id'];
        $startsAt = date('Y-m-d H:i:s');
        $expiresAt = date('Y-m-d H:i:s', strtotime('+100 years'));

        return Database::insert(
            "INSERT INTO subscriptions (user_id, plan_id, starts_at, expires_at, status) 
             VALUES (:user_id, :plan_id, :starts, :expires, 'active')",
            [
                ':user_id' => $userId,
                ':plan_id' => $planId,
                ':starts' => $startsAt,
                ':expires' => $expiresAt,
            ]
        );
    }

    public static function upgrade(int $userId, int $planId): bool {
        $plan = Database::one(
            "SELECT * FROM subscription_plans WHERE id = :id AND is_active = 1 AND code != 'free'",
            [':id' => $planId]
        );

        if (!$plan) {
            return false;
        }

        // Check if user has an active paid subscription with remaining days
        $activeSub = Database::one(
            "SELECT s.id, s.expires_at, p.code 
             FROM subscriptions s
             JOIN subscription_plans p ON s.plan_id = p.id
             WHERE s.user_id = :uid AND s.status = 'active' AND s.expires_at > NOW() AND p.code != 'free'
             ORDER BY s.expires_at DESC LIMIT 1",
            [':uid' => $userId]
        );

        $now = time();
        $baseTime = ($activeSub && strtotime($activeSub['expires_at']) > $now)
            ? strtotime($activeSub['expires_at'])
            : $now;

        // Expire prior active subscription records
        Database::execute(
            "UPDATE subscriptions SET status = 'expired' WHERE user_id = :uid AND status = 'active'",
            [':uid' => $userId]
        );

        $startsAt = date('Y-m-d H:i:s');
        $durationDays = (int)$plan['duration_days'];
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationDays} days", $baseTime));

        Database::insert(
            "INSERT INTO subscriptions (user_id, plan_id, starts_at, expires_at, status) 
             VALUES (:user_id, :plan_id, :starts, :expires, 'active')",
            [
                ':user_id' => $userId,
                ':plan_id' => $plan['id'],
                ':starts' => $startsAt,
                ':expires' => $expiresAt,
            ]
        );

        // If plan includes boost (e.g. 3-Month plan), grant boost
        if (!empty($plan['includes_boost'])) {
            $boostDuration = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_duration_hours'")['setting_value'] ?? 24);
            $boostExpires = date('Y-m-d H:i:s', strtotime("+{$boostDuration} hours"));
            Database::insert(
                "INSERT INTO boosts (user_id, starts_at, expires_at, status) 
                 VALUES (:uid, NOW(), :exp, 'active')",
                [':uid' => $userId, ':exp' => $boostExpires]
            );
        }

        // Create notification
        Database::insert(
            "INSERT INTO notifications (user_id, type, title, body, link) 
             VALUES (:uid, 'subscription', 'Premium Activated', :body, '/subscription')",
            [
                ':uid' => $userId,
                ':body' => "Welcome to {$plan['name']}! Enjoy unlimited messaging and exclusive features.",
            ]
        );

        // Invalidate auth cache so changes take effect immediately
        Auth::clearCache();

        return true;
    }

    public static function checkDailyMessagePermission(int $userId): array {
        return Auth::canSendMessage($userId);
    }

    public static function expireOverdue(): int {
        $stmt = Database::connect()->prepare(
            "UPDATE subscriptions SET status = 'expired' WHERE status = 'active' AND expires_at <= NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
