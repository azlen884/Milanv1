<?php

namespace App\Helpers;

use DateTime;

class Security {
    public static function e(?string $str): string {
        return htmlspecialchars($str ?? '', ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function hashPassword(string $password): string {
        return password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);
    }

    public static function verifyPassword(string $password, string $hash): bool {
        return password_verify($password, $hash);
    }

    public static function calculateAge(string $dob): int {
        try {
            $birthDate = new DateTime($dob);
            $today = new DateTime('today');
            return (int)$birthDate->diff($today)->y;
        } catch (\Exception $e) {
            return 0;
        }
    }

    public static function isAtLeast18(string $dob): bool {
        return self::calculateAge($dob) >= 18;
    }

    public static function sanitize(string $input): string {
        return trim(strip_tags($input));
    }

    public static function randomFilename(string $extension): string {
        $cleanExt = preg_replace('/[^a-zA-Z0-9]/', '', strtolower($extension));
        return bin2hex(random_bytes(16)) . '_' . time() . '.' . $cleanExt;
    }

    public static function validateUpload(array $file, array $allowedMimes, int $maxBytes): array {
        if (!isset($file['error'])) {
            return ['valid' => false, 'error' => 'No upload data received.'];
        }

        if ($file['error'] !== UPLOAD_ERR_OK) {
            $msg = match ($file['error']) {
                UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'File size exceeds maximum upload limit.',
                UPLOAD_ERR_PARTIAL => 'File was only partially uploaded. Please retry.',
                UPLOAD_ERR_NO_FILE => 'No file was selected for upload.',
                UPLOAD_ERR_NO_TMP_DIR => 'Server temporary directory missing.',
                UPLOAD_ERR_CANT_WRITE => 'Failed to write file to disk.',
                default => 'File upload error (code: ' . $file['error'] . ').',
            };
            return ['valid' => false, 'error' => $msg];
        }

        if (!isset($file['tmp_name']) || empty($file['tmp_name']) || !file_exists($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Uploaded temporary file not found.'];
        }

        // Enforce is_uploaded_file in HTTP runtime (allow CLI testing)
        if (php_sapi_name() !== 'cli' && !is_uploaded_file($file['tmp_name'])) {
            return ['valid' => false, 'error' => 'Upload security verification failed.'];
        }

        $fileSize = (int)($file['size'] ?? filesize($file['tmp_name']));
        if ($fileSize <= 0) {
            return ['valid' => false, 'error' => 'Uploaded file is empty.'];
        }

        if ($fileSize > $maxBytes) {
            $maxMb = round($maxBytes / (1024 * 1024), 1);
            return ['valid' => false, 'error' => "File size exceeds the maximum limit of {$maxMb}MB."];
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        // Normalize image MIME types
        $mimeMap = [
            'image/jpg' => 'image/jpeg',
            'image/pjpeg' => 'image/jpeg',
            'image/x-png' => 'image/png',
        ];
        if (isset($mimeMap[$mime])) {
            $mime = $mimeMap[$mime];
        }

        // Additional image validation for image uploads
        if (str_starts_with($mime, 'image/') || in_array('image/jpeg', $allowedMimes, true)) {
            $imageInfo = @getimagesize($file['tmp_name']);
            if ($imageInfo !== false && !empty($imageInfo['mime'])) {
                $detectedMime = $imageInfo['mime'];
                if (isset($mimeMap[$detectedMime])) {
                    $detectedMime = $mimeMap[$detectedMime];
                }
                $mime = $detectedMime;
            } elseif (in_array('image/jpeg', $allowedMimes, true) && !in_array('application/pdf', $allowedMimes, true)) {
                return ['valid' => false, 'error' => 'The uploaded file is not a valid or readable image.'];
            }
        }

        if (!in_array($mime, $allowedMimes, true)) {
            $typeDesc = 'JPEG, PNG, and WebP images';
            if (in_array('application/pdf', $allowedMimes, true)) {
                $typeDesc .= ' or PDF documents';
            }
            return ['valid' => false, 'error' => "Invalid file format ({$mime}). Only valid {$typeDesc} are allowed."];
        }

        return ['valid' => true, 'mime' => $mime];
    }
}
