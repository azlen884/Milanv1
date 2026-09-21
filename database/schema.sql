-- Milan Dating - Plain PHP & MySQL Schema
-- Strictly Real Data Architecture

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS rate_limits;
DROP TABLE IF EXISTS audit_logs;
DROP TABLE IF EXISTS settings;
DROP TABLE IF EXISTS landing_marketing_profiles;
DROP TABLE IF EXISTS notifications;
DROP TABLE IF EXISTS boosts;
DROP TABLE IF EXISTS profile_visitors;
DROP TABLE IF EXISTS blocked_users;
DROP TABLE IF EXISTS reports;
DROP TABLE IF EXISTS kyc_records;
DROP TABLE IF EXISTS payments;
DROP TABLE IF EXISTS subscriptions;
DROP TABLE IF EXISTS subscription_plans;
DROP TABLE IF EXISTS messages;
DROP TABLE IF EXISTS conversations;
DROP TABLE IF EXISTS matches;
DROP TABLE IF EXISTS user_interactions;
DROP TABLE IF EXISTS user_interests;
DROP TABLE IF EXISTS interests;
DROP TABLE IF EXISTS dating_preferences;
DROP TABLE IF EXISTS user_photos;
DROP TABLE IF EXISTS user_profiles;
DROP TABLE IF EXISTS users;
DROP TABLE IF EXISTS admin_users;

