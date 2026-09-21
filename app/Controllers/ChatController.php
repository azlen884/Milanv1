<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\View;
use App\Services\ChatService;
use App\Services\BotService;

class ChatController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $userId = $user['id'];

        // Process any due admin bot messages for this user (if free plan)
        BotService::processForUser($userId);

        $activeConvId = isset($_GET['id']) ? (int)$_GET['id'] : null;

        // Fetch all conversations for user
        $sql = "SELECT c.id as conversation_id, c.last_message_at,
                       CASE WHEN c.user1_id = :uid THEN c.user2_id ELSE c.user1_id END as partner_id,
                       p.name as partner_name, p.primary_photo as partner_photo, p.kyc_status,
                       u.last_active_at,
                       (SELECT body FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) as last_message_body,
                       (SELECT type FROM messages m WHERE m.conversation_id = c.id ORDER BY m.id DESC LIMIT 1) as last_message_type,
                       (SELECT COUNT(*) FROM messages m WHERE m.conversation_id = c.id AND m.receiver_id = :uid AND m.is_read = 0) as unread_count
                FROM conversations c
                JOIN users u ON u.id = (CASE WHEN c.user1_id = :uid THEN c.user2_id ELSE c.user1_id END)
                JOIN user_profiles p ON p.user_id = u.id
                WHERE (c.user1_id = :uid OR c.user2_id = :uid)
                  AND u.status = 'active'
                  AND u.id NOT IN (SELECT blocked_id FROM blocked_users WHERE blocker_id = :uid)
                ORDER BY c.last_message_at DESC";

        $conversations = Database::query($sql, [':uid' => $userId]);

        // If a specific conversation is requested or we default to the first one on desktop
        $activeConversation = null;
        $messages = [];
        $activePartner = null;

        if ($activeConvId) {
            $conv = Database::one(
                "SELECT * FROM conversations WHERE id = :id AND (user1_id = :uid OR user2_id = :uid)",
                [':id' => $activeConvId, ':uid' => $userId]
            );
            if ($conv) {
                $activeConversation = $conv;
                $partnerId = ($conv['user1_id'] === $userId) ? (int)$conv['user2_id'] : (int)$conv['user1_id'];
                $activePartner = Database::one(
                    "SELECT u.id, u.last_active_at, p.name, p.age, p.city, p.primary_photo, p.kyc_status 
                     FROM users u JOIN user_profiles p ON u.id = p.user_id WHERE u.id = :pid",
                    [':pid' => $partnerId]
                );

                // Mark messages as read
                ChatService::markAsRead($activeConvId, $userId);

                // Fetch recent messages
                $messages = Database::query(
                    "SELECT * FROM messages WHERE conversation_id = :cid ORDER BY id ASC LIMIT 100",
                    [':cid' => $activeConvId]
                );
            }
        }

        // Daily limit status
        $msgPermission = Auth::canSendMessage($userId);

        View::render('chat/index', [
            'conversations' => $conversations,
            'activeConversation' => $activeConversation,
            'activePartner' => $activePartner,
            'messages' => $messages,
            'msgPermission' => $msgPermission,
        ], 'main');
    }

    public function start(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $partnerId = (int)($_GET['with'] ?? 0);
        if ($partnerId <= 0 || $partnerId === $user['id']) {
            header('Location: /messages');
            exit;
        }

        $conv = ChatService::getOrCreateConversation($user['id'], $partnerId);
        if ($conv) {
            header("Location: /messages?id={$conv['id']}");
        } else {
            Session::flash('error', 'Unable to start conversation with this profile.');
            header('Location: /discover');
        }
        exit;
    }

    public function sendText(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $convId = (int)($_POST['conversation_id'] ?? 0);
        $body = trim($_POST['body'] ?? '');

        $result = ChatService::sendTextMessage($user['id'], $convId, $body);
        View::json($result, $result['success'] ? 200 : 403);
    }

    public function poll(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['messages' => []], 401);
        }

        // Release session lock early so poll does not block other requests / page navigation
        Session::closeWrite();

        $convId = (int)($_GET['id'] ?? 0);
        $afterId = (int)($_GET['after'] ?? 0);

        if ($convId <= 0) {
            View::json(['messages' => []]);
        }

        // Process bot messages if due
        BotService::processForUser($user['id']);

        // Verify participant
        $conv = Database::one(
            "SELECT id FROM conversations WHERE id = :id AND (user1_id = :uid OR user2_id = :uid)",
            [':id' => $convId, ':uid' => $user['id']]
        );
        if (!$conv) {
            View::json(['messages' => []]);
        }

        $newMessages = Database::query(
            "SELECT * FROM messages WHERE conversation_id = :cid AND id > :after ORDER BY id ASC",
            [':cid' => $convId, ':after' => $afterId]
        );

        // Mark as read
        ChatService::markAsRead($convId, $user['id']);
        Auth::updateLastActive($user['id']);

        // Check if partner is currently typing (real-time typing indicator)
        $typing = Database::one(
            "SELECT user_id FROM conversation_typing 
             WHERE conversation_id = :cid AND user_id != :uid AND updated_at >= DATE_SUB(NOW(), INTERVAL 4 SECOND)",
            [':cid' => $convId, ':uid' => $user['id']]
        );

        // Check if partner is online (real-time presence)
        $partnerId = ($conv['user1_id'] === $user['id']) ? (int)$conv['user2_id'] : (int)$conv['user1_id'];
        $partner = Database::one("SELECT last_active_at FROM users WHERE id = :pid", [':pid' => $partnerId]);
        $partnerOnline = Auth::isOnline($partner['last_active_at'] ?? null);

        View::json([
            'messages' => $newMessages,
            'perm' => Auth::canSendMessage($user['id']),
            'is_typing' => !empty($typing),
            'partner_online' => $partnerOnline,
        ]);
    }

    /**
     * Real-time typing status ping
     */
    public function typing(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $convId = (int)($_POST['conversation_id'] ?? 0);
        $isTyping = (int)($_POST['is_typing'] ?? 1);

        if ($convId <= 0) {
            View::json(['success' => false, 'error' => 'Invalid conversation.'], 400);
        }

        if ($isTyping) {
            Database::execute(
                "INSERT INTO conversation_typing (conversation_id, user_id, updated_at) 
                 VALUES (:cid, :uid, NOW()) 
                 ON DUPLICATE KEY UPDATE updated_at = NOW()",
                [':cid' => $convId, ':uid' => $user['id']]
            );
        } else {
            Database::execute(
                "DELETE FROM conversation_typing WHERE conversation_id = :cid AND user_id = :uid",
                [':cid' => $convId, ':uid' => $user['id']]
            );
        }

        Auth::updateLastActive($user['id']);
        View::json(['success' => true]);
    }
}
