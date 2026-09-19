<?php

namespace App\Helpers;

class AdminAuth {
    public static function check(): bool {
        return self::id() !== null;
    }

    public static function id(): ?int {
        $aid = Session::get('admin_id');
        return $aid ? (int)$aid : null;
    }

    public static function login(int $adminId): void {
        Session::regenerate();
        Session::set('admin_id', $adminId);
        Session::set('admin_logged_in_at', time());
    }

    public static function logout(): void {
        Session::remove('admin_id');
        Session::remove('admin_logged_in_at');
    }

    public static function admin(): ?array {
        $id = self::id();
        if (!$id) {
            return null;
        }

        return Database::one(
            "SELECT id, username, email, role, created_at FROM admin_users WHERE id = :id AND role = 'super_admin' LIMIT 1",
            [':id' => $id]
        );
    }

    public static function logAudit(string $action, string $entityType, ?int $entityId = null, ?string $details = null): void {
        $adminId = self::id();
        $ip = RateLimiter::getClientIp();
        Database::execute(
            "INSERT INTO audit_logs (admin_id, action, entity_type, entity_id, details, ip_address) 
             VALUES (:admin_id, :action, :entity_type, :entity_id, :details, :ip)",
            [
                ':admin_id' => $adminId,
                ':action' => $action,
                ':entity_type' => $entityType,
                ':entity_id' => $entityId,
                ':details' => $details,
                ':ip' => $ip,
            ]
        );
    }
}
