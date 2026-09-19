<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\AdminAuth;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\RateLimiter;
use App\Helpers\View;
use App\Services\KycService;
use App\Services\BoostService;
use App\Services\RazorpayService;

class AdminController {
    /**
     * Display Super Admin Sign In
     */
    public function showLogin(): void {
        if (AdminAuth::check() && AdminAuth::admin()) {
            header('Location: /admin/dashboard');
            exit;
        }

        View::renderAdmin('login', [
            'pageTitle' => 'Super Admin Login',
            'csrfToken' => Session::csrfToken(),
        ]);
    }

    /**
     * Authenticate Super Admin (Strictly single Super Admin role)
     */
    public function login(): void {
        $ip = RateLimiter::getClientIp();
        $username = trim($_POST['username'] ?? $_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        if (!RateLimiter::check('admin_login', $ip, 5, 900)) {
            Session::flash('error', 'Too many failed attempts. Please wait 15 minutes.');
            header('Location: /admin/login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/login');
            exit;
        }

        $admin = Database::one(
            "SELECT id, username, email, password_hash, role FROM admin_users WHERE (username = :u OR email = :u) AND role = 'super_admin' LIMIT 1",
            [':u' => $username]
        );

        if (!$admin || !Security::verifyPassword($password, $admin['password_hash'])) {
            Session::flash('error', 'Invalid administrator credentials.');
            header('Location: /admin/login');
            exit;
        }

        // Reset rate limiter on successful authentication
        Database::execute("DELETE FROM rate_limits WHERE action = 'admin_login' AND key_name LIKE :k", [':k' => '%:' . $ip]);

        AdminAuth::login((int)$admin['id']);
        AdminAuth::logAudit('admin_login', 'admin_users', (int)$admin['id'], 'Super Admin authenticated successfully.');

        Session::flash('success', 'Welcome back, ' . htmlspecialchars($admin['username']) . '!');
        header('Location: /admin/dashboard');
        exit;
    }

    /**
     * Super Admin Logout
     */
    public function logout(): void {
        if (AdminAuth::check()) {
            AdminAuth::logAudit('admin_logout', 'admin_users', AdminAuth::id(), 'Super Admin logged out.');
            AdminAuth::logout();
        }
        Session::flash('success', 'You have been safely logged out.');
        header('Location: /admin/login');
        exit;
    }

    /**
     * High-Level Dashboard
     * Optimized single aggregated metrics, zero N+1 queries
     */
    public function dashboard(): void {
        $this->ensureSuperAdmin();

        // 1. Fast Aggregated Platform Metrics
        $totalUsers = (int)(Database::one("SELECT COUNT(*) as c FROM users")['c'] ?? 0);
        $activeUsersToday = (int)(Database::one("SELECT COUNT(*) as c FROM users WHERE last_active_at >= DATE_SUB(NOW(), INTERVAL 24 HOUR)")['c'] ?? 0);
        $pendingKyc = (int)(Database::one("SELECT COUNT(*) as c FROM kyc_records WHERE status = 'pending'")['c'] ?? 0);
        $verifiedKyc = (int)(Database::one("SELECT COUNT(*) as c FROM user_profiles WHERE kyc_status = 'verified'")['c'] ?? 0);
        $paidSubscribers = (int)(Database::one("SELECT COUNT(*) as c FROM subscriptions WHERE status = 'active' AND plan_id != 1 AND expires_at > NOW()")['c'] ?? 0);
        $activeBoosts = (int)(Database::one("SELECT COUNT(*) as c FROM boosts WHERE status = 'active' AND expires_at > NOW()")['c'] ?? 0);
        $openReports = (int)(Database::one("SELECT COUNT(*) as c FROM reports WHERE status = 'open'")['c'] ?? 0);

        // 2. Revenue Summary
        $allTimeRevenue = (int)((Database::one("SELECT SUM(amount_paisa) as s FROM payments WHERE status = 'captured'")['s'] ?? 0) / 100);
        $todayRevenue = (int)((Database::one("SELECT SUM(amount_paisa) as s FROM payments WHERE status = 'captured' AND DATE(created_at) = CURDATE()")['s'] ?? 0) / 100);
        $monthRevenue = (int)((Database::one("SELECT SUM(amount_paisa) as s FROM payments WHERE status = 'captured' AND YEAR(created_at) = YEAR(CURDATE()) AND MONTH(created_at) = MONTH(CURDATE())")['s'] ?? 0) / 100);

        // 3. Recent Registrations (limited to 5)
        $recentUsers = Database::query(
            "SELECT u.id, u.email, u.status, u.created_at, p.name, p.age, p.city, p.gender, p.primary_photo, p.kyc_status,
                    COALESCE(sp.name, 'Free Plan') as plan_name
             FROM users u
             LEFT JOIN user_profiles p ON u.id = p.user_id
             LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
             LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
             GROUP BY u.id
             ORDER BY u.id DESC LIMIT 5"
        );

        // 4. Recent Payment Transactions (limited to 5)
        $recentPayments = Database::query(
            "SELECT pay.*, u.email as user_email, p.name as user_name 
             FROM payments pay
             JOIN users u ON pay.user_id = u.id
             LEFT JOIN user_profiles p ON u.id = p.user_id
             ORDER BY pay.id DESC LIMIT 5"
        );

        // 5. System Status summary
        $maintenanceMode = (Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")['setting_value'] ?? '0') === '1';
        $razorpayMode = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'razorpay_mode'")['setting_value'] ?? 'test';
        $razorpayEnabled = (Database::one("SELECT setting_value FROM settings WHERE setting_key = 'razorpay_enabled'")['setting_value'] ?? '1') === '1';

        View::renderAdmin('dashboard', [
            'pageTitle' => 'Dashboard',
            'metrics' => [
                'total_users' => $totalUsers,
                'active_today' => $activeUsersToday,
                'pending_kyc' => $pendingKyc,
                'verified_kyc' => $verifiedKyc,
                'paid_subscribers' => $paidSubscribers,
                'active_boosts' => $activeBoosts,
                'open_reports' => $openReports,
            ],
            'revenue' => [
                'today' => $todayRevenue,
                'this_month' => $monthRevenue,
                'all_time' => $allTimeRevenue,
            ],
            'recentUsers' => $recentUsers,
            'recentPayments' => $recentPayments,
            'systemStatus' => [
                'maintenance_mode' => $maintenanceMode,
                'razorpay_mode' => $razorpayMode,
                'razorpay_enabled' => $razorpayEnabled,
            ]
        ]);
    }

    /**
     * Analytics & Platform Growth Insights
     */
    public function analytics(): void {
        $this->ensureSuperAdmin();

        // 1. Gender breakdown
        $genderStats = Database::query(
            "SELECT gender, COUNT(*) as count 
             FROM user_profiles 
             GROUP BY gender"
        );

        // 2. Top Cities
        $topCities = Database::query(
            "SELECT city, COUNT(*) as count 
             FROM user_profiles 
             WHERE city IS NOT NULL AND city != '' 
             GROUP BY city 
             ORDER BY count DESC LIMIT 8"
        );

        // 3. Subscription Distribution
        $planDistribution = Database::query(
            "SELECT sp.name, sp.code, COUNT(s.id) as subscriber_count 
             FROM subscription_plans sp
             LEFT JOIN subscriptions s ON sp.id = s.plan_id AND s.status = 'active'
             GROUP BY sp.id"
        );

        // 4. Overall Activity Counts
        $totalMatches = (int)(Database::one("SELECT COUNT(*) as c FROM matches")['c'] ?? 0);
        $totalMessages = (int)(Database::one("SELECT COUNT(*) as c FROM messages")['c'] ?? 0);
        $totalBoostsPurchased = (int)(Database::one("SELECT COUNT(*) as c FROM payments WHERE payment_type = 'boost' AND status = 'captured'")['c'] ?? 0);

        View::renderAdmin('analytics', [
            'pageTitle' => 'Analytics & Platform Insights',
            'genderStats' => $genderStats,
            'topCities' => $topCities,
            'planDistribution' => $planDistribution,
            'totalMatches' => $totalMatches,
            'totalMessages' => $totalMessages,
            'totalBoostsPurchased' => $totalBoostsPurchased,
        ]);
    }

    /**
     * User Directory & Management (No HTML tables, card list)
     */
    public function users(): void {
        $this->ensureSuperAdmin();

        $search = trim($_GET['q'] ?? '');
        $statusFilter = $_GET['status'] ?? '';
        $kycFilter = $_GET['kyc'] ?? '';
        $planFilter = $_GET['plan'] ?? '';

        $where = ["1=1"];
        $params = [];

        if ($search !== '') {
            $where[] = "(u.email LIKE :q OR p.name LIKE :q OR p.city LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        if ($statusFilter && in_array($statusFilter, ['active', 'suspended', 'banned'], true)) {
            $where[] = "u.status = :status";
            $params[':status'] = $statusFilter;
        }

        if ($kycFilter && in_array($kycFilter, ['not_submitted', 'pending', 'verified', 'rejected', 'resubmission'], true)) {
            $where[] = "p.kyc_status = :kyc";
            $params[':kyc'] = $kycFilter;
        }

        if ($planFilter && in_array($planFilter, ['free', 'monthly', 'three_months'], true)) {
            $where[] = "sp.code = :plan";
            $params[':plan'] = $planFilter;
        }

        $whereClause = implode(' AND ', $where);

        $users = Database::query(
            "SELECT u.id, u.email, u.status, u.created_at, u.last_active_at,
                    p.name, p.dob, p.age, p.gender, p.city, p.occupation, p.primary_photo, p.kyc_status,
                    COALESCE(sp.name, 'Free Plan') as plan_name,
                    COALESCE(sp.code, 'free') as plan_code
             FROM users u
             LEFT JOIN user_profiles p ON u.id = p.user_id
             LEFT JOIN subscriptions s ON u.id = s.user_id AND s.status = 'active'
             LEFT JOIN subscription_plans sp ON s.plan_id = sp.id
             WHERE {$whereClause}
             GROUP BY u.id
             ORDER BY u.id DESC LIMIT 100",
            $params
        );

        View::renderAdmin('users', [
            'pageTitle' => 'User Management',
            'users' => $users,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'kycFilter' => $kycFilter,
            'planFilter' => $planFilter,
        ]);
    }

    /**
     * User Quick Actions
     */
    public function userAction(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $action = $_POST['action'] ?? '';

        if ($userId <= 0) {
            header('Location: /admin/users');
            exit;
        }

        if ($action === 'ban') {
            Database::execute("UPDATE users SET status = 'banned' WHERE id = :id", [':id' => $userId]);
            AdminAuth::logAudit('user_banned', 'users', $userId, 'User banned by Super Admin.');
            Session::flash('success', 'User has been banned.');
        } elseif ($action === 'unban' || $action === 'activate') {
            Database::execute("UPDATE users SET status = 'active' WHERE id = :id", [':id' => $userId]);
            AdminAuth::logAudit('user_unbanned', 'users', $userId, 'User set to active by Super Admin.');
            Session::flash('success', 'User unbanned and restored to active status.');
        } elseif ($action === 'delete') {
            Database::execute("DELETE FROM users WHERE id = :id", [':id' => $userId]);
            AdminAuth::logAudit('user_deleted', 'users', $userId, 'User permanently deleted by Super Admin.');
            Session::flash('success', 'User record permanently deleted.');
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/users'));
        exit;
    }

    public function toggleBan(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $user = Database::one("SELECT id, status FROM users WHERE id = :id", [':id' => $userId]);
        if ($user) {
            $newStatus = ($user['status'] === 'banned') ? 'active' : 'banned';
            Database::execute("UPDATE users SET status = :st WHERE id = :id", [':st' => $newStatus, ':id' => $userId]);
            AdminAuth::logAudit('toggle_ban', 'users', $userId, "User status toggled to {$newStatus}");
            Session::flash('success', "User status updated to {$newStatus}.");
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/users'));
        exit;
    }

    public function verifyKycManual(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        $status = $_POST['status'] ?? 'verified';
        $validStatuses = ['not_submitted', 'pending', 'verified', 'rejected', 'resubmission'];
        $dbStatus = in_array($status, $validStatuses, true) ? $status : 'verified';

        Database::execute("UPDATE user_profiles SET kyc_status = :st WHERE user_id = :uid", [':st' => $dbStatus, ':uid' => $userId]);
        AdminAuth::logAudit('user_kyc_manual', 'user_profiles', $userId, "KYC manually set to {$dbStatus}");

        Session::flash('success', "User KYC verification status set to {$dbStatus}.");
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/users'));
        exit;
    }

    public function deleteUser(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/users');
            exit;
        }

        $userId = (int)($_POST['user_id'] ?? 0);
        if ($userId > 0) {
            Database::execute("DELETE FROM users WHERE id = :id", [':id' => $userId]);
            AdminAuth::logAudit('user_deleted', 'users', $userId, 'User permanently deleted.');
            Session::flash('success', 'User has been permanently deleted.');
        }

        header('Location: /admin/users');
        exit;
    }

    /**
     * Safety & Moderation Reports
     */
    public function reports(): void {
        $this->ensureSuperAdmin();

        $statusFilter = $_GET['status'] ?? 'all';
        $where = ["1=1"];
        $params = [];

        if (in_array($statusFilter, ['open', 'resolved', 'dismissed'], true)) {
            $where[] = "r.status = :st";
            $params[':st'] = $statusFilter;
        }

        $whereClause = implode(' AND ', $where);

        $reports = Database::query(
            "SELECT r.*, 
                    rep.email as reporter_email, p_rep.name as reporter_name,
                    tar.email as reported_email, p_tar.name as reported_name, tar.status as reported_status
             FROM reports r
             JOIN users rep ON r.reporter_id = rep.id
             JOIN user_profiles p_rep ON r.reporter_id = p_rep.user_id
             JOIN users tar ON r.reported_id = tar.id
             JOIN user_profiles p_tar ON r.reported_id = p_tar.user_id
             WHERE {$whereClause}
             ORDER BY (r.status = 'open') DESC, r.id DESC LIMIT 100",
            $params
        );

        View::renderAdmin('reports', [
            'pageTitle' => 'Safety & Moderation Reports',
            'reports' => $reports,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function resolveReport(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/reports');
            exit;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        $action = $_POST['action'] ?? 'resolve';

        if ($action === 'ban') {
            $report = Database::one("SELECT reported_id FROM reports WHERE id = :id", [':id' => $reportId]);
            if ($report) {
                Database::execute("UPDATE users SET status = 'banned' WHERE id = :id", [':id' => $report['reported_id']]);
                Database::execute("UPDATE reports SET status = 'resolved' WHERE id = :id", [':id' => $reportId]);
                AdminAuth::logAudit('report_ban_offender', 'reports', $reportId, "Offender banned and report resolved");
                Session::flash('success', 'Reported user has been banned and report marked resolved.');
            }
        } else {
            Database::execute("UPDATE reports SET status = 'resolved' WHERE id = :id", [':id' => $reportId]);
            AdminAuth::logAudit('report_resolved', 'reports', $reportId, 'Report marked as resolved');
            Session::flash('success', 'Report marked as resolved.');
        }

        header('Location: /admin/reports');
        exit;
    }

    public function dismissReport(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/reports');
            exit;
        }

        $reportId = (int)($_POST['report_id'] ?? 0);
        Database::execute("UPDATE reports SET status = 'dismissed' WHERE id = :id", [':id' => $reportId]);
        AdminAuth::logAudit('report_dismissed', 'reports', $reportId, 'Report dismissed by Super Admin');

        Session::flash('success', 'Report dismissed.');
        header('Location: /admin/reports');
        exit;
    }

    /**
     * KYC Compliance Queue
     */
    public function kyc(): void {
        $this->ensureSuperAdmin();

        $statusFilter = $_GET['status'] ?? 'pending';
        $where = ["1=1"];
        $params = [];

        if (in_array($statusFilter, ['pending', 'verified', 'rejected', 'resubmission'], true)) {
            $where[] = "k.status = :st";
            $params[':st'] = $statusFilter;
        }

        $whereClause = implode(' AND ', $where);

        $kycGloballyEnabled = KycService::isGloballyEnabled();
        $records = Database::query(
            "SELECT k.*, k.doc_type as document_type, u.email as user_email, p.name as user_name, p.age, p.city, p.primary_photo
             FROM kyc_records k
             JOIN users u ON k.user_id = u.id
             JOIN user_profiles p ON k.user_id = p.user_id
             WHERE {$whereClause}
             ORDER BY k.id DESC LIMIT 100",
            $params
        );

        View::renderAdmin('kyc', [
            'pageTitle' => 'KYC Compliance Management',
            'records' => $records,
            'kycGloballyEnabled' => $kycGloballyEnabled,
            'statusFilter' => $statusFilter,
        ]);
    }

    public function reviewKyc(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/kyc');
            exit;
        }

        $recordId = (int)($_POST['record_id'] ?? $_POST['kyc_id'] ?? 0);
        $decision = $_POST['decision'] ?? '';
        $reason = trim($_POST['reason'] ?? '');

        if ($recordId > 0 && in_array($decision, ['approve', 'reject', 'resubmission'], true)) {
            $result = KycService::reviewSubmission($recordId, $decision, $reason);
            AdminAuth::logAudit('kyc_review', 'kyc_records', $recordId, "Decision: {$decision}. Reason: {$reason}");
            Session::flash('success', "KYC submission has been processed: " . ucfirst($decision));
        }

        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/kyc'));
        exit;
    }

    public function toggleGlobalKyc(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/kyc');
            exit;
        }

        $enabled = !empty($_POST['kyc_globally_enabled']) ? '1' : '0';
        Database::execute(
            "UPDATE settings SET setting_value = :v WHERE setting_key = 'kyc_globally_enabled'",
            [':v' => $enabled]
        );

        AdminAuth::logAudit('kyc_global_toggle', 'settings', null, "Global KYC requirement set to {$enabled}");
        Session::flash('success', 'Global KYC enforcement updated.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/kyc'));
        exit;
    }

    /**
     * Protected Stream for KYC Documents
     */
    public function viewKycDocument(): void {
        $this->ensureSuperAdmin();

        $file = '';
        if (!empty($_GET['file'])) {
            $file = basename($_GET['file']);
        } elseif (!empty($_GET['id'])) {
            $kycId = (int)$_GET['id'];
            $type = $_GET['type'] ?? 'doc';
            $rec = Database::one("SELECT doc_file_path, selfie_file_path FROM kyc_records WHERE id = :id", [':id' => $kycId]);
            if ($rec) {
                $file = ($type === 'selfie') ? $rec['selfie_file_path'] : $rec['doc_file_path'];
            }
        }

        if (empty($file)) {
            http_response_code(404);
            die("Document not specified.");
        }

        $path = dirname(__DIR__, 2) . '/storage/kyc/' . basename($file);
        if (!file_exists($path)) {
            http_response_code(404);
            die("Document file does not exist.");
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $path) ?: 'image/jpeg';
        finfo_close($finfo);

        header('Content-Type: ' . $mime);
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: private, no-store, max-age=0');
        readfile($path);
        exit;
    }

    /**
     * Subscription Plans (Exactly THREE tiers: Free, Monthly, 3 Months)
     */
    public function subscriptions(): void {
        $this->ensureSuperAdmin();

        $plans = Database::query("SELECT * FROM subscription_plans ORDER BY id ASC");
        
        // Count active subscribers per plan
        $subCounts = [];
        $subCountsRaw = Database::query(
            "SELECT plan_id, COUNT(*) as cnt 
             FROM subscriptions 
             WHERE status = 'active' AND (expires_at IS NULL OR expires_at > NOW()) 
             GROUP BY plan_id"
        );
        foreach ($subCountsRaw as $row) {
            $subCounts[$row['plan_id']] = (int)$row['cnt'];
        }

        View::renderAdmin('subscriptions', [
            'pageTitle' => 'Subscription Plans',
            'plans' => $plans,
            'subCounts' => $subCounts,
        ]);
    }

    public function updatePlan(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/subscriptions');
            exit;
        }

        $planId = (int)($_POST['plan_id'] ?? 0);
        $price = (int)($_POST['price_inr'] ?? 0);
        $dailyLimit = (int)($_POST['daily_messages_limit'] ?? 0);
        $canViewVisitors = !empty($_POST['can_view_visitors']) ? 1 : 0;
        $includesBoost = !empty($_POST['includes_boost']) ? 1 : 0;
        $isActive = !empty($_POST['is_active']) ? 1 : 0;
        $desc = trim($_POST['description'] ?? '');

        Database::execute(
            "UPDATE subscription_plans 
             SET price_inr = :p, daily_messages_limit = :dml, 
                 can_view_visitors = :cvv, includes_boost = :ib, is_active = :act, description = :desc
             WHERE id = :id",
            [
                ':p' => $price,
                ':dml' => $dailyLimit,
                ':cvv' => $canViewVisitors,
                ':ib' => $includesBoost,
                ':act' => $isActive,
                ':desc' => $desc,
                ':id' => $planId,
            ]
        );

        AdminAuth::logAudit('plan_update', 'subscription_plans', $planId, "Plan #{$planId} updated (Price: ₹{$price})");
        Session::flash('success', 'Subscription plan updated successfully.');
        header('Location: /admin/subscriptions');
        exit;
    }

    /**
     * Payments & Transactions
     */
    public function payments(): void {
        $this->ensureSuperAdmin();

        $search = trim($_GET['q'] ?? '');
        $statusFilter = $_GET['status'] ?? '';
        $typeFilter = $_GET['type'] ?? '';

        $where = ["1=1"];
        $params = [];

        if ($search !== '') {
            $where[] = "(pay.razorpay_order_id LIKE :q OR pay.razorpay_payment_id LIKE :q OR u.email LIKE :q)";
            $params[':q'] = "%{$search}%";
        }

        if ($statusFilter && in_array($statusFilter, ['captured', 'created', 'failed'], true)) {
            $where[] = "pay.status = :st";
            $params[':st'] = $statusFilter;
        }

        if ($typeFilter && in_array($typeFilter, ['subscription', 'boost'], true)) {
            $where[] = "pay.payment_type = :pt";
            $params[':pt'] = $typeFilter;
        }

        $whereClause = implode(' AND ', $where);

        $transactions = Database::query(
            "SELECT pay.*, u.email as user_email, p.name as user_name 
             FROM payments pay
             JOIN users u ON pay.user_id = u.id
             LEFT JOIN user_profiles p ON u.id = p.user_id
             WHERE {$whereClause}
             ORDER BY pay.id DESC LIMIT 100",
            $params
        );

        $totalCapturedAmount = (int)((Database::one("SELECT SUM(amount_paisa) as s FROM payments WHERE status = 'captured'")['s'] ?? 0) / 100);

        View::renderAdmin('payments', [
            'pageTitle' => 'Payment Transactions',
            'transactions' => $transactions,
            'search' => $search,
            'statusFilter' => $statusFilter,
            'typeFilter' => $typeFilter,
            'totalCapturedAmount' => $totalCapturedAmount,
        ]);
    }

    /**
     * Dedicated Razorpay Gateway Management (Requirement 3)
     */
    public function razorpay(): void {
        $this->ensureSuperAdmin();

        $settingsRaw = Database::query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'razorpay_%'");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $keyId = $settings['razorpay_key_id'] ?? ($config['razorpay']['key_id'] ?? '');
        $hasSecret = !empty($settings['razorpay_key_secret']) || !empty($config['razorpay']['key_secret']);
        $hasWebhookSecret = !empty($settings['razorpay_webhook_secret']) || !empty($config['razorpay']['webhook_secret']);
        $mode = $settings['razorpay_mode'] ?? 'test';
        $enabled = ($settings['razorpay_enabled'] ?? '1') === '1';

        // Recent 10 Razorpay transactions
        $recentGatewayLogs = Database::query(
            "SELECT pay.*, u.email as user_email 
             FROM payments pay
             JOIN users u ON pay.user_id = u.id
             ORDER BY pay.id DESC LIMIT 10"
        );

        View::renderAdmin('razorpay', [
            'pageTitle' => 'Razorpay Gateway Management',
            'keyId' => $keyId,
            'hasSecret' => $hasSecret,
            'hasWebhookSecret' => $hasWebhookSecret,
            'mode' => $mode,
            'enabled' => $enabled,
            'recentLogs' => $recentGatewayLogs,
        ]);
    }

    public function updateRazorpay(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/razorpay');
            exit;
        }

        $enabled = !empty($_POST['razorpay_enabled']) ? '1' : '0';
        $mode = ($_POST['razorpay_mode'] ?? 'test') === 'live' ? 'live' : 'test';
        $keyId = trim($_POST['razorpay_key_id'] ?? '');
        $keySecret = trim($_POST['razorpay_key_secret'] ?? '');
        $webhookSecret = trim($_POST['razorpay_webhook_secret'] ?? '');

        // Save enabled & mode & key_id
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES ('razorpay_enabled', :v) 
             ON DUPLICATE KEY UPDATE setting_value = :v",
            [':v' => $enabled]
        );
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES ('razorpay_mode', :v) 
             ON DUPLICATE KEY UPDATE setting_value = :v",
            [':v' => $mode]
        );
        Database::execute(
            "INSERT INTO settings (setting_key, setting_value) VALUES ('razorpay_key_id', :v) 
             ON DUPLICATE KEY UPDATE setting_value = :v",
            [':v' => $keyId]
        );

        // Update secret only if user provided a new one (never overwrite with empty or reveal in UI)
        if ($keySecret !== '') {
            Database::execute(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('razorpay_key_secret', :v) 
                 ON DUPLICATE KEY UPDATE setting_value = :v",
                [':v' => $keySecret]
            );
        }

        if ($webhookSecret !== '') {
            Database::execute(
                "INSERT INTO settings (setting_key, setting_value) VALUES ('razorpay_webhook_secret', :v) 
                 ON DUPLICATE KEY UPDATE setting_value = :v",
                [':v' => $webhookSecret]
            );
        }

        AdminAuth::logAudit('razorpay_settings_updated', 'settings', null, "Razorpay updated (Mode: {$mode}, Enabled: {$enabled})");
        Session::flash('success', 'Razorpay Gateway configuration saved securely.');
        header('Location: /admin/razorpay');
        exit;
    }

    /**
     * Boost Management (Requirement 5)
     */
    public function boost(): void {
        $this->ensureSuperAdmin();

        $price = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_price_inr'")['setting_value'] ?? 19);
        $duration = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_duration_hours'")['setting_value'] ?? 24);
        $enabled = (Database::one("SELECT setting_value FROM settings WHERE setting_key = 'boost_globally_enabled'")['setting_value'] ?? '1') === '1';
        $maxActive = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'max_active_boosts_per_user'")['setting_value'] ?? 3);

        // Active boosts right now
        $activeBoosts = Database::query(
            "SELECT b.*, u.email, p.name, p.age, p.city, p.primary_photo 
             FROM boosts b
             JOIN users u ON b.user_id = u.id
             LEFT JOIN user_profiles p ON u.id = p.user_id
             WHERE b.status = 'active' AND b.expires_at > NOW()
             ORDER BY b.expires_at ASC"
        );

        // Boost purchase transactions
        $boostPurchases = Database::query(
            "SELECT pay.*, u.email, p.name 
             FROM payments pay
             JOIN users u ON pay.user_id = u.id
             LEFT JOIN user_profiles p ON u.id = p.user_id
             WHERE pay.payment_type = 'boost'
             ORDER BY pay.id DESC LIMIT 50"
        );

        View::renderAdmin('boost', [
            'pageTitle' => 'Profile Boost Management',
            'price' => $price,
            'duration' => $duration,
            'enabled' => $enabled,
            'maxActive' => $maxActive,
            'activeBoosts' => $activeBoosts,
            'boostPurchases' => $boostPurchases,
        ]);
    }

    public function updateBoostSettings(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/boost');
            exit;
        }

        $enabled = !empty($_POST['boost_globally_enabled']) ? '1' : '0';
        $price = max(1, (int)($_POST['boost_price_inr'] ?? 19));
        $duration = max(1, (int)($_POST['boost_duration_hours'] ?? 24));
        $maxActive = max(1, (int)($_POST['max_active_boosts_per_user'] ?? 3));

        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'boost_globally_enabled'", [':v' => $enabled]);
        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'boost_price_inr'", [':v' => $price]);
        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'boost_duration_hours'", [':v' => $duration]);
        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'max_active_boosts_per_user'", [':v' => $maxActive]);

        AdminAuth::logAudit('boost_settings_updated', 'settings', null, "Boost settings updated (Price: ₹{$price}, Duration: {$duration}h)");
        Session::flash('success', 'Profile Boost configuration updated.');
        header('Location: /admin/boost');
        exit;
    }

    /**
     * Discovery & Dating Settings
     */
    public function discovery(): void {
        $this->ensureSuperAdmin();

        $minAge = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'min_age_requirement'")['setting_value'] ?? 18);
        $maxDistance = (int)(Database::one("SELECT setting_value FROM settings WHERE setting_key = 'max_search_distance_km'")['setting_value'] ?? 100);
        $requirePhoto = (Database::one("SELECT setting_value FROM settings WHERE setting_key = 'require_photo_for_discovery'")['setting_value'] ?? '1') === '1';

        $interests = Database::query("SELECT * FROM interests ORDER BY category ASC, name ASC");

        View::renderAdmin('discovery', [
            'pageTitle' => 'Discovery & Dating Settings',
            'minAge' => $minAge,
            'maxDistance' => $maxDistance,
            'requirePhoto' => $requirePhoto,
            'interests' => $interests,
        ]);
    }

    public function updateDiscoverySettings(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/discovery');
            exit;
        }

        $minAge = max(18, (int)($_POST['min_age_requirement'] ?? 18));
        $maxDist = max(10, (int)($_POST['max_search_distance_km'] ?? 100));
        $reqPhoto = !empty($_POST['require_photo_for_discovery']) ? '1' : '0';

        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'min_age_requirement'", [':v' => $minAge]);
        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'max_search_distance_km'", [':v' => $maxDist]);
        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'require_photo_for_discovery'", [':v' => $reqPhoto]);

        AdminAuth::logAudit('discovery_settings_updated', 'settings', null, 'Discovery settings updated.');
        Session::flash('success', 'Discovery and dating algorithm parameters saved.');
        header('Location: /admin/discovery');
        exit;
    }

    public function saveInterest(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/discovery');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? 'Lifestyle');

        if ($name !== '') {
            Database::execute(
                "INSERT IGNORE INTO interests (name, category) VALUES (:name, :category)",
                [':name' => $name, ':category' => $category]
            );
            AdminAuth::logAudit('interest_added', 'interests', null, "Interest '{$name}' added to {$category}");
            Session::flash('success', "Interest '{$name}' added.");
        }

        header('Location: /admin/discovery');
        exit;
    }

    public function deleteInterest(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/discovery');
            exit;
        }

        $id = (int)($_POST['interest_id'] ?? 0);
        if ($id > 0) {
            Database::execute("DELETE FROM interests WHERE id = :id", [':id' => $id]);
            AdminAuth::logAudit('interest_deleted', 'interests', $id, "Interest #{$id} deleted");
            Session::flash('success', 'Interest deleted.');
        }

        header('Location: /admin/discovery');
        exit;
    }

    /**
     * Landing Marketing Profiles Management
     */
    public function landingProfiles(): void {
        $this->ensureSuperAdmin();

        $profiles = Database::query(
            "SELECT * FROM landing_marketing_profiles ORDER BY display_order ASC, id ASC"
        );

        View::renderAdmin('landing_profiles', [
            'pageTitle' => 'Landing Page Marketing Profiles',
            'profiles' => $profiles,
        ]);
    }

    public function saveLandingProfile(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/landing-profiles');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        $name = trim($_POST['name'] ?? '');
        $age = (int)($_POST['age'] ?? 25);
        $gender = in_array($_POST['gender'] ?? '', ['male', 'female', 'other'], true) ? $_POST['gender'] : 'female';
        $city = trim($_POST['city'] ?? '');
        $photoUrl = trim($_POST['photo_url'] ?? '');
        $shortBio = trim($_POST['short_bio'] ?? '');
        $interests = trim($_POST['interests'] ?? '');
        $badge = trim($_POST['badge'] ?? 'Verified');
        $order = (int)($_POST['display_order'] ?? 0);
        $isActive = !empty($_POST['is_active']) ? 1 : 0;

        if ($id > 0) {
            Database::execute(
                "UPDATE landing_marketing_profiles 
                 SET name = :name, age = :age, gender = :gender, city = :city, photo_url = :photo, 
                     short_bio = :bio, interests = :interests, badge = :badge, display_order = :order, is_active = :active
                 WHERE id = :id",
                [
                    ':name' => $name,
                    ':age' => $age,
                    ':gender' => $gender,
                    ':city' => $city,
                    ':photo' => $photoUrl,
                    ':bio' => $shortBio,
                    ':interests' => $interests,
                    ':badge' => $badge,
                    ':order' => $order,
                    ':active' => $isActive,
                    ':id' => $id,
                ]
            );
            AdminAuth::logAudit('landing_profile_updated', 'landing_marketing_profiles', $id, "Updated landing profile: {$name}");
            Session::flash('success', 'Marketing profile updated successfully.');
        } else {
            Database::execute(
                "INSERT INTO landing_marketing_profiles (name, age, gender, city, photo_url, short_bio, interests, badge, display_order, is_active)
                 VALUES (:name, :age, :gender, :city, :photo, :bio, :interests, :badge, :order, :active)",
                [
                    ':name' => $name,
                    ':age' => $age,
                    ':gender' => $gender,
                    ':city' => $city,
                    ':photo' => $photoUrl,
                    ':bio' => $shortBio,
                    ':interests' => $interests,
                    ':badge' => $badge,
                    ':order' => $order,
                    ':active' => $isActive,
                ]
            );
            AdminAuth::logAudit('landing_profile_created', 'landing_marketing_profiles', null, "Created landing profile: {$name}");
            Session::flash('success', 'New marketing profile added.');
        }

        header('Location: /admin/landing-profiles');
        exit;
    }

    public function deleteLandingProfile(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/landing-profiles');
            exit;
        }

        $id = (int)($_POST['id'] ?? 0);
        if ($id > 0) {
            Database::execute("DELETE FROM landing_marketing_profiles WHERE id = :id", [':id' => $id]);
            AdminAuth::logAudit('landing_profile_deleted', 'landing_marketing_profiles', $id, "Deleted landing profile #{$id}");
            Session::flash('success', 'Marketing profile deleted.');
        }

        header('Location: /admin/landing-profiles');
        exit;
    }

    /**
     * Site Content & FAQ Management
     */
    public function content(): void {
        $this->ensureSuperAdmin();

        $tagline = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'site_tagline'")['setting_value'] ?? 'Meaningful Connections for Modern Indians';
        $contactEmail = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'contact_email'")['setting_value'] ?? 'support@milandating.in';
        $faqJson = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'landing_faq_json'")['setting_value'] ?? '[]';
        $faqs = json_decode($faqJson, true) ?: [];

        View::renderAdmin('content', [
            'pageTitle' => 'Site Content & FAQ',
            'tagline' => $tagline,
            'contactEmail' => $contactEmail,
            'faqs' => $faqs,
        ]);
    }

    public function saveContent(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/content');
            exit;
        }

        $tagline = trim($_POST['site_tagline'] ?? '');
        $contactEmail = trim($_POST['contact_email'] ?? '');

        if ($tagline !== '') {
            Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'site_tagline'", [':v' => $tagline]);
        }
        if ($contactEmail !== '') {
            Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'contact_email'", [':v' => $contactEmail]);
        }

        AdminAuth::logAudit('content_updated', 'settings', null, 'Site content copy updated.');
        Session::flash('success', 'Site content updated successfully.');
        header('Location: /admin/content');
        exit;
    }

    /**
     * Platform Global Settings
     */
    public function settings(): void {
        $this->ensureSuperAdmin();

        $settingsRaw = Database::query("SELECT setting_key, setting_value FROM settings");
        $settings = [];
        foreach ($settingsRaw as $row) {
            $settings[$row['setting_key']] = $row['setting_value'];
        }

        View::renderAdmin('settings', [
            'pageTitle' => 'Global Platform Settings',
            'settings' => $settings,
        ]);
    }

    public function updateSettings(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/settings');
            exit;
        }

        $allowedKeys = [
            'site_name', 'site_tagline', 'contact_email', 'maintenance_mode', 
            'kyc_globally_enabled', 'free_daily_messages_limit'
        ];

        foreach ($allowedKeys as $key) {
            if (isset($_POST[$key])) {
                $val = trim($_POST[$key]);
                Database::execute(
                    "INSERT INTO settings (setting_key, setting_value) VALUES (:k, :v) 
                     ON DUPLICATE KEY UPDATE setting_value = :v",
                    [':k' => $key, ':v' => $val]
                );
            }
        }

        AdminAuth::logAudit('settings_updated', 'settings', null, 'Global settings updated by Super Admin.');
        Session::flash('success', 'Settings updated successfully.');
        header('Location: /admin/settings');
        exit;
    }

    /**
     * Security Audit Logs
     */
    public function auditLogs(): void {
        $this->ensureSuperAdmin();

        $actionFilter = $_GET['action'] ?? '';
        $where = ["1=1"];
        $params = [];

        if ($actionFilter !== '') {
            $where[] = "a.action LIKE :act";
            $params[':act'] = "%{$actionFilter}%";
        }

        $whereClause = implode(' AND ', $where);

        $logs = Database::query(
            "SELECT a.*, adm.username as admin_username 
             FROM audit_logs a
             LEFT JOIN admin_users adm ON a.admin_id = adm.id
             WHERE {$whereClause}
             ORDER BY a.id DESC LIMIT 100",
            $params
        );

        View::renderAdmin('audit_logs', [
            'pageTitle' => 'Security Audit Logs',
            'logs' => $logs,
            'actionFilter' => $actionFilter,
        ]);
    }

    /**
     * System & Runtime Health
     */
    public function system(): void {
        $this->ensureSuperAdmin();

        $tables = Database::query("SHOW TABLE STATUS");
        $maintenanceActive = (Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")['setting_value'] ?? '0') === '1';

        $systemInfo = [
            'php_version' => PHP_VERSION,
            'pdo_loaded' => class_exists('PDO'),
            'pdo_mysql_loaded' => extension_loaded('pdo_mysql'),
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'PHP CLI / Built-in',
            'tables_count' => count($tables),
            'maintenance_mode' => $maintenanceActive,
            'memory_usage' => round(memory_get_usage() / 1024 / 1024, 2) . ' MB',
        ];

        View::renderAdmin('system', [
            'pageTitle' => 'System & Runtime Health',
            'systemInfo' => $systemInfo,
            'tables' => $tables,
        ]);
    }

    public function toggleMaintenance(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/system');
            exit;
        }

        $current = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'maintenance_mode'")['setting_value'] ?? '0';
        $new = ($current === '1') ? '0' : '1';

        Database::execute("UPDATE settings SET setting_value = :v WHERE setting_key = 'maintenance_mode'", [':v' => $new]);
        AdminAuth::logAudit('maintenance_toggled', 'settings', null, "Maintenance mode set to {$new}");

        Session::flash('success', ($new === '1') ? 'Maintenance mode is now ACTIVE.' : 'Maintenance mode DISABLED. Site is live.');
        header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '/admin/system'));
        exit;
    }

    public function cleanupSystem(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/system');
            exit;
        }

        // Clean expired boosts
        $expiredBoosts = BoostService::expireOverdue();
        // Clean expired rate limits
        $stmt = Database::connect()->prepare("DELETE FROM rate_limits WHERE reset_at < UNIX_TIMESTAMP()");
        $stmt->execute();
        $expiredRateLimits = $stmt->rowCount();

        AdminAuth::logAudit('system_cleanup', 'system', null, "Pruned {$expiredBoosts} expired boosts, {$expiredRateLimits} rate limit records.");
        Session::flash('success', "System cleanup completed: {$expiredBoosts} expired boosts and {$expiredRateLimits} rate limits cleared.");
        header('Location: /admin/system');
        exit;
    }

    /**
     * Admin Bots Management View (Part 7: Admin Bots)
     */
    public function bots(): void {
        $this->ensureSuperAdmin();

        $bots = Database::query(
            "SELECT b.*, p.age, p.gender, p.city, p.bio,
                    (SELECT COUNT(*) FROM admin_bot_messages WHERE bot_id = b.id) as message_count,
                    (SELECT COUNT(*) FROM admin_bot_deliveries WHERE bot_id = b.id) as sent_count
             FROM admin_bots b
             JOIN user_profiles p ON b.user_id = p.user_id
             ORDER BY b.id DESC"
        );

        foreach ($bots as &$bot) {
            $bot['messages'] = Database::query(
                "SELECT * FROM admin_bot_messages WHERE bot_id = :bid ORDER BY delay_minutes ASC, message_order ASC",
                [':bid' => $bot['id']]
            );
        }
        unset($bot);

        View::renderAdmin('bots', [
            'pageTitle' => 'Admin Bots & Automated Predefined Messages',
            'bots' => $bots,
            'csrfToken' => Session::csrfToken(),
        ]);
    }

    /**
     * Create a new Admin Bot with real profile & optional initial message
     */
    public function createBot(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/bots');
            exit;
        }

        $name = trim($_POST['name'] ?? '');
        $age = max(18, min(80, (int)($_POST['age'] ?? 24)));
        $gender = in_array($_POST['gender'] ?? '', ['male', 'female'], true) ? $_POST['gender'] : 'female';
        $city = trim($_POST['city'] ?? 'Mumbai');
        $bio = trim($_POST['bio'] ?? 'Hello! Nice to meet you here on Milan Dating.');
        $initialMessage = trim($_POST['initial_message'] ?? '');
        $initialDelay = max(0, (int)($_POST['initial_delay_minutes'] ?? 0));

        if (empty($name)) {
            Session::flash('error', 'Bot name is required.');
            header('Location: /admin/bots');
            exit;
        }

        // Handle avatar photo upload
        $avatarUrl = '/uploads/profiles/default_female.jpg';
        if ($gender === 'male') {
            $avatarUrl = '/uploads/profiles/default_male.jpg';
        }

        if (isset($_FILES['photo']) && !empty($_FILES['photo']['tmp_name'])) {
            $val = Security::validateUpload($_FILES['photo'], ['image/jpeg', 'image/png', 'image/webp'], 15 * 1024 * 1024);
            if ($val['valid']) {
                $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profiles';
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0775, true);
                }
                $ext = match ($val['mime']) {
                    'image/png' => 'png',
                    'image/webp' => 'webp',
                    default => 'jpg',
                };
                $filename = Security::randomFilename($ext);
                $target = $uploadDir . '/' . $filename;
                if (move_uploaded_file($_FILES['photo']['tmp_name'], $target) || copy($_FILES['photo']['tmp_name'], $target)) {
                    $avatarUrl = '/uploads/profiles/' . $filename;
                }
            }
        }

        // 1. Create real user account for bot
        $email = 'bot_' . bin2hex(random_bytes(6)) . '@milan.internal';
        $passwordHash = Security::hashPassword(bin2hex(random_bytes(16)));

        $userId = Database::insert(
            "INSERT INTO users (email, password_hash, status, is_admin, email_verified_at, last_active_at, created_at)
             VALUES (:email, :pwd, 'active', 0, NOW(), NOW(), NOW())",
            [
                ':email' => $email,
                ':pwd' => $passwordHash,
            ]
        );

        // 2. Create real user profile
        $lookingFor = ($gender === 'female') ? 'men' : 'women';
        Database::insert(
            "INSERT INTO user_profiles (user_id, name, age, gender, looking_for, city, bio, primary_photo, is_verified, kyc_status, created_at)
             VALUES (:uid, :name, :age, :gender, :lf, :city, :bio, :photo, 1, 'verified', NOW())",
            [
                ':uid' => $userId,
                ':name' => $name,
                ':age' => $age,
                ':gender' => $gender,
                ':lf' => $lookingFor,
                ':city' => $city,
                ':bio' => $bio,
                ':photo' => $avatarUrl,
            ]
        );

        // 3. Create Admin Bot record
        $botId = Database::insert(
            "INSERT INTO admin_bots (user_id, name, avatar_url, is_active, created_at)
             VALUES (:uid, :name, :photo, 1, NOW())",
            [
                ':uid' => $userId,
                ':name' => $name,
                ':photo' => $avatarUrl,
            ]
        );

        // 4. If initial message provided, save to admin_bot_messages
        if (!empty($initialMessage)) {
            Database::insert(
                "INSERT INTO admin_bot_messages (bot_id, message_order, delay_minutes, message_text, created_at)
                 VALUES (:bid, 1, :delay, :msg, NOW())",
                [
                    ':bid' => $botId,
                    ':delay' => $initialDelay,
                    ':msg' => $initialMessage,
                ]
            );
        }

        AdminAuth::logAudit('create_bot', 'admin_bots', $botId, "Created bot: {$name} (User #{$userId})");
        Session::flash('success', "Admin Bot '{$name}' created successfully!");
        header('Location: /admin/bots');
        exit;
    }

    /**
     * Toggle Bot Active/Inactive Status
     */
    public function toggleBot(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/bots');
            exit;
        }

        $botId = (int)($_POST['bot_id'] ?? 0);
        $bot = Database::one("SELECT id, name, is_active FROM admin_bots WHERE id = :id", [':id' => $botId]);
        if ($bot) {
            $newStatus = $bot['is_active'] ? 0 : 1;
            Database::execute("UPDATE admin_bots SET is_active = :status WHERE id = :id", [':status' => $newStatus, ':id' => $botId]);
            AdminAuth::logAudit('toggle_bot', 'admin_bots', $botId, "Toggled bot {$bot['name']} active to {$newStatus}");
            Session::flash('success', "Bot '{$bot['name']}' status updated.");
        }

        header('Location: /admin/bots');
        exit;
    }

    /**
     * Delete Bot and all related data
     */
    public function deleteBot(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/bots');
            exit;
        }

        $botId = (int)($_POST['bot_id'] ?? 0);
        $bot = Database::one("SELECT id, user_id, name FROM admin_bots WHERE id = :id", [':id' => $botId]);
        if ($bot) {
            // Delete user account which cascades to profiles, messages, bot records
            Database::execute("DELETE FROM users WHERE id = :uid", [':uid' => $bot['user_id']]);
            Database::execute("DELETE FROM admin_bots WHERE id = :id", [':id' => $botId]);
            AdminAuth::logAudit('delete_bot', 'admin_bots', $botId, "Deleted bot: {$bot['name']}");
            Session::flash('success', "Bot '{$bot['name']}' and its data deleted.");
        }

        header('Location: /admin/bots');
        exit;
    }

    /**
     * Add predefined message to bot sequence
     */
    public function addBotMessage(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/bots');
            exit;
        }

        $botId = (int)($_POST['bot_id'] ?? 0);
        $delayMinutes = max(0, (int)($_POST['delay_minutes'] ?? 5));
        $messageText = trim($_POST['message_text'] ?? '');

        if ($botId <= 0 || empty($messageText)) {
            Session::flash('error', 'Message text and valid bot are required.');
            header('Location: /admin/bots');
            exit;
        }

        $maxOrder = (int)(Database::one("SELECT MAX(message_order) as m FROM admin_bot_messages WHERE bot_id = :bid", [':bid' => $botId])['m'] ?? 0);
        $newOrder = $maxOrder + 1;

        Database::insert(
            "INSERT INTO admin_bot_messages (bot_id, message_order, delay_minutes, message_text, created_at)
             VALUES (:bid, :ord, :delay, :txt, NOW())",
            [
                ':bid' => $botId,
                ':ord' => $newOrder,
                ':delay' => $delayMinutes,
                ':txt' => $messageText,
            ]
        );

        Session::flash('success', 'Predefined message added to bot sequence.');
        header('Location: /admin/bots');
        exit;
    }

    /**
     * Delete predefined bot message
     */
    public function deleteBotMessage(): void {
        $this->ensureSuperAdmin();

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /admin/bots');
            exit;
        }

        $msgId = (int)($_POST['message_id'] ?? 0);
        Database::execute("DELETE FROM admin_bot_messages WHERE id = :id", [':id' => $msgId]);

        Session::flash('success', 'Predefined message removed.');
        header('Location: /admin/bots');
        exit;
    }

    /**
     * Authorization Guard: Strictly Super Admin Only
     */
    private function ensureSuperAdmin(): void {
        if (!AdminAuth::check() || !AdminAuth::admin()) {
            AdminAuth::logout();
            header('Location: /admin/login');
            exit;
        }
    }
}
