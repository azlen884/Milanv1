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
        $planId = (int)($_POST['plan_id'] ?? 0);
        $type = $_POST['type'] ?? 'subscription';

        if (empty($orderId) || empty($paymentId) || empty($signature)) {
            View::json(['success' => false, 'error' => 'Missing payment parameters.'], 400);
        }

        $rzp = new RazorpayService();
        $isValid = $rzp->verifyPaymentSignature($orderId, $paymentId, $signature);

        if (!$isValid) {
            // Mark payment as failed in records
            Database::execute(
                "UPDATE payments SET status = 'failed', razorpay_payment_id = :pid WHERE razorpay_order_id = :oid",
                [':pid' => $paymentId, ':oid' => $orderId]
            );
            View::json(['success' => false, 'error' => 'Payment verification signature failed.'], 400);
        }

        // Amount calculation
        $amountPaisa = 0;
        if ($type === 'subscription') {
            $plan = Database::one("SELECT price_inr FROM subscription_plans WHERE id = :id", [':id' => $planId]);
            $amountPaisa = ((int)($plan['price_inr'] ?? 0)) * 100;
        } else {
            $boostPrice = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'")['setting_value'] ?? 19);
            $amountPaisa = $boostPrice * 100;
        }

        $processed = $rzp->processVerifiedPayment(
            $user['id'],
            $planId,
            $orderId,
            $paymentId,
            $signature,
            $amountPaisa,
            $type
        );

        if ($processed) {
            Session::flash('success', 'Payment successful! Your premium perks are now active.');
            View::json([
                'success' => true,
                'message' => 'Payment verified and plan activated!',
                'redirect' => '/subscription',
            ]);
        } else {
            View::json(['success' => false, 'error' => 'Failed to activate plan.'], 500);
        }
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
