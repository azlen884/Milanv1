<?php

namespace App\Services;

use App\Helpers\Database;
use Exception;

class RazorpayService {
    private string $keyId;
    private string $keySecret;
    private string $webhookSecret;
    private bool $enabled;
    private string $mode;

    public function __construct() {
        // Load dynamically from database settings (Admin configured)
        $settingsRaw = Database::query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'razorpay_%'");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';

        $dbEnabled = $settings['razorpay_enabled'] ?? null;
        $this->enabled = ($dbEnabled !== null) ? ($dbEnabled === '1') : true;
        $this->mode = $settings['razorpay_mode'] ?? 'test';

        // Check DB first, fall back to environment/config if empty
        $dbKeyId = trim($settings['razorpay_key_id'] ?? '');
        $dbKeySecret = trim($settings['razorpay_key_secret'] ?? '');
        $dbWebhookSecret = trim($settings['razorpay_webhook_secret'] ?? '');

        $envKeyId = trim(env('RAZORPAY_KEY_ID', '') ?: ($config['razorpay']['key_id'] ?? ''));
        $envKeySecret = trim(env('RAZORPAY_KEY_SECRET', '') ?: ($config['razorpay']['key_secret'] ?? ''));
        $envWebhookSecret = trim(env('RAZORPAY_WEBHOOK_SECRET', '') ?: ($config['razorpay']['webhook_secret'] ?? ''));

        $this->keyId = $dbKeyId !== '' ? $dbKeyId : $envKeyId;
        $this->keySecret = $dbKeySecret !== '' ? $dbKeySecret : $envKeySecret;
        $this->webhookSecret = $dbWebhookSecret !== '' ? $dbWebhookSecret : $envWebhookSecret;
    }

    public function isEnabled(): bool {
        return $this->enabled;
    }

    public function getMode(): string {
        return $this->mode;
    }

    public function getKeyId(): string {
        return $this->keyId;
    }

    public function hasCredentials(): bool {
        return !empty($this->keyId) && !empty($this->keySecret);
    }

    /**
     * Create Razorpay Order via REST API
     */
    public function createOrder(int $amountPaisa, string $receipt, array $notes = []): array {
        if (!$this->enabled) {
            throw new Exception("Razorpay payment gateway is currently disabled by administrator. Please contact support.");
        }

        if (empty($this->keyId) || empty($this->keySecret)) {
            throw new Exception("Razorpay payment credentials are not configured. Please configure your Key ID and Key Secret in Admin Settings.");
        }

        if ($amountPaisa <= 0) {
            throw new Exception("Invalid order amount: {$amountPaisa} paise. Amount must be greater than zero.");
        }

        // Razorpay enforces a maximum 40-character receipt length
        $receipt = substr($receipt, 0, 40);

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
            CURLOPT_CONNECTTIMEOUT => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[Razorpay] Order creation cURL error: " . $curlError);
            throw new Exception("Razorpay gateway connection error: {$curlError}. Please verify your network connection.");
        }

        $data = json_decode($response, true);
        if ($httpCode >= 200 && $httpCode < 300 && isset($data['id'])) {
            return $data;
        }

        if ($httpCode === 401) {
            error_log("[Razorpay] Authentication failed for Key ID: " . substr($this->keyId, 0, 8) . "...");
            throw new Exception("Razorpay authentication failed. The configured Key ID or Key Secret is invalid or inactive. Please update your API credentials in Admin Settings.");
        }

        $errorDesc = $data['error']['description'] ?? ($data['error']['code'] ?? "Failed to create Razorpay order (HTTP {$httpCode}).");
        error_log("[Razorpay] Order creation failed (HTTP {$httpCode}): " . $errorDesc);
        throw new Exception("Razorpay error: " . $errorDesc);
    }

    /**
     * Verify Payment Signature (SHA256 HMAC)
     * Must strictly compute HMAC SHA256 of ($orderId . '|' . $paymentId) using Razorpay Key Secret
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
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[Razorpay] fetchPayment cURL error for {$paymentId}: {$curlError}");
            return null;
        }

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
        }

        error_log("[Razorpay] fetchPayment HTTP {$httpCode} for {$paymentId}: {$response}");
        return null;
    }

    /**
     * Capture an authorized payment via REST API
     */
    public function capturePayment(string $paymentId, int $amountPaisa, string $currency = 'INR'): ?array {
        if (empty($this->keyId) || empty($this->keySecret) || empty($paymentId)) {
            return null;
        }

        $url = 'https://api.razorpay.com/v1/payments/' . urlencode($paymentId) . '/capture';
        $payload = [
            'amount' => $amountPaisa,
            'currency' => $currency,
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
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 8,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($curlError) {
            error_log("[Razorpay] capturePayment cURL error for {$paymentId}: {$curlError}");
            return null;
        }

        if ($httpCode >= 200 && $httpCode < 300 && $response) {
            $data = json_decode($response, true);
            return is_array($data) ? $data : null;
        }

        error_log("[Razorpay] capturePayment HTTP {$httpCode} for {$paymentId}: {$response}");
        return null;
    }

    /**
     * Test API connection with configured credentials
     */
    public function testConnection(): array {
        if (empty($this->keyId) || empty($this->keySecret)) {
            return ['success' => false, 'error' => 'Razorpay Key ID or Key Secret is empty.'];
        }

        $url = 'https://api.razorpay.com/v1/orders?count=1';
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD => $this->keyId . ':' . $this->keySecret,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'User-Agent: MilanDating-Production/1.0',
            ],
            CURLOPT_TIMEOUT => 10,
            CURLOPT_CONNECTTIMEOUT => 6,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);

        if ($curlErr) {
            return ['success' => false, 'error' => "Network error connecting to Razorpay: {$curlErr}"];
        }

        if ($httpCode === 200) {
            return ['success' => true, 'message' => 'Credentials verified successfully! Connected to Razorpay.'];
        }

        if ($httpCode === 401) {
            return ['success' => false, 'error' => 'Authentication failed (HTTP 401). Invalid Key ID or Key Secret.'];
        }

        return ['success' => false, 'error' => "Razorpay returned HTTP {$httpCode}."];
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
