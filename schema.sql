-- ChillGC Tierlist Database Schema
-- Run this SQL to set up the MySQL database

CREATE DATABASE IF NOT EXISTS chillgc_tierlist CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE chillgc_tierlist;

-- Users table: stores Discord-authenticated users
CREATE TABLE IF NOT EXISTS users (
    id BIGINT UNSIGNED PRIMARY KEY,           -- Discord user ID
    username VARCHAR(100) NOT NULL,            -- Discord username
    discriminator VARCHAR(10) DEFAULT NULL,    -- Discord discriminator (legacy, may be 0)
    avatar VARCHAR(100) DEFAULT NULL,          -- Discord avatar hash
    access_token VARCHAR(255) DEFAULT NULL,    -- OAuth access token
    refresh_token VARCHAR(255) DEFAULT NULL,   -- OAuth refresh token
    token_expires_at DATETIME DEFAULT NULL,    -- Token expiration time
    is_admin TINYINT(1) DEFAULT 0,             -- Admin flag for managing people
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- People table: people available to be ranked
CREATE TABLE IF NOT EXISTS people (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,                -- Display name
    discord_id BIGINT UNSIGNED DEFAULT NULL,   -- Optional Discord user ID
    avatar_url VARCHAR(500) DEFAULT NULL,      -- Avatar image URL
    added_by BIGINT UNSIGNED DEFAULT NULL,     -- User who added this person
    is_active TINYINT(1) DEFAULT 1,            -- Soft delete flag
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (added_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Rankings table: user rankings for each person
CREATE TABLE IF NOT EXISTS rankings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,          -- User who made the ranking
    person_id INT UNSIGNED NOT NULL,           -- Person being ranked
    tier CHAR(1) NOT NULL,                     -- Tier: S, A, B, C, D, F
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (person_id) REFERENCES people(id) ON DELETE CASCADE,
    UNIQUE KEY unique_user_person (user_id, person_id),
    INDEX idx_person_tier (person_id, tier)
) ENGINE=InnoDB;

-- Optional: Add check constraint for valid tiers (MySQL 8.0.16+)
ALTER TABLE rankings ADD CONSTRAINT chk_tier CHECK (tier IN ('S', 'A', 'B', 'C', 'D', 'F'));
