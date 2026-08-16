ALTER TABLE `products`
    ADD COLUMN IF NOT EXISTS `product_type` VARCHAR(20) NOT NULL DEFAULT 'stock' AFTER `pending`,
    ADD COLUMN IF NOT EXISTS `lms_course_id` BIGINT UNSIGNED NULL DEFAULT NULL AFTER `product_type`;

CREATE TABLE IF NOT EXISTS `lms_enrollment_jobs` (
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
