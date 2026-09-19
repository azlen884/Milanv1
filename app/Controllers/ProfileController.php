<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\View;

class ProfileController {
    public function show(): void {
        $currentUser = Auth::user();
        if (!$currentUser) {
            header('Location: /login');
            exit;
        }

        $targetId = isset($_GET['id']) ? (int)$_GET['id'] : $currentUser['id'];
        $isOwnProfile = ($targetId === $currentUser['id']);

        // Check if user exists
        $user = Database::one(
            "SELECT u.id, u.last_active_at, u.created_at,
                    p.name, p.dob, p.age, p.gender, p.city, p.bio, p.occupation, p.education,
                    p.height_cm, p.primary_photo, p.languages, p.kyc_status, p.opt_out_visitors, p.is_incognito
             FROM users u
             JOIN user_profiles p ON u.id = p.user_id
             WHERE u.id = :id AND u.status = 'active' LIMIT 1",
            [':id' => $targetId]
        );

        if (!$user) {
            Session::flash('error', 'Profile not found or is no longer active.');
            header('Location: /discover');
            exit;
        }

        // Check if blocked
        if (!$isOwnProfile) {
            $isBlocked = Database::one(
                "SELECT id FROM blocked_users 
                 WHERE (blocker_id = :cur AND blocked_id = :tar) OR (blocker_id = :tar AND blocked_id = :cur)",
                [':cur' => $currentUser['id'], ':tar' => $targetId]
            );
            if ($isBlocked) {
                Session::flash('error', 'This profile is unavailable.');
                header('Location: /discover');
                exit;
            }

            // RECORD PROFILE VISITOR (Rule 21)
            // If visitor has not opted out, and visited profile has not opted out:
            if (empty($currentUser['opt_out_visitors'])) {
                Database::execute(
                    "INSERT INTO profile_visitors (profile_user_id, visitor_user_id, visited_at)
                     VALUES (:tar, :cur, NOW())
                     ON DUPLICATE KEY UPDATE visited_at = NOW(), updated_at = NOW()",
                    [':tar' => $targetId, ':cur' => $currentUser['id']]
                );

                // Notify if target has paid subscription
                $targetSub = Auth::getSubscription($targetId);
                if ($targetSub['can_view_visitors']) {
                    Database::insert(
                        "INSERT INTO notifications (user_id, type, title, body, link) 
                         VALUES (:uid, 'visitor', 'New Profile Visitor', :body, '/visitors')",
                        [
                            ':uid' => $targetId,
                            ':body' => "{$currentUser['name']} viewed your profile.",
                        ]
                    );
                }
            }
        }

        // Additional user photos
        $photos = Database::query(
            "SELECT id, photo_url, is_primary FROM user_photos WHERE user_id = :id ORDER BY is_primary DESC, id ASC",
            [':id' => $targetId]
        );

        // Interests
        $userInterests = Database::query(
            "SELECT i.name, i.category FROM user_interests ui 
             JOIN interests i ON ui.interest_id = i.id 
             WHERE ui.user_id = :id",
            [':id' => $targetId]
        );

        // Status relationships with target
        $hasSentInterest = false;
        $isMatched = false;
        $conversationId = null;

        if (!$isOwnProfile) {
            $interaction = Database::one(
                "SELECT id, status FROM user_interactions WHERE sender_id = :cur AND receiver_id = :tar",
                [':cur' => $currentUser['id'], ':tar' => $targetId]
            );
            $hasSentInterest = ($interaction !== null);

            $match = Database::one(
                "SELECT id FROM matches 
                 WHERE (user1_id = :cur AND user2_id = :tar) OR (user1_id = :tar AND user2_id = :cur)",
                [':cur' => $currentUser['id'], ':tar' => $targetId]
            );
            $isMatched = ($match !== null);

            $conv = Database::one(
                "SELECT id FROM conversations 
                 WHERE (user1_id = :cur AND user2_id = :tar) OR (user1_id = :tar AND user2_id = :cur)",
                [':cur' => $currentUser['id'], ':tar' => $targetId]
            );
            $conversationId = $conv['id'] ?? null;
        }

        View::render('profile/show', [
            'profile' => $user,
            'photos' => $photos,
            'interests' => $userInterests,
            'isOwnProfile' => $isOwnProfile,
            'hasSentInterest' => $hasSentInterest,
            'isMatched' => $isMatched,
            'conversationId' => $conversationId,
        ], 'main');
    }

    public function block(): void {
        $currentUser = Auth::user();
        if (!$currentUser) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid token.'], 403);
        }

        $targetId = (int)($_POST['target_id'] ?? 0);
        if ($targetId <= 0 || $targetId === $currentUser['id']) {
            View::json(['success' => false, 'error' => 'Invalid user ID.'], 400);
        }

        Database::execute(
            "INSERT IGNORE INTO blocked_users (blocker_id, blocked_id, created_at) VALUES (:b, :d, NOW())",
            [':b' => $currentUser['id'], ':d' => $targetId]
        );

        View::json(['success' => true, 'message' => 'User has been blocked.']);
    }

    public function report(): void {
        $currentUser = Auth::user();
        if (!$currentUser) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid token.'], 403);
        }

        $targetId = (int)($_POST['target_id'] ?? 0);
        $reason = Security::sanitize($_POST['reason'] ?? 'other');
        $description = Security::sanitize($_POST['description'] ?? '');

        $validReasons = ['fake_profile', 'harassment', 'spam', 'scam', 'inappropriate_content', 'impersonation', 'other'];
        if (!in_array($reason, $validReasons, true)) {
            $reason = 'other';
        }

        Database::insert(
            "INSERT INTO reports (reporter_id, reported_id, reason, description, status, created_at)
             VALUES (:rep, :tar, :reason, :desc, 'open', NOW())",
            [
                ':rep' => $currentUser['id'],
                ':tar' => $targetId,
                ':reason' => $reason,
                ':desc' => $description,
            ]
        );

        View::json(['success' => true, 'message' => 'Report submitted. Our safety team will review it.']);
    }
}
