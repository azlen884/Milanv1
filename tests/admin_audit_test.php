<?php

require_once __DIR__ . '/../app/autoload.php';

use App\Helpers\Database;
use App\Helpers\AdminAuth;
use App\Helpers\Security;
use App\Helpers\Session;

Session::start();
AdminAuth::login(1);

echo "====================================================\n";
echo "  MILAN DATING - SUPER ADMIN PANEL VERIFICATION     \n";
echo "====================================================\n\n";

$allPassed = true;
function assertTest(string $desc, bool $condition, string $details = '') {
    global $allPassed;
    if ($condition) {
        echo " [PASS] {$desc}\n";
    } else {
        echo " [FAIL] {$desc} - Details: {$details}\n";
        $allPassed = false;
    }
}

// 1. PDO and Database connection check
$pdo = Database::connect();
assertTest("Native PDO Database Connection", $pdo instanceof \PDO);

// 2. Admin Role check: Exactly one Super Admin role
$enumCheck = Database::query("SHOW COLUMNS FROM admin_users LIKE 'role'");
$roleType = $enumCheck[0]['Type'] ?? '';
assertTest("Admin table enforces ENUM('super_admin')", strpos($roleType, "enum('super_admin')") !== false, "Type is: {$roleType}");

// Verify no staff or moderator roles in admin_users
$otherRoles = Database::one("SELECT COUNT(*) as c FROM admin_users WHERE role != 'super_admin'")['c'] ?? 0;
assertTest("Zero non-Super Admin roles in database", (int)$otherRoles === 0);

// 3. Super Admin account exists and credentials verify
$admin = Database::one("SELECT * FROM admin_users WHERE email = 'admin@milandating.in' AND role = 'super_admin'");
assertTest("Super Admin account exists", !empty($admin));
$passMatch = Security::verifyPassword('Admin@123', $admin['password_hash'] ?? '');
assertTest("Super Admin password hashes match Admin@123", $passMatch);

// 4. Authenticate Super Admin session
AdminAuth::login((int)$admin['id']);
assertTest("AdminAuth::check() is true", AdminAuth::check());
assertTest("AdminAuth::admin() returns valid Super Admin", AdminAuth::admin()['role'] === 'super_admin');

// 5. Check exactly THREE subscription tiers exist
$plans = Database::query("SELECT * FROM subscription_plans ORDER BY id ASC");
assertTest("Exactly THREE subscription plans in database", count($plans) === 3, "Found: " . count($plans));
$planCodes = array_column($plans, 'code');
assertTest("Plans are Free, Monthly, 3 Months", in_array('free', $planCodes) && in_array('monthly', $planCodes) && in_array('three_months', $planCodes));

// 6. Check Razorpay settings exist
$rzpKey = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'razorpay_key_id'");
assertTest("Razorpay Key ID exists in settings", !empty($rzpKey['setting_value']));

// 7. Check Profile Boost settings exist
$boostPrice = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'");
assertTest("Boost price in INR setting exists", (int)($boostPrice['setting_value'] ?? 0) > 0);

// 8. Check No <table> tags in any admin views
$adminViews = glob(__DIR__ . '/../views/admin/*.php');
$tableFound = false;
$tableFiles = [];
foreach ($adminViews as $file) {
    $content = file_get_contents($file);
    if (stripos($content, '<table') !== false) {
        $tableFound = true;
        $tableFiles[] = basename($file);
    }
}
assertTest("Zero HTML <table> tags across all admin views", !$tableFound, "Found in: " . implode(', ', $tableFiles));

// 9. Check Admin Navbar: No public links like 'Sign In' or 'Register'
$layoutContent = file_get_contents(__DIR__ . '/../views/admin/layout.php');
$hasPublicSignIn = stripos($layoutContent, 'href="/login"') !== false || stripos($layoutContent, '>Sign In<') !== false;
assertTest("Admin layout has no public 'Sign In' or member links", !$hasPublicSignIn);

// 10. Test all Admin View Rendering
ob_start();
$viewsToTest = [
    'dashboard', 'analytics', 'users', 'kyc', 'subscriptions', 
    'payments', 'razorpay', 'boost', 'reports', 'discovery', 
    'landing_profiles', 'content', 'settings', 'audit_logs', 'system'
];

foreach ($viewsToTest as $viewName) {
    try {
        $controller = new \App\Controllers\AdminController();
        $method = ($viewName === 'audit_logs') ? 'auditLogs' : (($viewName === 'landing_profiles') ? 'landingProfiles' : $viewName);
        if (method_exists($controller, $method)) {
            $controller->$method();
            $out = ob_get_clean();
            ob_start();
            $hasContent = strlen($out) > 200;
            assertTest("Admin View: {$viewName} rendered successfully (" . strlen($out) . " bytes)", $hasContent);
        } else {
            assertTest("Admin Controller method for {$viewName} exists", false);
        }
    } catch (\Throwable $e) {
        ob_get_clean();
        ob_start();
        assertTest("Admin View: {$viewName} rendered without error", false, $e->getMessage() . ' at ' . $e->getFile() . ':' . $e->getLine());
    }
}
ob_end_clean();

echo "\n====================================================\n";
if ($allPassed) {
    echo "  ALL VERIFICATIONS PASSED SUCCESSFULLY (100%) \n";
} else {
    echo "  SOME TESTS FAILED \n";
}
echo "====================================================\n";

exit($allPassed ? 0 : 1);