-- Admin Users (Super Admin Only)
CREATE TABLE admin_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('super_admin') NOT NULL DEFAULT 'super_admin',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Users Table
CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(191) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    status ENUM('active', 'suspended', 'banned') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    last_active_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user_status (status),
    INDEX idx_user_last_active (last_active_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Profiles
CREATE TABLE user_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    dob DATE NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    city VARCHAR(100) NOT NULL,
    bio TEXT NULL,
    occupation VARCHAR(100) NULL,
    education VARCHAR(100) NULL,
    height_cm INT NULL,
    primary_photo VARCHAR(255) NULL,
    languages VARCHAR(255) DEFAULT 'Hindi, English',
    kyc_status ENUM('not_submitted', 'pending', 'verified', 'rejected', 'resubmission') NOT NULL DEFAULT 'not_submitted',
    opt_out_visitors TINYINT(1) NOT NULL DEFAULT 0,
    is_incognito TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_profile_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_profile_gender (gender),
    INDEX idx_profile_city (city),
    INDEX idx_profile_age (age),
    INDEX idx_profile_kyc (kyc_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Photos
CREATE TABLE user_photos (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    is_primary TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_photos_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Dating Preferences
CREATE TABLE dating_preferences (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL UNIQUE,
    interested_in_gender ENUM('all', 'male', 'female', 'other') NOT NULL DEFAULT 'all',
    age_min INT NOT NULL DEFAULT 18,
    age_max INT NOT NULL DEFAULT 55,
    city_preference VARCHAR(100) NULL,
    CONSTRAINT fk_pref_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Interests Master Table
CREATE TABLE interests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    category VARCHAR(50) NOT NULL DEFAULT 'Lifestyle'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Interests Pivot
CREATE TABLE user_interests (
    user_id INT UNSIGNED NOT NULL,
    interest_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (user_id, interest_id),
    CONSTRAINT fk_ui_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_ui_interest FOREIGN KEY (interest_id) REFERENCES interests (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- User Interactions (Interest Sent / Received)
CREATE TABLE user_interactions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    status ENUM('pending', 'accepted', 'rejected') NOT NULL DEFAULT 'pending',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_interaction (sender_id, receiver_id),
    CONSTRAINT fk_interact_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_interact_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Matches (Created ONLY upon mutual interests)
CREATE TABLE matches (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user1_id INT UNSIGNED NOT NULL,
    user2_id INT UNSIGNED NOT NULL,
    matched_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_match_pair (user1_id, user2_id),
    CONSTRAINT fk_match_u1 FOREIGN KEY (user1_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_match_u2 FOREIGN KEY (user2_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Conversations
CREATE TABLE conversations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user1_id INT UNSIGNED NOT NULL,
    user2_id INT UNSIGNED NOT NULL,
    last_message_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_conv_pair (user1_id, user2_id),
    CONSTRAINT fk_conv_u1 FOREIGN KEY (user1_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_conv_u2 FOREIGN KEY (user2_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_conv_last_msg (last_message_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Messages
CREATE TABLE messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    conversation_id INT UNSIGNED NOT NULL,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    type ENUM('text', 'image') NOT NULL DEFAULT 'text',
    body TEXT NULL,
    media_url VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_msg_conv FOREIGN KEY (conversation_id) REFERENCES conversations (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_sender FOREIGN KEY (sender_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_msg_receiver FOREIGN KEY (receiver_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_msg_created (created_at),
    INDEX idx_msg_read (receiver_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscription Plans Master Table
CREATE TABLE subscription_plans (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    code VARCHAR(50) NOT NULL UNIQUE,
    name VARCHAR(100) NOT NULL,
    price_inr INT NOT NULL,
    duration_days INT NOT NULL,
    daily_messages_limit INT NOT NULL DEFAULT 4, -- 4 for Free, 0 = unlimited for paid
    can_view_visitors TINYINT(1) NOT NULL DEFAULT 0,
    includes_boost TINYINT(1) NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    description VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Subscriptions Table
CREATE TABLE subscriptions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active', 'expired', 'cancelled') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_sub_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_sub_plan FOREIGN KEY (plan_id) REFERENCES subscription_plans (id) ON DELETE RESTRICT,
    INDEX idx_sub_status (user_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Payments Table (Real Razorpay)
CREATE TABLE payments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan_id INT UNSIGNED NULL,
    payment_type ENUM('subscription', 'boost') NOT NULL DEFAULT 'subscription',
    razorpay_order_id VARCHAR(100) NOT NULL,
    razorpay_payment_id VARCHAR(100) NULL UNIQUE,
    razorpay_signature VARCHAR(255) NULL,
    amount_paisa INT NOT NULL,
    currency VARCHAR(10) NOT NULL DEFAULT 'INR',
    status ENUM('created', 'authorized', 'captured', 'failed', 'refunded') NOT NULL DEFAULT 'created',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_pay_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_pay_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- KYC Records Table
CREATE TABLE kyc_records (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    doc_type VARCHAR(50) NOT NULL,
    doc_file_path VARCHAR(255) NOT NULL,
    selfie_file_path VARCHAR(255) NOT NULL,
    status ENUM('pending', 'verified', 'rejected', 'resubmission') NOT NULL DEFAULT 'pending',
    admin_notes TEXT NULL,
    reviewed_by INT UNSIGNED NULL,
    reviewed_at DATETIME NULL,
    submitted_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_kyc_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_kyc_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Reports Table
CREATE TABLE reports (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    reporter_id INT UNSIGNED NOT NULL,
    reported_id INT UNSIGNED NOT NULL,
    reason ENUM('fake_profile', 'harassment', 'spam', 'scam', 'inappropriate_content', 'impersonation', 'other') NOT NULL,
    description TEXT NULL,
    status ENUM('open', 'investigating', 'resolved', 'dismissed') NOT NULL DEFAULT 'open',
    action_taken VARCHAR(100) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_rep_reporter FOREIGN KEY (reporter_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_rep_reported FOREIGN KEY (reported_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_report_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Blocked Users Table
CREATE TABLE blocked_users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    blocker_id INT UNSIGNED NOT NULL,
    blocked_id INT UNSIGNED NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_block (blocker_id, blocked_id),
    CONSTRAINT fk_block_blocker FOREIGN KEY (blocker_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_block_blocked FOREIGN KEY (blocked_id) REFERENCES users (id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Profile Visitors (Paid Users Only)
CREATE TABLE profile_visitors (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    profile_user_id INT UNSIGNED NOT NULL,
    visitor_user_id INT UNSIGNED NOT NULL,
    visited_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_visit_record (profile_user_id, visitor_user_id),
    CONSTRAINT fk_visit_profile FOREIGN KEY (profile_user_id) REFERENCES users (id) ON DELETE CASCADE,
    CONSTRAINT fk_visit_visitor FOREIGN KEY (visitor_user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_visit_time (visited_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Boosts Table
CREATE TABLE boosts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    starts_at DATETIME NOT NULL,
    expires_at DATETIME NOT NULL,
    status ENUM('active', 'expired') NOT NULL DEFAULT 'active',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_boost_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_boost_status (status, expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Notifications Table
CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    type VARCHAR(50) NOT NULL,
    title VARCHAR(150) NOT NULL,
    body TEXT NOT NULL,
    link VARCHAR(255) NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE,
    INDEX idx_notif_read (user_id, is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Landing Marketing Profiles (Presentation Only - Strictly isolated from real dating system)
CREATE TABLE landing_marketing_profiles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    age INT NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    city VARCHAR(100) NOT NULL,
    photo_url VARCHAR(255) NOT NULL,
    short_bio VARCHAR(255) NOT NULL,
    interests VARCHAR(255) NOT NULL,
    badge VARCHAR(50) NULL,
    display_order INT NOT NULL DEFAULT 0,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Global Settings Table
CREATE TABLE settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_key VARCHAR(100) NOT NULL UNIQUE,
    setting_value TEXT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Application Rate Limiting & Abuse Prevention
CREATE TABLE rate_limits (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key_name VARCHAR(191) NOT NULL,
    action VARCHAR(50) NOT NULL,
    hits INT NOT NULL DEFAULT 1,
    reset_at INT UNSIGNED NOT NULL,
    UNIQUE KEY uniq_rate_key (key_name, action),
    INDEX idx_rate_reset (reset_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Audit Logs Table (Admin actions)
CREATE TABLE audit_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id INT UNSIGNED NULL,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT UNSIGNED NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_audit_admin (admin_id),
    INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;

-- Seed Default Settings
INSERT INTO settings (setting_key, setting_value) VALUES
('site_name', 'Milan Dating'),
('site_tagline', 'Meaningful Connections for Modern Indians'),
('contact_email', 'support@milandating.in'),
('kyc_globally_enabled', '1'),
('boost_price_inr', '19'),
('boost_duration_hours', '24'),
('boost_globally_enabled', '1'),
('max_active_boosts_per_user', '3'),
('maintenance_mode', '0'),
('razorpay_enabled', '1'),
('razorpay_mode', 'test'),
('razorpay_key_id', 'rzp_test_1DP5mmOlF5G5ag'),
('razorpay_key_secret', 'milan_razorpay_secret_key'),
('razorpay_webhook_secret', ''),
('free_daily_messages_limit', '4'),
('min_age_requirement', '18'),
('max_search_distance_km', '100'),
('require_photo_for_discovery', '1'),
('landing_faq_json', '[]');

-- Seed Exact Subscription Plans
-- Free = ₹0 (4 messages/day)
-- Monthly = ₹299/month (unlimited messages)
-- 3 Months = ₹700/3 months (unlimited messages + included boost, saves ₹197)
INSERT INTO subscription_plans (code, name, price_inr, duration_days, daily_messages_limit, can_view_visitors, includes_boost, is_active, description) VALUES
('free', 'Free Plan', 0, 36500, 4, 0, 0, 1, 'Standard discovery with 4 daily messages'),
('monthly', 'Monthly Premium', 299, 30, 0, 1, 0, 1, 'Unlimited messaging, see profile visitors & premium badge'),
('three_months', '3-Month Premium', 700, 90, 0, 1, 1, 1, 'Unlimited messaging, visitors, included boost & save ₹197');

-- Seed Standard Indian Interests
INSERT INTO interests (name, category) VALUES
('Chai & Conversations', 'Lifestyle'),
('Bollywood & Cinema', 'Entertainment'),
('Travel & Road Trips', 'Adventure'),
('Yoga & Fitness', 'Health'),
('Street Food & Dining', 'Food'),
('Music & Concerts', 'Arts'),
('Cricket & Sports', 'Sports'),
('Tech & Startups', 'Career'),
('Reading & Literature', 'Hobbies'),
('Photography & Art', 'Creativity'),
('Classical Dance', 'Arts'),
('Cooking & Baking', 'Food');

-- Seed Default Super Admin
-- Username: admin, Password: Admin@123
-- Stored via standard bcrypt password_hash
INSERT INTO admin_users (username, email, password_hash, role) VALUES
('admin', 'admin@milandating.in', '$2y$10$QRHmpGFjjzp/Zg/FjsTrV.xCR4PhmEUGj/6hWYaPXbT2zZoHG6EZu', 'super_admin');

-- Seed Landing Marketing Profiles
-- Strictly presentation profiles for the public landing page only
INSERT INTO landing_marketing_profiles (name, age, gender, city, photo_url, short_bio, interests, badge, display_order, is_active) VALUES
('Ananya Sharma', 25, 'female', 'Mumbai', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?auto=format&fit=crop&w=800&q=80', 'Architect passionate about heritage structures, filter coffee, and weekend indie gigs in Bandra.', 'Architecture, Coffee, Music', 'Verified', 1, 1),
('Kabir Mehta', 27, 'male', 'Bengaluru', 'https://images.unsplash.com/photo-1506794778202-cad84cf45f1d?auto=format&fit=crop&w=800&q=80', 'Product designer who loves trekking in the Western Ghats and experimenting with spicy Andhra delicacies.', 'Design, Trekking, Culinary', 'Verified', 2, 1),
('Pooja Patel', 26, 'female', 'Delhi NCR', 'https://images.unsplash.com/photo-1517841905240-472988babdf9?auto=format&fit=crop&w=800&q=80', 'Corporate lawyer by day, amateur potter and book club enthusiast on weekends. Looking for genuine conversations.', 'Law, Pottery, Books', 'Lawyer', 3, 1),
('Rohan Sen', 28, 'male', 'Pune', 'https://images.unsplash.com/photo-1507003211169-0a1dd7228f2d?auto=format&fit=crop&w=800&q=80', 'Software engineer with a soul for acoustic guitar, street photography, and long highway monsoon drives.', 'Guitar, Photography, Travel', 'Verified', 4, 1);
