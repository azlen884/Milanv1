<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Auth;

class BoostService {
    public static function canUserBoost(int $userId): array {
        $globallyEnabled = (bool)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_globally_enabled'")['setting_value'] ?? 1);
        if (!$globallyEnabled) {
            return ['allowed' => false, 'reason' => 'Profile Boost is currently unavailable.'];
        }

        $sub = Auth::getSubscription($userId);
        if ($sub['code'] === 'free') {
            return ['allowed' => false, 'reason' => 'Free members cannot Boost. Please upgrade to a Premium plan to boost your visibility.'];
        }

        $activeBoost = Database::one(
            "SELECT id, expires_at FROM boosts WHERE user_id = :uid AND status = 'active' AND expires_at > NOW() LIMIT 1",
            [':uid' => $userId]
        );
        if ($activeBoost) {
            return ['allowed' => false, 'reason' => 'You already have an active profile boost in effect until ' . date('M j, g:i A', strtotime($activeBoost['expires_at']))];
        }

        // 3-month plan gets boost included
        $isIncluded = !empty($sub['includes_boost']);
        $boostPrice = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'")['setting_value'] ?? 19);

        return [
            'allowed' => true,
            'is_included' => $isIncluded,
            'price_inr' => $isIncluded ? 0 : $boostPrice,
        ];
    }

    public static function activatePaidBoost(int $userId): bool {
        $durationHours = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_duration_hours'")['setting_value'] ?? 24);
        $expiresAt = date('Y-m-d H:i:s', strtotime("+{$durationHours} hours"));

        Database::execute(
            "UPDATE boosts SET status = 'expired' WHERE user_id = :uid AND status = 'active'",
            [':uid' => $userId]
        );

        Database::insert(
            "INSERT INTO boosts (user_id, starts_at, expires_at, status) VALUES (:uid, NOW(), :exp, 'active')",
            [':uid' => $userId, ':exp' => $expiresAt]
        );

        Database::insert(
            "INSERT INTO notifications (user_id, type, title, body, link) 
             VALUES (:uid, 'boost', 'Profile Boost Activated', 'Your profile is now boosted at the top of Discover for the next 24 hours!', '/discover')",
            [':uid' => $userId]
        );

        return true;
    }

    public static function expireOverdue(): int {
        $stmt = Database::connect()->prepare(
            "UPDATE boosts SET status = 'expired' WHERE status = 'active' AND expires_at <= NOW()"
        );
        $stmt->execute();
        return $stmt->rowCount();
    }
}
