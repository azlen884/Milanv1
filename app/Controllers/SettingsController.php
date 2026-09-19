<?php

namespace App\Controllers;

use App\Helpers\Database;
use App\Helpers\Auth;
use App\Helpers\Security;
use App\Helpers\Session;
use App\Helpers\View;
use App\Services\KycService;

class SettingsController {
    public function index(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        $tab = $_GET['tab'] ?? 'profile';
        $userId = $user['id'];

        // Photos
        $photos = Database::query("SELECT * FROM user_photos WHERE user_id = :uid ORDER BY is_primary DESC, id ASC", [':uid' => $userId]);

        // Interests
        $allInterests = Database::query("SELECT * FROM interests ORDER BY category, name");
        $userInterestIds = array_column(
            Database::query("SELECT interest_id FROM user_interests WHERE user_id = :uid", [':uid' => $userId]),
            'interest_id'
        );

        // Dating Preferences
        $pref = Database::one("SELECT * FROM dating_preferences WHERE user_id = :uid", [':uid' => $userId]);

        // KYC Record
        $kycRecord = Database::one("SELECT * FROM kyc_records WHERE user_id = :uid ORDER BY id DESC LIMIT 1", [':uid' => $userId]);
        $kycGloballyEnabled = KycService::isGloballyEnabled();

        // Blocked Users
        $blockedUsers = Database::query(
            "SELECT b.id as block_id, b.created_at, p.user_id, p.name, p.age, p.city, p.primary_photo 
             FROM blocked_users b
             JOIN user_profiles p ON b.blocked_id = p.user_id
             WHERE b.blocker_id = :uid",
            [':uid' => $userId]
        );

        View::render('settings/index', [
            'user' => $user,
            'currentTab' => $tab,
            'photos' => $photos,
            'allInterests' => $allInterests,
            'userInterestIds' => $userInterestIds,
            'preferences' => $pref,
            'kycRecord' => $kycRecord,
            'kycGloballyEnabled' => $kycGloballyEnabled,
            'blockedUsers' => $blockedUsers,
        ], 'main');
    }

    public function updateProfile(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=profile');
            exit;
        }

        $name = Security::sanitize($_POST['name'] ?? '');
        $city = Security::sanitize($_POST['city'] ?? '');
        $bio = Security::sanitize($_POST['bio'] ?? '');
        $occupation = Security::sanitize($_POST['occupation'] ?? '');
        $education = Security::sanitize($_POST['education'] ?? '');
        $languages = Security::sanitize($_POST['languages'] ?? '');
        $height = !empty($_POST['height_cm']) ? (int)$_POST['height_cm'] : null;

        if (empty($name) || empty($city)) {
            Session::flash('error', 'Name and city are required.');
            header('Location: /settings?tab=profile');
            exit;
        }

        Database::execute(
            "UPDATE user_profiles 
             SET name = :name, city = :city, bio = :bio, occupation = :occ, education = :edu, languages = :lang, height_cm = :ht
             WHERE user_id = :uid",
            [
                ':name' => $name,
                ':city' => $city,
                ':bio' => $bio,
                ':occ' => $occupation,
                ':edu' => $education,
                ':lang' => $languages,
                ':ht' => $height,
                ':uid' => $user['id'],
            ]
        );

