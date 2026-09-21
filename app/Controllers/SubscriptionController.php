<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Session;
use App\Helpers\View;
use App\Services\RazorpayService;
use App\Services\SubscriptionService;
use App\Services\BoostService;
use Exception;

class SubscriptionController {
    /**
     * Paid Upgrade Page:
     * MUST DISPLAY ONLY:
     * 1. Monthly ₹299
     * 2. 3 Months ₹700
     * DO NOT SHOW FREE ON THIS PAGE (Rule 11)
     */
    public function upgrade(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        // Fetch ONLY paid plans
        $plans = Database::query(
            "SELECT * FROM subscription_plans 
             WHERE is_active = 1 AND code != 'free' 
             ORDER BY price_inr ASC"
        );

        $currentSub = Auth::getSubscription($user['id']);
        $razorpay = new RazorpayService();

        View::render('subscription/upgrade', [
            'plans' => $plans,
            'currentSub' => $currentSub,
            'razorpayKeyId' => $razorpay->getKeyId(),
        ], 'main');
    }

    /**
     * My Subscription details in Profile/Account area
     * Here Free plan details ARE visible (Rule 11)
     */
    public function show(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $sub = Auth::getSubscription($user['id']);
        $payments = Database::query(
            "SELECT * FROM payments WHERE user_id = :uid ORDER BY id DESC LIMIT 10",
            [':uid' => $user['id']]
        );

        $dailyPerm = Auth::canSendMessage($user['id']);
        $boostInfo = BoostService::canUserBoost($user['id']);
        $activeBoost = Database::one(
            "SELECT * FROM boosts WHERE user_id = :uid AND status = 'active' AND expires_at > NOW() LIMIT 1",
            [':uid' => $user['id']]
        );

        View::render('subscription/show', [
            'subscription' => $sub,
            'payments' => $payments,
            'dailyPerm' => $dailyPerm,
            'boostInfo' => $boostInfo,
            'activeBoost' => $activeBoost,
        ], 'main');
    }

