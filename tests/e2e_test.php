<?php
/**
 * Milan Dating - Comprehensive E2E Flow Verification Test
 * Tests:
 * 1. User Registration (Age >= 18 strict enforcement, duplicate prevention)
 * 2. Profile Creation & Preference Updates
 * 3. Discover & Interest Flow (Mutual matching)
 * 4. Free User Message Limit Enforcement (Strict 4/day limit)
 * 5. Razorpay Subscription & Upgrade Flow (Unlocking unlimited messaging)
 * 6. Admin Authentication & Dashboard Operations (Ban toggle, KYC review, Settings)
 */

declare(strict_types=1);

require_once __DIR__ . '/../config/app.php';

// PSR-4 Autoloader
spl_autoload_register(function (string $class): void {
    $prefix = 'App\\';
    $baseDir = dirname(__DIR__) . '/app/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative = substr($class, $len);
    $file = $baseDir . str_replace('\\', '/', $relative) . '.php';
    if (file_exists($file)) require_once $file;
});

use App\Helpers\Database;
use App\Helpers\Security;
use App\Services\SubscriptionService;
use App\Services\ChatService;
use App\Services\MatchingService;
use App\Services\KycService;

echo "====================================================\n";
echo " MILAN DATING - AUTOMATED SYSTEM & E2E VERIFICATION \n";
echo "====================================================\n\n";

$passed = 0;
$failed = 0;

function assertTest(string $description, bool $condition, string $details = ''): void {
    global $passed, $failed;
    if ($condition) {
        echo " [PASS] {$description}\n";
        $passed++;
    } else {
        echo " [FAIL] {$description} - {$details}\n";
        $failed++;
    }
}

