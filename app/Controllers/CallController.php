<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Helpers\View;

class CallController {
    /**
     * Initiate a new WebRTC Video Call
     */
    public function start(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($csrf)) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $convId = (int)($_POST['conversation_id'] ?? 0);
        $sdpOffer = trim($_POST['sdp_offer'] ?? '');
        $candidates = trim($_POST['candidates'] ?? '[]');

        if ($convId <= 0 || empty($sdpOffer)) {
            View::json(['success' => false, 'error' => 'Invalid call request parameters.'], 400);
        }

        // Validate conversation & participant
        $conv = Database::one(
            "SELECT id, user1_id, user2_id FROM conversations WHERE id = :id",
            [':id' => $convId]
        );

        if (!$conv || ($conv['user1_id'] !== $user['id'] && $conv['user2_id'] !== $user['id'])) {
            View::json(['success' => false, 'error' => 'Invalid conversation.'], 403);
        }

        $receiverId = ($conv['user1_id'] === $user['id']) ? (int)$conv['user2_id'] : (int)$conv['user1_id'];

        // Check if blocked
        $blocked = Database::one(
            "SELECT id FROM blocked_users 
             WHERE (blocker_id = :a AND blocked_id = :b) OR (blocker_id = :b AND blocked_id = :a)",
            [':a' => $user['id'], ':b' => $receiverId]
        );
        if ($blocked) {
            View::json(['success' => false, 'error' => 'Cannot initiate call to this user.'], 403);
        }

        // Check if receiver is already in an ongoing active call
        $ongoing = Database::one(
            "SELECT id FROM video_calls 
             WHERE (caller_id = :rid OR receiver_id = :rid) 
               AND status IN ('calling', 'accepted') 
               AND updated_at >= DATE_SUB(NOW(), INTERVAL 30 SECOND)",
            [':rid' => $receiverId]
        );
        if ($ongoing) {
            View::json(['success' => false, 'busy' => true, 'error' => 'User is currently busy on another call.'], 200);
        }

        // Expire any previous unaccepted calls for this conversation
        Database::execute(
            "UPDATE video_calls SET status = 'missed', ended_at = NOW() 
             WHERE conversation_id = :cid AND status = 'calling'",
            [':cid' => $convId]
        );

        // Insert new call session
        $callId = Database::insert(
            "INSERT INTO video_calls (conversation_id, caller_id, receiver_id, status, sdp_offer, caller_candidates, created_at)
             VALUES (:cid, :cid_user, :rid_user, 'calling', :sdp, :cand, NOW())",
            [
                ':cid' => $convId,
                ':cid_user' => $user['id'],
                ':rid_user' => $receiverId,
                ':sdp' => $sdpOffer,
                ':cand' => $candidates,
            ]
        );

        // Update caller activity
        Auth::updateLastActive($user['id']);

