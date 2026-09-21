<?php

namespace App\Services;

use App\Helpers\Database;
use Exception;

class RazorpayService {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private bool $enabled;

    public function __construct() {
        // Load dynamically from database settings (Admin configured)
        $settingsRaw = Database::query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'razorpay_%'");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $this->enabled = ($settings['razorpay_enabled'] ?? '1') === '1';
        $this->keyId = trim($settings['razorpay_key_id'] ?? ($config['razorpay']['key_id'] ?? ''));
        $this->keySecret = trim($settings['razorpay_key_secret'] ?? ($config['razorpay']['key_secret'] ?? ''));
        $this->webhookSecret = trim($settings['razorpay_webhook_secret'] ?? ($config['razorpay']['webhook_secret'] ?? ''));
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function getKeyId(): string {
        return $this->keyId;
    }

    /**
     * Create Razorpay Order via REST API
     */
    public function createOrder(int $amountPaisa, string $receipt, array $notes = []): array {
        if (!$this->enabled) {
            throw new Exception("Razorpay payment gateway is currently disabled by administrator. Please contact support.");
        }

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new Exception("Razorpay payment credentials are not configured in Admin Settings. Please configure API keys.");
        }

        $url = 'https://api.razorpay.com/v1/orders';
        $payload = [
            'amount' => $amountPaisa,
            'currency' => 'INR',
            'receipt' => $receipt,
            'notes' => $notes,
            'payment_capture' => 1,
        ];

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: MilanDating-Production/1.0',
            ],
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            throw new Exception("Razorpay connection error: {$curlError}");
        }

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($data['id'])) {
            return $data;
        }

        $errorMsg = $data['error']['description'] ?? ($data['error']['code'] ?? "Failed to create Razorpay order (HTTP {$httpCode}).");
        throw new Exception($errorMsg);
    }

    /**
     * Verify Payment Signature (SHA256 HMAC)
     */
    public function verifyPaymentSignature(string $orderId, string $paymentId, string $signature): bool {
        if (empty($this->keySecret) || empty($orderId) || empty($paymentId) || empty($signature)) {
            return false;
        }

        $expectedSignature = hash_hmac('sha256', $orderId . '|' . $paymentId, $this->keySecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Verify Webhook Signature
     */
    public function verifyWebhookSignature(string $payload, string $signature): bool {
        if (empty($this->webhookSecret) || empty($signature)) {
            return false;
        }
        $expectedSignature = hash_hmac('sha256', $payload, $this->webhookSecret);
        return hash_equals($expectedSignature, $signature);
    }

    /**
     * Fetch payment details directly from Razorpay API
     */
    public function fetchPayment(string $paymentId): ?array {
        if (empty($this->keyId) || empty($this->keySecret) || empty($paymentId)) {
            return null;
        }

        $url = 'https://api.razorpay.com/v1/payments/' . urlencode($paymentId);
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: MilanDating-Production/1.0',
            ],
            CURLOPT_TIMEOUT => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
        }

        return null;
    }

    /**
     * Record payment and activate subscription with duplicate prevention and transactions
     */
    public function processVerifiedPayment(
        int $userId,
        int $planId,
        string $orderId,
        string $paymentId,
        string $signature,
        int $amountPaisa,
        string $paymentType = 'subscription'
    ): bool {
        // 1. Prevent reuse of the same payment ID across another order or another user
        $reuseCheck = Database::one(
            "SELECT id, user_id, razorpay_order_id, status FROM payments 
             WHERE razorpay_payment_id = :pay_id AND razorpay_order_id != :oid LIMIT 1",
            [':pay_id' => $paymentId, ':oid' => $orderId]
        );
        if ($reuseCheck) {
            error_log("[Security] Payment ID {$paymentId} already used for order {$reuseCheck['razorpay_order_id']}");
            return false;
        }

        // 2. Check for duplicate processing of the same order
        $existing = Database::one(
            "SELECT id, status FROM payments WHERE razorpay_payment_id = :pay_id AND razorpay_order_id = :oid LIMIT 1",
            [':pay_id' => $paymentId, ':oid' => $orderId]
        );

        if ($existing && $existing['status'] === 'captured') {
            return true; // Already processed safely
        }

        // 3. Database transaction to record payment and activate benefit atomically
        Database::beginTransaction();
        try {
            // Update or insert payment record
            $existingOrder = Database::one(
                "SELECT id FROM payments WHERE razorpay_order_id = :oid AND user_id = :uid LIMIT 1",
                [':oid' => $orderId, ':uid' => $userId]
            );

            if ($existingOrder) {
                Database::execute(
                    "UPDATE payments 
                     SET plan_id = :pid, payment_type = :ptype, razorpay_payment_id = :pid_str, 
                         razorpay_signature = :sig, amount_paisa = :amount, status = 'captured', updated_at = NOW()
                     WHERE id = :id",
                    [
                        ':pid' => $planId ?: null,
                        ':ptype' => $paymentType,
                        ':pid_str' => $paymentId,
                        ':sig' => $signature,
                        ':amount' => $amountPaisa,
                        ':id' => $existingOrder['id'],
                    ]
                );
            } else {
                Database::execute(
                    "INSERT INTO payments (user_id, plan_id, payment_type, razorpay_order_id, razorpay_payment_id, razorpay_signature, amount_paisa, currency, status, created_at, updated_at)
                     VALUES (:uid, :pid, :ptype, :oid, :pid_str, :sig, :amount, 'INR', 'captured', NOW(), NOW())",
                    [
                        ':uid' => $userId,
                        ':pid' => $planId ?: null,
                        ':ptype' => $paymentType,
                        ':oid' => $orderId,
                        ':pid_str' => $paymentId,
                        ':sig' => $signature,
                        ':amount' => $amountPaisa,
                    ]
                );
            }

            // Activate entitlement
            $activated = false;
            if ($paymentType === 'subscription') {
                $activated = SubscriptionService::upgrade($userId, $planId);
            } elseif ($paymentType === 'boost') {
                $activated = BoostService::activatePaidBoost($userId);
            } else {
                $activated = true;
            }

            if (!$activated) {
                throw new Exception("Failed to activate entitlement for {$paymentType}");
            }

            Database::commit();
            return true;
        } catch (\Throwable $e) {
            Database::rollBack();
            error_log("[Payment Processing Error] " . $e->getMessage());
            return false;
        }
    }
}