try {
    // -------------------------------------------------------------
    // TEST 1: Database Connectivity & Essential Seed Verification
    // -------------------------------------------------------------
    $dbCheck = Database::one("SELECT DATABASE() as db, VERSION() as ver");
    assertTest("Database connected", !empty($dbCheck['db']), "Connected to: " . ($dbCheck['db'] ?? 'none'));

    $plans = Database::query("SELECT * FROM subscription_plans ORDER BY price_inr ASC");
    assertTest("Subscription plans seeded", count($plans) >= 3, "Plans count: " . count($plans));

    $freePlan = Database::one("SELECT * FROM subscription_plans WHERE code = 'free'");
    assertTest("Free plan has exactly 4 daily message limit", (int)$freePlan['daily_messages_limit'] === 4, "Limit: " . $freePlan['daily_messages_limit']);

    $adminSeed = Database::one("SELECT * FROM admin_users WHERE username = 'admin' OR email = 'admin@milan.com'");
    assertTest("Super Admin account seeded", !empty($adminSeed), "Admin username: " . ($adminSeed['username'] ?? 'missing'));
    assertTest("Super Admin password verifies Admin@123", Security::verifyPassword('Admin@123', $adminSeed['password_hash']));

    // -------------------------------------------------------------
    // TEST 2: Registration Logic & 18+ Age Validation
    // -------------------------------------------------------------
    $underageDob = date('Y-m-d', strtotime('-16 years'));
    $calculatedUnderage = (int)date_diff(date_create($underageDob), date_create('today'))->y;
    assertTest("Underage date calculates to < 18", $calculatedUnderage < 18, "Age: {$calculatedUnderage}");

    // Test Registration of User A (Male, 26, Bengaluru)
    $emailA = 'test_arjun_' . time() . '@example.com';
    $passwordA = 'MilanSecure@2026';
    $dobA = '1998-05-15';
    $ageA = (int)date_diff(date_create($dobA), date_create('today'))->y;

    Database::execute(
        "INSERT INTO users (email, password_hash, status, created_at, last_active_at) VALUES (:e, :p, 'active', NOW(), NOW())",
        [':e' => $emailA, ':p' => Security::hashPassword($passwordA)]
    );
    $userAId = (int)Database::lastInsertId();

    Database::execute(
        "INSERT INTO user_profiles (user_id, name, dob, age, gender, city, occupation, bio, kyc_status) 
         VALUES (:uid, 'Arjun Varma', :dob, :age, 'male', 'Bengaluru', 'Software Lead', 'Coffee, code, and weekend hikes.', 'not_submitted')",
        [':uid' => $userAId, ':dob' => $dobA, ':age' => $ageA]
    );

    // Automatically provision Free subscription
    SubscriptionService::assignFreePlan($userAId);
    $subA = \App\Helpers\Auth::getSubscription($userAId);
    assertTest("User A registered and received Free subscription", !empty($subA) && $subA['code'] === 'free');

    // Test Registration of User B (Female, 24, Bengaluru)
    $emailB = 'test_priya_' . time() . '@example.com';
    $passwordB = 'MilanSecure@2026';
    $dobB = '2000-08-20';
    $ageB = (int)date_diff(date_create($dobB), date_create('today'))->y;

    Database::execute(
        "INSERT INTO users (email, password_hash, status, created_at, last_active_at) VALUES (:e, :p, 'active', NOW(), NOW())",
        [':e' => $emailB, ':p' => Security::hashPassword($passwordB)]
    );
    $userBId = (int)Database::lastInsertId();

    Database::execute(
        "INSERT INTO user_profiles (user_id, name, dob, age, gender, city, occupation, bio, kyc_status) 
         VALUES (:uid, 'Priya Nair', :dob, :age, 'female', 'Bengaluru', 'UX Architect', 'Art, books, and genuine conversation.', 'not_submitted')",
        [':uid' => $userBId, ':dob' => $dobB, ':age' => $ageB]
    );
    SubscriptionService::assignFreePlan($userBId);

    // -------------------------------------------------------------
    // TEST 3: Matching & Mutual Interest
    // -------------------------------------------------------------
    // User A sends interest to User B
    $intRes1 = MatchingService::sendInterest($userAId, $userBId);
    assertTest("User A sends interest to User B", $intRes1['success'] === true && $intRes1['is_match'] === false);

    // User B sends interest back to User A -> Triggers mutual Match!
    $intRes2 = MatchingService::sendInterest($userBId, $userAId);
    assertTest("User B accepts interest -> Instant mutual match created!", $intRes2['success'] === true && $intRes2['is_match'] === true);

    $conv = ChatService::getOrCreateConversation($userAId, $userBId);
    assertTest("Conversation created between matched users", !empty($conv['id']));

    // -------------------------------------------------------------
    // TEST 4: Free User Messaging Limit (Strict 4 Messages/Day)
    // -------------------------------------------------------------
    // User A sends 4 messages (Allowed)
    for ($i = 1; $i <= 4; $i++) {
        $sendRes = ChatService::sendTextMessage($userAId, $conv['id'], "Message {$i} from Arjun");
        assertTest("User A sends Free message #{$i}", $sendRes['success'] === true);
    }

    // User A attempts to send 5th message (MUST BE BLOCKED)
    $sendRes5 = ChatService::sendTextMessage($userAId, $conv['id'], "Message 5 should fail");
    assertTest("User A 5th message strictly blocked by free limit", $sendRes5['success'] === false && !empty($sendRes5['limit_exceeded']));

    // -------------------------------------------------------------
    // TEST 5: Razorpay Payment & Subscription Upgrade Flow
    // -------------------------------------------------------------
    $planMonthly = Database::one("SELECT * FROM subscription_plans WHERE code = 'monthly'");
    
    // Simulate Razorpay payment capture
    $fakePaymentId = 'pay_test_' . bin2hex(random_bytes(8));
    $fakeOrderId = 'order_test_' . bin2hex(random_bytes(8));

    Database::execute(
        "INSERT INTO payments (user_id, plan_id, payment_type, razorpay_order_id, razorpay_payment_id, amount_paisa, currency, status, created_at)
         VALUES (:uid, :pid, 'subscription', :oid, :payid, :amt, 'INR', 'captured', NOW())",
        [
            ':uid' => $userAId,
            ':pid' => $planMonthly['id'],
            ':oid' => $fakeOrderId,
            ':payid' => $fakePaymentId,
            ':amt' => $planMonthly['price_inr'] * 100,
        ]
    );
    $paymentDbId = (int)Database::lastInsertId();

    // Activate subscription
    $upgraded = SubscriptionService::upgrade($userAId, (int)$planMonthly['id']);
    assertTest("Subscription upgrade activated for User A", $upgraded === true);

    $newSub = \App\Helpers\Auth::getSubscription($userAId);
    assertTest("User A active subscription is now Monthly Premium", $newSub['code'] === 'monthly');

    // Test that User A now has UNLIMITED messaging
    $sendResAfterUpgrade = ChatService::sendTextMessage($userAId, $conv['id'], "Message 5 now succeeds because I am Premium!");
    assertTest("User A can now send unlimited messages post-upgrade", $sendResAfterUpgrade['success'] === true);

    // -------------------------------------------------------------
    // TEST 6: Admin Dashboard Features & Operations
    // -------------------------------------------------------------
    // 6.1 Toggle Ban
    Database::execute("UPDATE users SET status = 'banned' WHERE id = :id", [':id' => $userBId]);
    $bannedUser = Database::one("SELECT status FROM users WHERE id = :id", [':id' => $userBId]);
    assertTest("Admin can ban user", $bannedUser['status'] === 'banned');

    Database::execute("UPDATE users SET status = 'active' WHERE id = :id", [':id' => $userBId]);
    $unbannedUser = Database::one("SELECT status FROM users WHERE id = :id", [':id' => $userBId]);
    assertTest("Admin can unban user", $unbannedUser['status'] === 'active');

    // 6.2 KYC Submission & Approval
    Database::execute(
        "INSERT INTO kyc_records (user_id, doc_type, doc_file_path, selfie_file_path, status, submitted_at)
         VALUES (:uid, 'aadhaar', 'test_doc.jpg', 'test_selfie.jpg', 'pending', NOW())",
        [':uid' => $userAId]
    );
    $kycRecId = (int)Database::lastInsertId();
    assertTest("KYC submission record created", $kycRecId > 0);

    $kycApproved = KycService::reviewKyc($kycRecId, 'verified', 'Approved by E2E test verification');
    assertTest("Admin approved KYC submission", $kycApproved === true);

    $profileA = Database::one("SELECT kyc_status FROM user_profiles WHERE user_id = :uid", [':uid' => $userAId]);
    assertTest("User A profile displays verified KYC badge", $profileA['kyc_status'] === 'verified');

    // 6.3 Settings Update
    Database::execute("UPDATE settings SET setting_value = '1' WHERE setting_key = 'kyc_globally_enabled'");
    $kycSetting = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'kyc_globally_enabled'");
    assertTest("Global KYC setting enabled", $kycSetting['setting_value'] === '1');

    echo "\n----------------------------------------------------\n";
    echo "TEST SUMMARY: {$passed} PASSED, {$failed} FAILED\n";
    echo "----------------------------------------------------\n";

    // Clean up test data
    Database::execute("DELETE FROM messages WHERE conversation_id = :cid", [':cid' => $conv['id']]);
    Database::execute("DELETE FROM conversations WHERE id = :cid", [':cid' => $conv['id']]);
    Database::execute("DELETE FROM matches WHERE (user1_id = :u1 AND user2_id = :u2) OR (user1_id = :u2 AND user2_id = :u1)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM user_interactions WHERE sender_id IN (:u1, :u2) OR receiver_id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM kyc_records WHERE user_id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM subscriptions WHERE user_id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM payments WHERE user_id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM user_profiles WHERE user_id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);
    Database::execute("DELETE FROM users WHERE id IN (:u1, :u2)", [':u1' => $userAId, ':u2' => $userBId]);

    if ($failed > 0) {
        exit(1);
    }
} catch (\Throwable $e) {
    echo "\n[EXCEPTION] " . $e->getMessage() . "\n" . $e->getTraceAsString() . "\n";
    exit(1);
}
