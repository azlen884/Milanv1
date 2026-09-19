<?php

namespace App\Controllers;

use App\Helpers\Auth;
use App\Helpers\Database;
use App\Helpers\Session;
use App\Helpers\View;
use App\Services\MatchingService;

class InterestController {
    public function send(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Please sign in to express interest.'], 401);
        }

        // Support both FormData/URL-encoded and JSON body
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $targetId = (int)($_POST['target_id'] ?? 0);

        if ($targetId <= 0 || empty($csrfToken)) {
            $rawInput = file_get_contents('php://input');
            if (!empty($rawInput)) {
                $json = json_decode($rawInput, true);
                if (is_array($json)) {
                    if ($targetId <= 0) {
                        $targetId = (int)($json['target_id'] ?? 0);
                    }
                    if (empty($csrfToken) && !empty($json['csrf_token'])) {
                        $csrfToken = (string)$json['csrf_token'];
                    }
                }
            }
        }

        if (!Session::verifyCsrf($csrfToken)) {
            View::json(['success' => false, 'error' => 'Security token expired. Please refresh the page.'], 403);
        }

        if ($targetId <= 0) {
            View::json(['success' => false, 'error' => 'Target member not specified.'], 400);
        }

        if ($targetId === (int)$user['id']) {
            View::json(['success' => false, 'error' => 'You cannot send interest to yourself.'], 400);
        }

        // Verify target user exists in database
        $targetUser = Database::one(
            "SELECT id, status FROM users WHERE id = :id LIMIT 1",
            [':id' => $targetId]
        );

        if (!$targetUser) {
            View::json(['success' => false, 'error' => 'Target profile could not be found.'], 404);
        }

        if ($targetUser['status'] === 'banned') {
            View::json(['success' => false, 'error' => 'This profile is currently unavailable.'], 400);
        }

        $result = MatchingService::sendInterest((int)$user['id'], $targetId);

        if (!$result['success'] && empty($result['error'])) {
            $result['error'] = $result['message'] ?? 'Unable to send interest.';
        }

        View::json($result, $result['success'] ? 200 : 400);
    }
}

