-- Migration: Safely Remove Audio/Voice Messages & Add Bot System Control + Cron Logs
-- Date: 2026-09-21

-- 1. Create safe backup table for existing audio messages
CREATE TABLE IF NOT EXISTS messages_audio_backup AS 
SELECT id, conversation_id, sender_id, receiver_id, type, body, media_url, duration_seconds, created_at 
FROM messages WHERE type = 'voice' OR duration_seconds > 0;

-- 2. Archive any legacy voice records to text safely
UPDATE messages SET type = 'text', body = COALESCE(body, '[Voice message archived]') WHERE type = 'voice';

-- 3. Modify messages type enum to remove 'voice'
ALTER TABLE messages MODIFY COLUMN type ENUM('text', 'image') NOT NULL DEFAULT 'text';

-- 4. Drop duration_seconds column used exclusively for voice messages
ALTER TABLE messages DROP COLUMN IF EXISTS duration_seconds;

-- 5. Master setting for Bot System
INSERT INTO settings (setting_key, setting_value) VALUES ('bot_system_enabled', '1')
ON DUPLICATE KEY UPDATE setting_value = setting_value;

-- 6. Table for recording bot cron runs, execution times, and errors
CREATE TABLE IF NOT EXISTS bot_cron_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    status ENUM('success', 'disabled', 'error', 'skipped') NOT NULL DEFAULT 'success',
    messages_sent INT UNSIGNED NOT NULL DEFAULT 0,
    users_processed INT UNSIGNED NOT NULL DEFAULT 0,
    duration_ms INT UNSIGNED NOT NULL DEFAULT 0,
    log_output TEXT NULL,
    executed_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
