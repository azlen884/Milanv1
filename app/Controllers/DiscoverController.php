<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Security;
use App\Helpers\View;

class DiscoverController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $userId = $user['id'];

        // Get filter inputs
        $filterGender = $_GET['gender'] ?? '';
        $filterCity = Security::sanitize($_GET['city'] ?? '');
        $filterAgeMin = isset($_GET['age_min']) ? max(18, (int)$_GET['age_min']) : 18;
        $filterAgeMax = isset($_GET['age_max']) ? min(70, (int)$_GET['age_max']) : 60;
        $filterKyc = !empty($_GET['kyc_verified']) ? 1 : 0;
        $filterRecent = !empty($_GET['recently_active']) ? 1 : 0;
        $filterInterest = Security::sanitize($_GET['interest'] ?? '');

        // Build query for REAL users only
        $where = [
            "u.id != :uid",
            "u.status = 'active'",
            "p.is_incognito = 0",
            "p.age >= :amin",
            "p.age <= :amax",
            "u.id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)",
            "u.id NOT IN (SELECT blocker_id FROM blocked_users WHERE blocked_id = :uid)",
        ];

        $params = [
            ':uid' => $userId,
            ':amin' => $filterAgeMin,
            ':amax' => $filterAgeMax,
        ];

        if ($filterGender && in_array($filterGender, ['male', 'female', 'other'], true)) {
            $where[] = "p.gender = :gender";
            $params[':gender'] = $filterGender;
        }

        if ($filterCity !== '') {
            $where[] = "p.city = :city";
            $params[':city'] = $filterCity;
        }

        if ($filterKyc) {
            $where[] = "p.kyc_status = 'verified'";
        }

        if ($filterRecent) {
            // Active in last 48 hours
            $where[] = "u.last_active_at >= DATE_SUB(NOW(), INTERVAL 48 HOUR)";
        }

        if ($filterInterest !== '') {
            $where[] = "u.id IN (
                SELECT ui.user_id FROM user_interests ui 
                JOIN interests i ON ui.interest_id = i.id 
                WHERE i.name = :interest
            )";
            $params[':interest'] = $filterInterest;
        }

        $whereClause = implode(' AND ', $where);

        // Sorting: Boosted profiles rank higher (Rule 22), then recently active
        $sql = "SELECT u.id, u.last_active_at, p.name, p.age, p.gender, p.city, p.occupation, p.primary_photo, p.kyc_status, p.bio, p.languages,
                       (SELECT id FROM boosts b WHERE b.user_id = u.id AND b.status = 'active' AND b.expires_at > NOW() LIMIT 1) as is_boosted,
                       (SELECT id FROM user_interactions ui WHERE ui.sender_id = :uid AND ui.receiver_id = u.id LIMIT 1) as has_sent_interest,
                       (SELECT m.id FROM matches m WHERE (m.user1_id = :uid AND m.user2_id = u.id) OR (m.user1_id = u.id AND m.user2_id = :uid) LIMIT 1) as match_id
                FROM users u
                JOIN user_profiles p ON u.id = p.user_id
                WHERE {$whereClause}
                ORDER BY is_boosted DESC, u.last_active_at DESC
                LIMIT 50";

        $profiles = Database::query($sql, $params);

        // Fetch filter options
        $cities = Database::query("SELECT DISTINCT city FROM user_profiles WHERE city IS NOT NULL AND city != '' ORDER BY city ASC");
        $interests = Database::query("SELECT id, name FROM interests ORDER BY name ASC");

        View::render('discover/index', [
            'profiles' => $profiles,
            'cities' => array_column($cities, 'city'),
            'interests' => $interests,
            'filters' => [
                'gender' => $filterGender,
                'city' => $filterCity,
                'age_min' => $filterAgeMin,
                'age_max' => $filterAgeMax,
                'kyc_verified' => $filterKyc,
                'recently_active' => $filterRecent,
                'interest' => $filterInterest,
            ],
        ], 'main');
    }
}
