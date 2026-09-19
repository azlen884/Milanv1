<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\View;

class HomeController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $userId = $user['id'];
        $pref = Database::one("SELECT * FROM dating_preferences WHERE user_id = :uid", [':uid' => $userId]);
        $interestedGender = $pref['interested_in_gender'] ?? 'all';
        $ageMin = (int)($pref['age_min'] ?? 18);
        $ageMax = (int)($pref['age_max'] ?? 55);

        // Fetch real recommended profiles (matching gender preferences, not blocked, not self)
        $genderClause = "";
        $params = [':uid' => $userId, ':amin' => $ageMin, ':amax' => $ageMax];
        if ($interestedGender !== 'all') {
            $genderClause = "AND p.gender = :gender";
            $params[':gender'] = $interestedGender;
        }

        $suggestedSql = "SELECT u.id, u.last_active_at, p.name, p.age, p.gender, p.city, p.occupation, p.primary_photo, p.kyc_status, p.bio,
                                (SELECT id FROM boosts b WHERE b.user_id = u.id AND b.status = 'active' AND b.expires_at > NOW() LIMIT 1) as is_boosted,
                                (SELECT id FROM user_interactions ui WHERE ui.sender_id = :uid AND ui.receiver_id = u.id LIMIT 1) as has_sent_interest
                         FROM users u
                         JOIN user_profiles p ON u.id = p.user_id
                         WHERE u.id != :uid 
                           AND u.status = 'active'
                           AND p.is_incognito = 0
                           AND p.age BETWEEN :amin AND :amax
                           {$genderClause}
                           AND u.id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)
                           AND u.id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = :uid)
                         ORDER BY is_boosted DESC, u.last_active_at DESC 
                         LIMIT 8";

        $suggestedProfiles = Database::query($suggestedSql, $params);

        // Fetch real new members
        $newMembers = Database::query(
            "SELECT u.id, u.created_at, p.name, p.age, p.city, p.primary_photo, p.kyc_status
             FROM users u
             JOIN user_profiles p ON u.id = p.user_id
             WHERE u.id != :uid 
               AND u.status = 'active'
               AND p.is_incognito = 0
               AND u.id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)
             ORDER BY u.id DESC 
             LIMIT 6",
            [':uid' => $userId]
        );

        // Real active counts
        $matchesCount = Database::one(
            "SELECT COUNT(*) as cnt FROM matches WHERE user1_id = :uid OR user2_id = :uid",
            [':uid' => $userId]
        )['cnt'] ?? 0;

        $interestsReceivedCount = Database::one(
            "SELECT COUNT(*) as cnt FROM user_interactions WHERE receiver_id = :uid AND status = 'pending'",
            [':uid' => $userId]
        )['cnt'] ?? 0;

        View::render('home/index', [
            'user' => $user,
            'suggestedProfiles' => $suggestedProfiles,
            'newMembers' => $newMembers,
            'matchesCount' => (int)$matchesCount,
            'interestsReceivedCount' => (int)$interestsReceivedCount,
        ], 'main');
    }
}
