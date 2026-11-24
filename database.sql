-- =======================================================
-- APOLLO MINIMALIST MUSIC PLAYER DATABASE (FULL PHP NATIVE)
-- Tables: users, visitor_logs, activity_logs, playlists, saved_tracks
-- =======================================================

CREATE DATABASE IF NOT EXISTS `db_apollo` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `db_apollo`;

-- 1. Table: users (Registered User Accounts)
CREATE TABLE IF NOT EXISTS `users` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `username` VARCHAR(60) NOT NULL UNIQUE,
  `email` VARCHAR(150) NOT NULL UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `last_login_at` DATETIME DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Table: visitor_logs (Automatic Visitor Tracking without requiring Login)
CREATE TABLE IF NOT EXISTS `visitor_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(60) DEFAULT 'Guest',
  `session_id` VARCHAR(128) NOT NULL,
  `ip_address` VARCHAR(64) NOT NULL,
  `browser_device` VARCHAR(255) NOT NULL,
  `page_url` VARCHAR(255) DEFAULT '/',
  `visited_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_active_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_visitor_session` (`session_id`),
  INDEX `idx_visitor_user` (`user_id`),
  CONSTRAINT `fk_visitor_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 3. Table: activity_logs (Songs Played, Searches, Likes, Playlists, Auth Events)
CREATE TABLE IF NOT EXISTS `activity_logs` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `username` VARCHAR(60) DEFAULT 'Guest',
  `action_type` VARCHAR(40) NOT NULL,
  `video_id` VARCHAR(50) DEFAULT '',
  `track_title` VARCHAR(255) DEFAULT '',
  `track_artist` VARCHAR(150) DEFAULT '',
  `detail` VARCHAR(255) DEFAULT '',
  `ip_address` VARCHAR(64) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_activity_type` (`action_type`),
  INDEX `idx_activity_user` (`user_id`),
  CONSTRAINT `fk_activity_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 4. Table: playlists (Scoped per User Account or Guest)
CREATE TABLE IF NOT EXISTS `playlists` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT DEFAULT NULL,
  `name` VARCHAR(150) NOT NULL,
  `description` VARCHAR(255) DEFAULT '',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_playlist_user` (`user_id`),
  CONSTRAINT `fk_playlist_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 5. Table: saved_tracks (Songs inside Playlists / Liked Songs)
CREATE TABLE IF NOT EXISTS `saved_tracks` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `playlist_id` INT NOT NULL,
  `video_id` VARCHAR(50) NOT NULL,
  `title` VARCHAR(255) NOT NULL,
  `artist` VARCHAR(150) NOT NULL,
  `album` VARCHAR(150) DEFAULT '',
  `duration` VARCHAR(20) DEFAULT '00:00',
  `thumbnail` VARCHAR(500) DEFAULT '',
  `added_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`playlist_id`) REFERENCES `playlists`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
