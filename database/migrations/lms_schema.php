<?php
if (!defined('IN_SITE')) {
    die('The Request Not Found');
}

function run_lms_schema_migration($database)
{
    $success = true;

    if (!column_exists('products', 'product_type')) {
        $success = (bool) $database->query("ALTER TABLE `products` ADD COLUMN `product_type` VARCHAR(20) NOT NULL DEFAULT 'stock'") && $success;
    }
    if (!column_exists('products', 'lms_course_id')) {
        $success = (bool) $database->query('ALTER TABLE `products` ADD COLUMN `lms_course_id` BIGINT UNSIGNED NULL DEFAULT NULL') && $success;
    }

    $statements = [
        "CREATE TABLE IF NOT EXISTS `courses` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `product_id` INT NOT NULL,
            `instructor_id` INT NULL,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL UNIQUE,
            `subtitle` VARCHAR(255),
            `description` LONGTEXT,
            `featured_image` VARCHAR(255),
            `intro_video` VARCHAR(255),
            `level` ENUM('beginner', 'intermediate', 'advanced', 'all') DEFAULT 'all',
            `language` VARCHAR(50) DEFAULT 'vi',
            `total_duration` INT DEFAULT 0,
            `is_published` TINYINT(1) DEFAULT 0,
            `published_at` DATETIME,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_sections` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `description` TEXT,
            `sort_order` INT DEFAULT 0,
            `is_published` TINYINT(1) DEFAULT 1,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_lessons` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `section_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `title` VARCHAR(255) NOT NULL,
            `slug` VARCHAR(255) NOT NULL,
            `lesson_type` ENUM('text', 'video', 'audio', 'pdf', 'embed', 'quiz') NOT NULL,
            `content` LONGTEXT,
            `media_url` VARCHAR(255),
            `media_duration` INT,
            `media_size` BIGINT,
            `embed_code` TEXT,
            `is_free_preview` TINYINT(1) DEFAULT 0,
            `is_published` TINYINT(1) DEFAULT 0,
            `sort_order` INT DEFAULT 0,
            `attachments` JSON,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (`section_id`) REFERENCES `course_sections`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_quiz_questions` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `lesson_id` INT NOT NULL,
            `question` TEXT NOT NULL,
            `question_type` ENUM('multiple_choice', 'true_false', 'short_answer') NOT NULL,
            `options` JSON NOT NULL,
            `explanation` TEXT,
            `sort_order` INT DEFAULT 0,
            `points` INT DEFAULT 1,
            FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_enrollments` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `order_id` INT NULL,
            `status` ENUM('active', 'expired', 'cancelled', 'suspended') DEFAULT 'active',
            `enrolled_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `expires_at` DATETIME NULL,
            `completed_at` DATETIME NULL,
            UNIQUE KEY `user_course_unique` (`user_id`, `course_id`),
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_progress` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `user_id` INT NOT NULL,
            `course_id` INT NOT NULL,
            `lesson_id` INT NOT NULL,
            `status` ENUM('not_started', 'in_progress', 'completed') DEFAULT 'not_started',
            `completed_at` DATETIME NULL,
            `last_position` INT DEFAULT 0,
            `quiz_score` DECIMAL(5,2) NULL,
            `quiz_attempts` INT DEFAULT 0,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
            `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            UNIQUE KEY `user_lesson_unique` (`user_id`, `lesson_id`),
            FOREIGN KEY (`course_id`) REFERENCES `courses`(`id`) ON DELETE CASCADE,
            FOREIGN KEY (`lesson_id`) REFERENCES `course_lessons`(`id`) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `course_media` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `uploaded_by` INT NOT NULL,
            `filename` VARCHAR(255) NOT NULL,
            `original_name` VARCHAR(255) NOT NULL,
            `mime_type` VARCHAR(100) NOT NULL,
            `file_size` BIGINT NOT NULL,
            `file_path` VARCHAR(255) NOT NULL,
            `storage_type` ENUM('local', 's3') DEFAULT 'local',
            `thumbnail_path` VARCHAR(255),
            `duration` INT,
            `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci",
        "CREATE TABLE IF NOT EXISTS `lms_enrollment_jobs` (
            `id` BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            `order_id` INT NOT NULL,
            `trans_id` VARCHAR(255) NOT NULL,
            `product_id` INT NOT NULL,
            `user_id` INT NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `username` VARCHAR(255) NULL,
            `full_name` VARCHAR(255) NULL,
            `course_id` BIGINT UNSIGNED NOT NULL,
            `status` VARCHAR(20) NOT NULL DEFAULT 'pending',
            `attempts` INT NOT NULL DEFAULT 0,
            `last_error` TEXT NULL,
            `next_attempt_at` DATETIME NULL,
            `created_at` DATETIME NOT NULL,
            `updated_at` DATETIME NOT NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uniq_order_course` (`order_id`, `course_id`),
            UNIQUE KEY `uniq_user_course` (`user_id`, `course_id`),
            KEY `idx_queue` (`status`, `next_attempt_at`),
            KEY `idx_user_course` (`user_id`, `course_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
    ];

    foreach ($statements as $statement) {
        $success = (bool) $database->query($statement) && $success;
    }

    return $success;
}
