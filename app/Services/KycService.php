<?php

namespace App\Services;

use App\Helpers\Database;
use App\Helpers\Security;
use App\Helpers\AdminAuth;

class KycService {
    public static function isGloballyEnabled(): bool {
        $setting = Database::one("SELECT setting_value FROM settings WHERE setting_key = 'kyc_globally_enabled'");
        return !empty($setting['setting_value']);
    }

    public static function submitKyc(int $userId, string $docType, array $docFile, array $selfieFile): array {
        if (!self::isGloballyEnabled()) {
            return ['success' => false, 'error' => 'KYC verification is currently disabled by administrator.'];
        }

        // Validate doc file
        $allowedMimes = ['image/jpeg', 'image/png', 'image/webp', 'application/pdf'];
        $valDoc = Security::validateUpload($docFile, $allowedMimes, 8 * 1024 * 1024);
        if (!$valDoc['valid']) {
            return ['success' => false, 'error' => 'ID Document: ' . $valDoc['error']];
        }

        // Validate selfie
        $valSelfie = Security::validateUpload($selfieFile, ['image/jpeg', 'image/png', 'image/webp'], 8 * 1024 * 1024);
        if (!$valSelfie['valid']) {
            return ['success' => false, 'error' => 'Selfie Photo: ' . $valSelfie['error']];
        }

        // Store privately in protected storage/kyc/
        $storageDir = dirname(__DIR__, 2) . '/storage/kyc';
        if (!is_dir($storageDir)) {
            mkdir($storageDir, 0750, true);
        }

        $docExt = pathinfo($docFile['name'], PATHINFO_EXTENSION);
        $selfieExt = pathinfo($selfieFile['name'], PATHINFO_EXTENSION);

        $docFilename = Security::randomFilename($docExt);
        $selfieFilename = Security::randomFilename($selfieExt);

        if (!move_uploaded_file($docFile['tmp_name'], $storageDir . '/' . $docFilename)) {
            return ['success' => false, 'error' => 'Failed to save ID document securely.'];
        }

        if (!move_uploaded_file($selfieFile['tmp_name'], $storageDir . '/' . $selfieFilename)) {
            @unlink($storageDir . '/' . $docFilename);
            return ['success' => false, 'error' => 'Failed to save selfie photo securely.'];
        }

        // Insert or update KYC record
        Database::execute(
            "INSERT INTO kyc_records (user_id, doc_type, doc_file_path, selfie_file_path, status, submitted_at)
             VALUES (:uid, :type, :doc, :selfie, 'pending', NOW())",
            [
                ':uid' => $userId,
                ':type' => Security::sanitize($docType),
                ':doc' => $docFilename,
                ':selfie' => $selfieFilename,
            ]
        );

        // Update profile status
        Database::execute(
            "UPDATE user_profiles SET kyc_status = 'pending' WHERE user_id = :uid",
            [':uid' => $userId]
        );

        return ['success' => true, 'message' => 'Your verification documents have been submitted securely for review.'];
    }

    public static function reviewKyc(int $recordId, string $status, ?string $adminNotes = null): bool {
        if (!in_array($status, ['verified', 'rejected', 'resubmission'], true)) {
            return false;
        }

        $record = Database::one("SELECT * FROM kyc_records WHERE id = :id", [':id' => $recordId]);
        if (!$record) {
            return false;
        }

        $adminId = AdminAuth::id();

        Database::execute(
            "UPDATE kyc_records 
             SET status = :status, admin_notes = :notes, reviewed_by = :aid, reviewed_at = NOW() 
             WHERE id = :id",
            [
                ':status' => $status,
                ':notes' => $adminNotes,
                ':aid' => $adminId,
                ':id' => $recordId,
            ]
        );

        // Update user profile
        Database::execute(
            "UPDATE user_profiles SET kyc_status = :status WHERE user_id = :uid",
            [':status' => $status, ':uid' => $record['user_id']]
        );

        // Notify user
        $notifBody = match ($status) {
            'verified' => 'Congratulations! Your profile has been verified. The Verified badge is now active on your profile.',
            'resubmission' => 'Your verification requires resubmission: ' . ($adminNotes ?? 'Please upload clearer documents.'),
            'rejected' => 'Your verification was declined: ' . ($adminNotes ?? 'Documents did not meet criteria.'),
        };

        Database::insert(
            "INSERT INTO notifications (user_id, type, title, body, link) 
             VALUES (:uid, 'kyc', 'Verification Update', :body, '/settings/kyc')",
            [':uid' => $record['user_id'], ':body' => $notifBody]
        );

        // Audit Log
        AdminAuth::logAudit(
            "kyc_{$status}",
            'kyc_records',
            $recordId,
            "KYC record #{$recordId} for User #{$record['user_id']} marked as {$status}. Note: {$adminNotes}"
        );

        return true;
    }
}
