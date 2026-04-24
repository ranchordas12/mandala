-- ============================================================
--  MANDALA GALLERY — Complete Database Schema
--  PASTE THIS INTO: phpMyAdmin → SQL tab → GO
--  (after selecting your database first)
-- ============================================================

SET NAMES utf8mb4;
SET foreign_key_checks = 0;

-- ── USERS ────────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `users` (
  `id`            INT UNSIGNED     NOT NULL AUTO_INCREMENT,
  `name`          VARCHAR(120)     NOT NULL,
  `email`         VARCHAR(200)     NOT NULL,
  `password_hash` VARCHAR(255)     NOT NULL,
  `created_at`    DATETIME         NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── MANDALAS ─────────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `mandalas` (
  `id`          INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`     INT UNSIGNED  NOT NULL,
  `title`       VARCHAR(120)  NOT NULL,
  `description` TEXT,
  `category`    ENUM('geometric','floral','spiritual','abstract') NOT NULL DEFAULT 'geometric',
  `image_path`  VARCHAR(255)  NOT NULL,
  `is_public`   TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`  DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`     (`user_id`),
  KEY `idx_category` (`category`),
  KEY `idx_public`   (`is_public`),
  CONSTRAINT `fk_mandala_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── BLOG POSTS ───────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `blog_posts` (
  `id`           INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`      INT UNSIGNED  NOT NULL,
  `title`        VARCHAR(200)  NOT NULL,
  `excerpt`      VARCHAR(250)  NOT NULL,
  `content`      LONGTEXT      NOT NULL,
  `is_published` TINYINT(1)    NOT NULL DEFAULT 1,
  `created_at`   DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_user`      (`user_id`),
  KEY `idx_published` (`is_published`),
  CONSTRAINT `fk_blog_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── USER SETTINGS ────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `user_settings` (
  `id`               INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `user_id`          INT UNSIGNED  NOT NULL,
  `artist_initials`  VARCHAR(10)   NOT NULL DEFAULT 'AG',
  `artist_name`      VARCHAR(80)   NOT NULL DEFAULT 'Aastha Ghimire',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uq_user` (`user_id`),
  CONSTRAINT `fk_settings_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- ── DOWNLOAD LOG ─────────────────────────────────────────────
CREATE TABLE IF NOT EXISTS `download_log` (
  `id`            INT UNSIGNED  NOT NULL AUTO_INCREMENT,
  `mandala_id`    INT UNSIGNED  NOT NULL,
  `user_id`       INT UNSIGNED  NOT NULL,
  `ip`            VARCHAR(45),
  `downloaded_at` DATETIME      NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `idx_mandala` (`mandala_id`),
  KEY `idx_user_dl` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET foreign_key_checks = 1;

-- ============================================================
--  ADMIN ACCOUNT — Change password after first login!
--
--  This creates one admin with:
--    Email:    admin@ghimireaastha.com.np
--    Password: Mandala@2025
--
--  The hash below is bcrypt of "Mandala@2025"
-- ============================================================
INSERT IGNORE INTO `users` (`name`, `email`, `password_hash`) VALUES (
  'Aastha Ghimire',
  'admin@ghimireaastha.com.np',
  '$2y$12$3kz3hNL8E4grk8pZqXF5.ezaqBLbWAMxquXKPGQ3J/sXmH2b0h5GS'
);

-- Insert default settings for admin (user id 1)
INSERT IGNORE INTO `user_settings` (`user_id`, `artist_initials`, `artist_name`) VALUES (1, 'AG', 'Aastha Ghimire');

-- ============================================================
--  DONE. All 5 tables created + 1 admin account inserted.
-- ============================================================
