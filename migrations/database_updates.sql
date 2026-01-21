-- 1. Create team_penalties table
CREATE TABLE IF NOT EXISTS `team_penalties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `tournament_id` int(11) NOT NULL,
  `match_id` int(11) DEFAULT NULL,
  `type` enum('warning','deduction') NOT NULL,
  `points` int(11) NOT NULL DEFAULT 0,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `team_id` (`team_id`),
  KEY `tournament_id` (`tournament_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- 2. Add video_url to photo table
ALTER TABLE `photo` ADD COLUMN `video_url` TEXT DEFAULT NULL AFTER `file_name`;

-- 3. Add gender to divisions table
ALTER TABLE `divisions` ADD COLUMN `gender` enum('Men','Women','Mixed') DEFAULT 'Men' AFTER `division_name`;

-- 4. Ensure payment_settings table exists (usually handled by AdminController, but good to have DDL)
CREATE TABLE IF NOT EXISTS `payment_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_name` varchar(100) NOT NULL UNIQUE,
  `setting_value` text NOT NULL,
  `description` text,
  `created_at` datetime DEFAULT CURRENT_TIMESTAMP,
  `updated_at` datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_setting_name` (`setting_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Insert default logo setting
INSERT IGNORE INTO `payment_settings` (`setting_name`, `setting_value`, `description`) VALUES ('SITE_LOGO', '', 'Path to site logo');
