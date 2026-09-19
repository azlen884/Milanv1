<?php

namespace App\Helpers;

class RateLimiter {
    public static function check(string $action, string $identifier, int $maxAttempts, int $decaySeconds): bool {
        $key = substr($action . ':' . $identifier, 0, 190);
        $now = time();

        $record = Database::one(
            "SELECT hits, reset_at FROM rate_limits WHERE key_name = :key_name AND action = :action",
            [':key_name' => $key, ':action' => $action]
        );

        if (!$record) {
            Database::execute(
                "INSERT INTO rate_limits (key_name, action, hits, reset_at) VALUES (:key, :action, 1, :reset)",
                [':key' => $key, ':action' => $action, ':reset' => $now + $decaySeconds]
            );
            return true;
        }

        if ($record['reset_at'] < $now) {
            // Window expired, reset counter
            Database::execute(
                "UPDATE rate_limits SET hits = 1, reset_at = :reset WHERE key_name = :key AND action = :action",
                [':key' => $key, ':action' => $action, ':reset' => $now + $decaySeconds]
            );
            return true;
        }

        if ($record['hits'] >= $maxAttempts) {
            return false;
        }

        Database::execute(
            "UPDATE rate_limits SET hits = hits + 1 WHERE key_name = :key AND action = :action",
            [':key' => $key, ':action' => $action]
        );

        return true;
    }

    public static function getClientIp(): string {
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'] ?? 
              $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 
              $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $parts = explode(',', $ip);
        return trim($parts[0]);
    }
}