    /**
     * Create Razorpay Order endpoint (AJAX)
     */
    public function createOrder(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $type = $_POST['type'] ?? 'subscription';

        if ($type === 'boost') {
            $boostPrice = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'")['setting_value'] ?? 19);
            $amountPaisa = $boostPrice * 100;
            $receipt = 'rcpt_bst_' . $user['id'] . '_' . time();

            try {
                $rzp = new RazorpayService();
                $order = $rzp->createOrder($amountPaisa, $receipt, [
                    'user_id' => $user['id'],
                    'type' => 'boost',
                ]);

                // Store order in payments table
                Database::insert(
                    "INSERT INTO payments (user_id, payment_type, razorpay_order_id, amount_paisa, currency, status)
                     VALUES (:uid, 'boost', :oid, :amt, 'INR', 'created')",
                    [':uid' => $user['id'], ':oid' => $order['id'], ':amt' => $amountPaisa]
                );

                View::json([
                    'success' => true,
                    'order' => $order,
                    'key_id' => $rzp->getKeyId(),
                    'user' => [
                        'name' => $user['name'],
                        'email' => $user['email'],
                    ]
                ]);
            } catch (Exception $e) {
                View::json(['success' => false, 'error' => $e->getMessage()], 500);
            }
            return;
        }

        // Subscription Plan order
        $plan = Database::one(
            "SELECT * FROM subscription_plans WHERE id = :id AND is_active = 1 AND code != 'free'",
            [':id' => $planId]
        );

        if (!$plan) {
            View::json(['success' => false, 'error' => 'Invalid subscription plan.'], 400);
        }

        $amountPaisa = (int)$plan['price_inr'] * 100;
        $receipt = 'rcpt_sub_' . $user['id'] . '_' . $plan['id'] . '_' . time();

        try {
            $rzp = new RazorpayService();
            $order = $rzp->createOrder($amountPaisa, $receipt, [
                'user_id' => $user['id'],
                'plan_id' => $plan['id'],
                'type' => 'subscription',
            ]);

            // Save order in database
            Database::insert(
                "INSERT INTO payments (user_id, plan_id, payment_type, razorpay_order_id, amount_paisa, currency, status)
                 VALUES (:uid, :pid, 'subscription', :oid, :amt, 'INR', 'created')",
                [
                    ':uid' => $user['id'],
                    ':pid' => $plan['id'],
                    ':oid' => $order['id'],
                    ':amt' => $amountPaisa,
                ]
            );

            View::json([
                'success' => true,
                'order' => $order,
                'plan' => $plan,
                'key_id' => $rzp->getKeyId(),
                'user' => [
                    'name' => $user['name'],
                    'email' => $user['email'],
                ]
            ]);
        } catch (Exception $e) {
            View::json(['success' => false, 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Verify Razorpay Payment endpoint (AJAX)
     * Verifies cryptographic signature before activating subscription!
     */
    public function verifyPayment(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $orderId = trim($_POST['razorpay_order_id'] ?? '');
        $paymentId = trim($_POST['razorpay_payment_id'] ?? '');
        $signature = trim($_POST['razorpay_signature'] ?? '');
        $planIdInput = (int)($_POST['plan_id'] ?? 0);
        $typeInput = trim($_POST['type'] ?? '');

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            View::json(['success' => false, 'error' => 'Missing payment parameters.'], 400);
        }

        // 1. Fetch the original order record from the database
        $orderRecord = Database::one(
            "SELECT * FROM payments WHERE razorpay_order_id = :oid LIMIT 1",
            [':oid' => $orderId]
        );

        if (!$orderRecord) {
            View::json(['success' => false, 'error' => 'Payment order record was not found.'], 404);
        }

        // 2. Verify order belongs to the logged-in user
        if ((int)$orderRecord['user_id'] !== (int)$user['id']) {
            error_log("[Security] Order user mismatch: Order belongs to user {$orderRecord['user_id']}, but current session is user {$user['id']}");
            View::json(['success' => false, 'error' => 'Unauthorized order ownership.'], 403);
        }

        // 3. Determine and verify payment type
        $type = $orderRecord['payment_type'] ?: ($typeInput ?: 'subscription');
        if ($typeInput && $orderRecord['payment_type'] && $typeInput !== $orderRecord['payment_type']) {
            View::json(['success' => false, 'error' => 'Payment type mismatch.'], 400);
        }

        // 4. Verify Plan / Amount
        $planId = (int)($orderRecord['plan_id'] ?: $planIdInput);
        $expectedAmountPaisa = 0;

        if ($type === 'subscription') {
            $plan = Database::one(
                "SELECT id, price_inr FROM subscription_plans WHERE id = :id AND is_active = 1 AND code != 'free'",
                [':id' => $planId]
            );
            if (!$plan) {
                View::json(['success' => false, 'error' => 'Invalid or inactive subscription plan.'], 400);
            }
            $expectedAmountPaisa = ((int)$plan['price_inr']) * 100;
        } elseif ($type === 'boost') {
            $boostPrice = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'")['setting_value'] ?? 19);
            $expectedAmountPaisa = $boostPrice * 100;
        }

        if ((int)$orderRecord['amount_paisa'] !== $expectedAmountPaisa) {
            error_log("[Security] Amount mismatch: DB order has {$orderRecord['amount_paisa']}, expected {$expectedAmountPaisa}");
            View::json(['success' => false, 'error' => 'Payment amount discrepancy detected.'], 400);
        }

        // 5. Idempotent check: if already captured, return success immediately
        if ($orderRecord['status'] === 'captured' && $orderRecord['razorpay_payment_id'] === $paymentId) {
            Auth::clearCache();
            Session::flash('success', 'Payment confirmed! Your benefits are active.');
            View::json([
                'success' => true,
                'message' => 'Payment already verified successfully.',
                'redirect' => ($type === 'boost') ? '/discover' : '/subscription',
            ]);
        }

        // 6. Verify Payment ID is not reused on another order
        $reuseCheck = Database::one(
            "SELECT id, razorpay_order_id FROM payments WHERE razorpay_payment_id = :pid AND razorpay_order_id != :oid LIMIT 1",
            [':pid' => $paymentId, ':oid' => $orderId]
        );
        if ($reuseCheck) {
            error_log("[Security] Duplicate payment ID {$paymentId} attempted for order {$orderId}");
            View::json(['success' => false, 'error' => 'This payment ID has already been processed for another transaction.'], 400);
        }

        // 7. Verify HMAC-SHA256 signature
        $rzp = new RazorpayService();
        $isValid = $rzp->verifyPaymentSignature($orderId, $paymentId, $signature);

        if (!$isValid) {
            Database::execute(
                "UPDATE payments SET status = 'failed', razorpay_payment_id = :pid WHERE razorpay_order_id = :oid",
                [':pid' => $paymentId, ':oid' => $orderId]
            );
            View::json(['success' => false, 'error' => 'Payment verification signature failed.'], 400);
        }

        // 8. Optional/Direct gateway verification via Razorpay REST API
        $apiPayment = $rzp->fetchPayment($paymentId);
        if ($apiPayment) {
            $apiStatus = $apiPayment['status'] ?? '';
            $apiOrder = $apiPayment['order_id'] ?? '';
            $apiAmount = (int)($apiPayment['amount'] ?? 0);

            if (!in_array($apiStatus, ['captured', 'authorized'], true)) {
                Database::execute(
                    "UPDATE payments SET status = 'failed', razorpay_payment_id = :pid WHERE razorpay_order_id = :oid",
                    [':pid' => $paymentId, ':oid' => $orderId]
                );
                View::json(['success' => false, 'error' => "Payment status is {$apiStatus}, not captured."], 400);
            }

            if (!empty($apiOrder) && $apiOrder !== $orderId) {
                View::json(['success' => false, 'error' => 'Payment gateway order mismatch.'], 400);
            }

            if ($apiAmount > 0 && $apiAmount !== $expectedAmountPaisa) {
                View::json(['success' => false, 'error' => 'Payment gateway amount mismatch.'], 400);
            }
        }

        // 9. Process payment and activate benefits atomically
        $processed = $rzp->processVerifiedPayment(
            $user['id'],
            $planId,
            $orderId,
            $paymentId,
            $signature,
            $expectedAmountPaisa,
            $type
        );

        if ($processed) {
            Auth::clearCache();
            $redirectUrl = ($type === 'boost') ? '/discover' : '/subscription';
            $msg = ($type === 'boost') 
                ? 'Payment successful! Your profile Boost is now active for 24 hours.' 
                : 'Payment verified successfully! Welcome to Milan Premium.';
            Session::flash('success', $msg);
            View::json([
                'success' => true,
                'message' => $msg,
                'redirect' => $redirectUrl,
            ]);
        } else {
            View::json(['success' => false, 'error' => 'Payment was received but activating your perks encountered an error. Please contact support.'], 500);
        }
    }

    /**
     * Razorpay Webhook Handler
     */
    public function webhook(): void {
        $payload = file_get_contents('php://input');
        $signature = $_SERVER['HTTP_X_RAZORPAY_SIGNATURE'] ?? '';

        if (empty($payload)) {
            View::json(['status' => 'empty_payload'], 400);
        }

        $rzp = new RazorpayService();
        if (!empty($signature)) {
            if (!$rzp->verifyWebhookSignature($payload, $signature)) {
                View::json(['error' => 'Invalid webhook signature'], 400);
            }
        }

        $data = json_decode($payload, true);
        $event = $data['event'] ?? '';

        if ($event === 'payment.captured' || $event === 'order.paid') {
            $paymentEntity = $data['payload']['payment']['entity'] ?? [];
            $paymentId = $paymentEntity['id'] ?? '';
            $orderId = $paymentEntity['order_id'] ?? '';
            $amount = (int)($paymentEntity['amount'] ?? 0);

            if ($orderId && $paymentId) {
                $orderRecord = Database::one(
                    "SELECT * FROM payments WHERE razorpay_order_id = :oid LIMIT 1",
                    [':oid' => $orderId]
                );

                if ($orderRecord && $orderRecord['status'] !== 'captured') {
                    $userId = (int)$orderRecord['user_id'];
                    $planId = (int)($orderRecord['plan_id'] ?? 0);
                    $type = $orderRecord['payment_type'] ?? 'subscription';

                    $rzp->processVerifiedPayment(
                        $userId,
                        $planId,
                        $orderId,
                        $paymentId,
                        'webhook_verified',
                        $amount,
                        $type
                    );
                }
            }
        }

        View::json(['status' => 'success']);
    }

    /**
     * Activate Boost (for 3-month subscriber where included or standalone)
     */
    public function activateBoost(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid session token.'], 403);
        }

        $canBoost = BoostService::canUserBoost($user['id']);
        if (!$canBoost['allowed']) {
            View::json(['success' => false, 'error' => $canBoost['reason']], 400);
        }

        if ($canBoost['is_included']) {
            BoostService::activatePaidBoost($user['id']);
            Session::flash('success', 'Boost activated! Your profile is now featured at the top of Discover for 24 hours.');
            View::json(['success' => true, 'message' => 'Boost activated!']);
        } else {
            View::json(['success' => false, 'error' => 'Payment required for Boost.'], 402);
        }
    }
}