        Session::flash('success', 'Profile updated successfully.');
        header('Location: /settings?tab=profile');
        exit;
    }

    public function uploadPhoto(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required. Please sign in.'], 401);
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($csrfToken)) {
            View::json(['success' => false, 'error' => 'Security token expired. Please refresh the page.'], 403);
        }

        if (!isset($_FILES['photo']) || !is_array($_FILES['photo'])) {
            View::json(['success' => false, 'error' => 'No photo file was provided in the upload request.'], 400);
        }

        $file = $_FILES['photo'];
        $val = Security::validateUpload($file, ['image/jpeg', 'image/png', 'image/webp'], 5 * 1024 * 1024);
        if (!$val['valid']) {
            View::json(['success' => false, 'error' => $val['error']], 400);
        }

        // Limit user to maximum 9 profile photos
        $existingCount = (int)(Database::one("SELECT COUNT(*) as c FROM user_photos WHERE user_id = :uid", [':uid' => $user['id']])['c'] ?? 0);
        if ($existingCount >= 9) {
            View::json(['success' => false, 'error' => 'You have reached the limit of 9 photos. Please delete an older photo to upload a new one.'], 400);
        }

        $uploadDir = dirname(__DIR__, 2) . '/public/uploads/profiles';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0775, true);
        }

        $rawExt = pathinfo($file['name'] ?? '', PATHINFO_EXTENSION);
        $cleanExt = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $rawExt));
        if (empty($cleanExt)) {
            $cleanExt = match ($val['mime']) {
                'image/png' => 'png',
                'image/webp' => 'webp',
                default => 'jpg',
            };
        }

        $filename = Security::randomFilename($cleanExt);
        $target = $uploadDir . '/' . $filename;

        $saved = false;
        if (is_uploaded_file($file['tmp_name'])) {
            $saved = move_uploaded_file($file['tmp_name'], $target);
        } elseif (php_sapi_name() === 'cli' && file_exists($file['tmp_name'])) {
            $saved = copy($file['tmp_name'], $target);
        }

        if (!$saved) {
            View::json(['success' => false, 'error' => 'Failed to write photo to storage directory. Check folder permissions.'], 500);
        }

        $photoUrl = '/uploads/profiles/' . $filename;
        $hasCurrentPrimary = !empty($user['primary_photo']) && $existingCount > 0;
        $isPrimary = $hasCurrentPrimary ? 0 : 1;

        $photoId = Database::insert(
            "INSERT INTO user_photos (user_id, photo_url, is_primary) VALUES (:uid, :url, :pri)",
            [':uid' => $user['id'], ':url' => $photoUrl, ':pri' => $isPrimary]
        );

        if ($isPrimary) {
            Database::execute(
                "UPDATE user_profiles SET primary_photo = :url WHERE user_id = :uid",
                [':url' => $photoUrl, ':uid' => $user['id']]
            );
        }

        View::json([
            'success' => true,
            'photo_id' => $photoId,
            'photo_url' => $photoUrl,
            'is_primary' => (bool)$isPrimary,
            'message' => 'Photo uploaded successfully!'
        ]);
    }

    public function deletePhoto(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($csrfToken)) {
            View::json(['success' => false, 'error' => 'Security token expired.'], 403);
        }

        $photoId = (int)($_POST['photo_id'] ?? 0);
        $photo = Database::one(
            "SELECT * FROM user_photos WHERE id = :id AND user_id = :uid",
            [':id' => $photoId, ':uid' => $user['id']]
        );

        if (!$photo) {
            View::json(['success' => false, 'error' => 'Photo not found.'], 404);
        }

        // Delete physical file safely
        $filePath = dirname(__DIR__, 2) . '/public' . $photo['photo_url'];
        if (file_exists($filePath) && is_file($filePath)) {
            @unlink($filePath);
        }
        Database::execute("DELETE FROM user_photos WHERE id = :id", [':id' => $photoId]);

        // If deleted photo was primary, elect the next available photo as primary
        if ($photo['is_primary']) {
            $next = Database::one("SELECT id, photo_url FROM user_photos WHERE user_id = :uid ORDER BY id ASC LIMIT 1", [':uid' => $user['id']]);
            if ($next) {
                Database::execute("UPDATE user_photos SET is_primary = 1 WHERE id = :id", [':id' => $next['id']]);
                Database::execute("UPDATE user_profiles SET primary_photo = :url WHERE user_id = :uid", [':url' => $next['photo_url'], ':uid' => $user['id']]);
            } else {
                Database::execute("UPDATE user_profiles SET primary_photo = NULL WHERE user_id = :uid", [':uid' => $user['id']]);
            }
        }

        View::json(['success' => true, 'message' => 'Photo deleted successfully.']);
    }

    public function setPrimaryPhoto(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Session::verifyCsrf($csrfToken)) {
            View::json(['success' => false, 'error' => 'Security token expired.'], 403);
        }

        $photoId = (int)($_POST['photo_id'] ?? 0);
        $photo = Database::one(
            "SELECT * FROM user_photos WHERE id = :id AND user_id = :uid",
            [':id' => $photoId, ':uid' => $user['id']]
        );

        if (!$photo) {
            View::json(['success' => false, 'error' => 'Photo not found.'], 404);
        }

        Database::execute("UPDATE user_photos SET is_primary = 0 WHERE user_id = :uid", [':uid' => $user['id']]);
        Database::execute("UPDATE user_photos SET is_primary = 1 WHERE id = :id", [':id' => $photoId]);
        Database::execute("UPDATE user_profiles SET primary_photo = :url WHERE user_id = :uid", [':url' => $photo['photo_url'], ':uid' => $user['id']]);

        View::json(['success' => true, 'message' => 'Primary photo updated.', 'photo_url' => $photo['photo_url']]);
    }

    public function updateInterests(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=interests');
            exit;
        }

        $selected = $_POST['interests'] ?? [];
        Database::execute("DELETE FROM user_interests WHERE user_id = :uid", [':uid' => $user['id']]);

        foreach ($selected as $intId) {
            $intId = (int)$intId;
            if ($intId > 0) {
                Database::execute(
                    "INSERT IGNORE INTO user_interests (user_id, interest_id) VALUES (:uid, :iid)",
                    [':uid' => $user['id'], ':iid' => $intId]
                );
            }
        }

        Session::flash('success', 'Interests updated.');
        header('Location: /settings?tab=interests');
        exit;
    }

    public function updatePreferences(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=preferences');
            exit;
        }

        $gender = $_POST['interested_in_gender'] ?? 'all';
        $ageMin = max(18, (int)($_POST['age_min'] ?? 18));
        $ageMax = min(70, (int)($_POST['age_max'] ?? 55));
        $city = Security::sanitize($_POST['city_preference'] ?? '');

        Database::execute(
            "INSERT INTO dating_preferences (user_id, interested_in_gender, age_min, age_max, city_preference)
             VALUES (:uid, :g, :amin, :amax, :city)
             ON DUPLICATE KEY UPDATE interested_in_gender = :g, age_min = :amin, age_max = :amax, city_preference = :city",
            [
                ':uid' => $user['id'],
                ':g' => $gender,
                ':amin' => $ageMin,
                ':amax' => $ageMax,
                ':city' => $city,
            ]
        );

        Session::flash('success', 'Preferences saved.');
        header('Location: /settings?tab=preferences');
        exit;
    }

    public function updatePrivacy(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=privacy');
            exit;
        }

        $optOutVisitors = !empty($_POST['opt_out_visitors']) ? 1 : 0;
        $isIncognito = !empty($_POST['is_incognito']) ? 1 : 0;

        Database::execute(
            "UPDATE user_profiles SET opt_out_visitors = :opt, is_incognito = :inc WHERE user_id = :uid",
            [':opt' => $optOutVisitors, ':inc' => $isIncognito, ':uid' => $user['id']]
        );

        Session::flash('success', 'Privacy settings updated.');
        header('Location: /settings?tab=privacy');
        exit;
    }

    public function submitKyc(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=kyc');
            exit;
        }

        $docType = $_POST['doc_type'] ?? 'aadhaar';
        $docFile = $_FILES['doc_file'] ?? null;
        $selfieFile = $_FILES['selfie_file'] ?? null;

        if (!$docFile || !$selfieFile) {
            Session::flash('error', 'Both ID document and selfie photo are required.');
            header('Location: /settings?tab=kyc');
            exit;
        }

        $result = KycService::submitKyc($user['id'], $docType, $docFile, $selfieFile);
        if ($result['success']) {
            Session::flash('success', $result['message']);
        } else {
            Session::flash('error', $result['error']);
        }

        header('Location: /settings?tab=kyc');
        exit;
    }

    public function unblock(): void {
        $user = Auth::user();
        if (!$user) {
            View::json(['success' => false, 'error' => 'Authentication required.'], 401);
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            View::json(['success' => false, 'error' => 'Invalid security token.'], 403);
        }

        $targetId = (int)($_POST['target_id'] ?? 0);
        Database::execute(
            "DELETE FROM blocked_users WHERE blocker_id = :uid AND blocked_id = :tar",
            [':uid' => $user['id'], ':tar' => $targetId]
        );

        View::json(['success' => true]);
    }

    public function updatePassword(): void {
        $user = Auth::user();
        if (!$user) {
            header('Location: /login');
            exit;
        }

        if (!Session::verifyCsrf($_POST['csrf_token'] ?? '')) {
            Session::flash('error', 'Invalid security token.');
            header('Location: /settings?tab=security');
            exit;
        }

        $currentPass = $_POST['current_password'] ?? '';
        $newPass = $_POST['new_password'] ?? '';
        $confirmPass = $_POST['confirm_password'] ?? '';

        $record = Database::one("SELECT password_hash FROM users WHERE id = :id", [':id' => $user['id']]);
        if (!$record || !Security::verifyPassword($currentPass, $record['password_hash'])) {
            Session::flash('error', 'Incorrect current password.');
            header('Location: /settings?tab=security');
            exit;
        }

        if (strlen($newPass) < 8) {
            Session::flash('error', 'New password must be at least 8 characters long.');
            header('Location: /settings?tab=security');
            exit;
        }

        if ($newPass !== $confirmPass) {
            Session::flash('error', 'New passwords do not match.');
            header('Location: /settings?tab=security');
            exit;
        }

        $newHash = Security::hashPassword($newPass);
        Database::execute("UPDATE users SET password_hash = :hash WHERE id = :id", [':hash' => $newHash, ':id' => $user['id']]);

        Session::flash('success', 'Password updated successfully.');
        header('Location: /settings?tab=security');
        exit;
    }
}