        View::json([
            'success' => true,
            'call_id' => $callId,
            'status' => 'calling',
            'receiver_id' => $receiverId,
        ]);
    }

    /**
     * Check active call state or check for incoming call
     */
    public function check(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        // Update activity timestamp
        Auth::updateLastActive($user['id']);

        $callId = (int)($_GET['call_id'] ?? 0);
        $convId = (int)($_GET['conversation_id'] ?? 0);

        // 1. If tracking an active call
        if ($callId > 0) {
            $call = Database::one(
                "SELECT id, conversation_id, caller_id, receiver_id, status, sdp_offer, sdp_answer, caller_candidates, receiver_candidates, started_at, ended_at 
                 FROM video_calls 
                 WHERE id = :id AND (caller_id = :uid OR receiver_id = :uid)",
                [':id' => $callId, ':uid' => $user['id']]
            );

            if (!$call) {
                View::json(['success' => false, 'status' => 'ended']);
            }

            // Auto-timeout unanswered calls after 45 seconds
            if ($call['status'] === 'calling' && (time() - strtotime($call['created_at'] ?? 'now')) > 45) {
                Database::execute(
                    "UPDATE video_calls SET status = 'missed', ended_at = NOW() WHERE id = :id",
                    [':id' => $callId]
                );
                $call['status'] = 'missed';
            }

            View::json([
                'success' => true,
                'call' => $call,
                'status' => $call['status'],
                'is_caller' => ($call['caller_id'] === $user['id']),
            ]);
        }

        // 2. Check for incoming call
        $whereSql = "vc.receiver_id = :uid AND vc.status = 'calling' AND vc.created_at >= DATE_SUB(NOW(), INTERVAL 45 SECOND)";
        $params = [':uid' => $user['id']];
        if ($convId > 0) {
            $whereSql .= " AND vc.conversation_id = :cid";
            $params[':cid'] = $convId;
        }

        $incoming = Database::one(
            "SELECT vc.id, vc.conversation_id, vc.caller_id, vc.status, vc.sdp_offer, vc.caller_candidates, vc.created_at,
                    p.name as caller_name, p.primary_photo as caller_photo
             FROM video_calls vc
             JOIN user_profiles p ON vc.caller_id = p.user_id
             WHERE {$whereSql}
             ORDER BY vc.id DESC LIMIT 1",
            $params
        );

        if ($incoming) {
            View::json([
                'success' => true,
                'incoming' => true,
                'call' => $incoming,
            ]);
        }

        View::json([
            'success' => true,
            'incoming' => false,
        ]);
    }

    /**
     * Respond to incoming call (Accept or Reject)
     */
    public function respond(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $csrf = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($csrf)) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $callId = (int)($_POST['call_id'] ?? 0);
        $action = $_POST['action'] ?? ''; // 'accept' or 'reject'
        $sdpAnswer = trim($_POST['sdp_answer'] ?? '');
        $candidates = trim($_POST['candidates'] ?? '[]');

        if ($callId <= 0 || !in_array($action, ['accept', 'reject'], true)) {
            View::json(['success' => false, 'error' => 'Invalid parameters.'], 400);
        }

        $call = Database::one(
            "SELECT id, caller_id, receiver_id, status FROM video_calls WHERE id = :id AND receiver_id = :uid",
            [':id' => $callId, ':uid' => $user['id']]
        );

        if (!$call) {
            View::json(['success' => false, 'error' => 'Call not found or unauthorized.'], 404);
        }

        if ($action === 'accept') {
            Database::execute(
                "UPDATE video_calls 
                 SET status = 'accepted', sdp_answer = :ans, receiver_candidates = :cands, started_at = NOW() 
                 WHERE id = :id",
                [
                    ':id' => $callId,
                    ':ans' => $sdpAnswer,
                    ':cands' => $candidates,
                ]
            );
            View::json(['success' => true, 'status' => 'accepted']);
        } else {
            Database::execute(
                "UPDATE video_calls SET status = 'rejected', ended_at = NOW() WHERE id = :id",
                [':id' => $callId]
            );
            View::json(['success' => true, 'status' => 'rejected']);
        }
    }

    /**
     * Send ICE candidate (trickle ICE)
     */
    public function sendCandidate(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $callId = (int)($_POST['call_id'] ?? 0);
        $candidateJson = trim($_POST['candidate'] ?? '');

        if ($callId <= 0 || empty($candidateJson)) {
            View::json(['success' => false, 'error' => 'Invalid parameters.'], 400);
        }

        $call = Database::one(
            "SELECT id, caller_id, receiver_id, caller_candidates, receiver_candidates 
             FROM video_calls 
             WHERE id = :id AND (caller_id = :uid OR receiver_id = :uid)",
            [':id' => $callId, ':uid' => $user['id']]
        );

        if (!$call) {
            View::json(['success' => false, 'error' => 'Call not found.'], 404);
        }

        $candidateObj = json_decode($candidateJson, true);
        if (!$candidateObj) {
            View::json(['success' => false, 'error' => 'Invalid candidate JSON.'], 400);
        }

        if ($call['caller_id'] === $user['id']) {
            $existing = json_decode($call['caller_candidates'] ?? '[]', true) ?: [];
            $existing[] = $candidateObj;
            Database::execute(
                "UPDATE video_calls SET caller_candidates = :cands WHERE id = :id",
                [':id' => $callId, ':cands' => json_encode($existing)]
            );
        } else {
            $existing = json_decode($call['receiver_candidates'] ?? '[]', true) ?: [];
            $existing[] = $candidateObj;
            Database::execute(
                "UPDATE video_calls SET receiver_candidates = :cands WHERE id = :id",
                [':id' => $callId, ':cands' => json_encode($existing)]
            );
        }

        View::json(['success' => true]);
    }

    /**
     * End an active or ringing call
     */
    public function end(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $callId = (int)($_POST['call_id'] ?? 0);
        if ($callId <= 0) {
            View::json(['success' => false, 'error' => 'Invalid call ID.'], 400);
        }

        Database::execute(
            "UPDATE video_calls SET status = 'ended', ended_at = NOW() 
             WHERE id = :id AND (caller_id = :uid OR receiver_id = :uid)",
            [':id' => $callId, ':uid' => $user['id']]
        );

        View::json(['success' => true, 'status' => 'ended']);
    }

    /**
     * Global User Activity Heartbeat (Part 5: Online / Offline Presence)
     */
    public function heartbeat(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Not authenticated.'], 401);
        }

        Auth::updateLastActive($user['id']);
        View::json(['success' => true, 'online' => true]);
    }
}
