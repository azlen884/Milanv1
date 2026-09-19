<?php

namespace App\Helpers;

use PDO;
use PDOException;
use PDOStatement;

class Database {
    private static ?\PDO $instance = null;

    /**
     * Safe startup and runtime diagnostic to verify that native PHP PDO
     * and the MySQL PDO driver (pdo_mysql) are loaded in the current PHP environment.
     *
     * @throws \RuntimeException If PDO or pdo_mysql is unavailable.
     */
    public static function checkDriverAvailability(): void {
        if (!class_exists(\PDO::class, false) && !extension_loaded('pdo')) {
            $msg = 'Critical Server Configuration Error: Native PHP PDO extension is not loaded in the active PHP runtime. '
                 . 'Please enable "pdo" (e.g. extension=pdo.so) in your active php.ini and restart the web server/PHP process.';
            error_log('[Server Configuration Error] ' . $msg);
            throw new \RuntimeException($msg);
        }

        $availableDrivers = class_exists(\PDO::class, false) ? \PDO::getAvailableDrivers() : [];
        if (!extension_loaded('pdo_mysql') && !in_array('mysql', $availableDrivers, true)) {
            $msg = 'Critical Server Configuration Error: Native PHP PDO MySQL driver (pdo_mysql) is not loaded in the active PHP runtime. '
                 . 'Please enable "pdo_mysql" (e.g. extension=pdo_mysql.so) in your active php.ini and restart the web server/PHP process.';
            error_log('[Server Configuration Error] ' . $msg);
            throw new \RuntimeException($msg);
        }
    }

    /**
     * Connect to the database using native PHP PDO with MySQL driver.
     *
     * @return \PDO
     */
    public static function connect(): \PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        // 1. Diagnostic: Verify native PDO and pdo_mysql driver availability
        self::checkDriverAvailability();

        // 2. Load configuration from environment/config system
        $config = require dirname(__DIR__, 2) . '/config/app.php';
        $db = $config['db'] ?? [];

        $host = $db['host'] ?? '127.0.0.1';
        $port = (int)($db['port'] ?? 3306);
        $dbname = $db['database'] ?? 'dating_db';
        $username = $db['username'] ?? '';
        $password = $db['password'] ?? '';

        $dsn = sprintf(
            'mysql:host=%s;port=%d;dbname=%s;charset=utf8mb4',
            $host,
            $port,
            $dbname
        );

        $options = [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
            \PDO::ATTR_EMULATE_PREPARES => true,
            \PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci",
        ];

        try {
            self::$instance = new \PDO($dsn, $username, $password, $options);
            return self::$instance;
        } catch (\PDOException $e) {
            // Log error securely without exposing credentials/passwords
            error_log('Database Connection Failure [Code ' . $e->getCode() . ']: ' . $e->getMessage());

            if (php_sapi_name() === 'cli') {
                fwrite(STDERR, "[CRITICAL] Database connection failed: " . $e->getMessage() . "\n");
                throw $e;
            }

            if (!headers_sent()) {
                http_response_code(500);
            }
            echo '<!DOCTYPE html>
            <html lang="en">
            <head>
                <meta charset="UTF-8">
                <meta name="viewport" content="width=device-width, initial-scale=1.0">
                <title>Database Service Unavailable | Milan Dating</title>
                <script src="https://cdn.tailwindcss.com"></script>
            </head>
            <body class="bg-slate-50 min-h-screen flex items-center justify-center p-4">
                <div class="max-w-md w-full bg-white rounded-3xl p-8 border border-slate-200 text-center shadow-sm">
                    <div class="w-14 h-14 bg-rose-100 text-rose-600 rounded-2xl flex items-center justify-center mx-auto mb-4 font-bold text-xl">!</div>
                    <h1 class="text-xl font-bold text-slate-900 mb-2">Database Service Unavailable</h1>
                    <p class="text-xs text-slate-500 leading-relaxed mb-4">The application could not establish a connection to the database. Please verify database server status and configuration.</p>
                </div>
            </body>
            </html>';
            exit(1);
        }
    }

    /**
     * Execute a prepared SELECT query and fetch all matching records.
     */
    public static function query(string $sql, array $params = []): array {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    /**
     * Execute a prepared SELECT query and fetch a single record.
     */
    public static function one(string $sql, array $params = []): ?array {
        $stmt = self::connect()->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch();
        return $result ?: null;
    }

    /**
     * Execute an INSERT, UPDATE, or DELETE query with prepared statements.
     */
    public static function execute(string $sql, array $params = []): bool {
        $stmt = self::connect()->prepare($sql);
        return $stmt->execute($params);
    }

    /**
     * Execute an INSERT query with prepared statements and return the last inserted ID.
     */
    public static function insert(string $sql, array $params = []): int {
        $pdo = self::connect();
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        return (int)$pdo->lastInsertId();
    }

    /**
     * Get the last inserted auto-increment ID.
     */
    public static function lastInsertId(): int {
        return (int)self::connect()->lastInsertId();
    }

    /**
     * Begin a database transaction.
     */
    public static function beginTransaction(): bool {
        return self::connect()->beginTransaction();
    }

    /**
     * Commit the active database transaction.
     */
    public static function commit(): bool {
        return self::connect()->commit();
    }

    /**
     * Roll back the active database transaction.
     */
    public static function rollBack(): bool {
        return self::connect()->rollBack();
    }

    /**
     * Check if currently within an active database transaction.
     */
    public static function inTransaction(): bool {
        return self::connect()->inTransaction();
    }
}
