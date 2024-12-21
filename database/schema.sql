-- Create database
CREATE DATABASE IF NOT EXISTS `defaultdb`;
USE `defaultdb`;

-- Create users table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `member_id` VARCHAR(50) UNIQUE NOT NULL,
    `full_name` VARCHAR(100) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    `status` ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);



-- Create transactions table
CREATE TABLE IF NOT EXISTS `transactions` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `debt_id` INT NOT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `description` TEXT,
    `image_path` VARCHAR(255),
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`debt_id`) REFERENCES `debts`(`id`) ON DELETE CASCADE
);

-- Create payments table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT NOT NULL,
    `transaction_id` INT,
    `amount` DECIMAL(10,2) NOT NULL,
    `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`transaction_id`) REFERENCES `transactions`(`id`) ON DELETE SET NULL
);

-- Create activity_logs table
CREATE TABLE IF NOT EXISTS `activity_logs` (
    `id` INT PRIMARY KEY AUTO_INCREMENT,
    `user_id` INT,
    `activity_type` VARCHAR(50) NOT NULL,
    `description` TEXT NOT NULL,
    `ip_address` VARCHAR(45),
    `user_agent` TEXT,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE SET NULL
);

-- Insert default admin user
INSERT INTO `users` (`member_id`, `full_name`, `password`, `role`) 
SELECT 'ADMIN001', 'System Administrator', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'
WHERE NOT EXISTS (
    SELECT 1 FROM `users` WHERE `role` = 'admin'
) LIMIT 1;
