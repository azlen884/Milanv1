<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\View;

class VisitorsController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $userId = $user['id'];
        $sub = Auth::getSubscription($userId);
        $isPaid = !empty($sub['can_view_visitors']);
        $hasOptedOut = !empty($user['opt_out_visitors']);

        $visitors = [];

        // ENFORCE SERVER-SIDE RULES (Rule 21)
        // 1. Paid-only
        // 2. If user opted out, they cannot view their own visitor list
        if ($isPaid && !$hasOptedOut) {
            $sql = "SELECT pv.visited_at,
                           p.user_id, p.name, p.age, p.city, p.occupation, p.primary_photo, p.kyc_status
                    FROM profile_visitors pv
                    JOIN users u ON u.id = pv.visitor_user_id
                    JOIN user_profiles p ON p.user_id = u.id
                    WHERE pv.profile_user_id = :uid
                      AND u.status = 'active'
                      AND p.opt_out_visitors = 0
                      AND pv.visitor_user_id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)
                    ORDER BY pv.visited_at DESC 
                    LIMIT 50";

            $visitors = Database::query($sql, [':uid' => $userId]);
        }

        View::render('visitors/index', [
            'isPaid' => $isPaid,
            'hasOptedOut' => $hasOptedOut,
            'visitors' => $visitors,
            'planName' => $sub['name'],
        ], 'main');
    }
}
