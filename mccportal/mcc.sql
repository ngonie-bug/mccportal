--
-- Refined table structure for a municipal service platform.
--

-- Table: users
-- Standardized user information with first and last names for flexibility.
-- The unique `user_id` is used across all other tables.
CREATE TABLE `users` (
  `user_id` INT PRIMARY KEY AUTO_INCREMENT,
  `first_name` VARCHAR(255) NOT NULL,
  `last_name` VARCHAR(255) NOT NULL,
  `username` VARCHAR(255) NOT NULL UNIQUE,
  `email` VARCHAR(255) UNIQUE,
  `phone` VARCHAR(255) UNIQUE,
  `password_hash` VARCHAR(255) NOT NULL,
  `role` ENUM('citizen', 'staff', 'admin') NOT NULL,
  `is_active` BOOLEAN DEFAULT TRUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `last_login` TIMESTAMP NULL
);

-- Table: audit_logs
-- Tracks all user actions for security and debugging.
CREATE TABLE `audit_logs` (
  `log_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `action` VARCHAR(255) NOT NULL,
  `details` TEXT,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
);

-- Table: departments
-- Stores information about municipal departments.
CREATE TABLE `departments` (
  `department_id` INT PRIMARY KEY AUTO_INCREMENT,
  `department_name` VARCHAR(255) NOT NULL UNIQUE,
  `description` TEXT,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table: category_department_mapping
-- Links service categories to departments.
CREATE TABLE `category_department_mapping` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `category_name` VARCHAR(255) NOT NULL UNIQUE,
  `department_id` INT NOT NULL,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
);

-- Table: staff
-- Links a user to their specific staff information.
CREATE TABLE `staff` (
  `staff_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL UNIQUE,
  `department_id` INT NOT NULL,
  `employee_id` VARCHAR(255) NOT NULL UNIQUE,
  `designation` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
);

-- Table: tickets
-- Centralized table for all citizen service requests.
CREATE TABLE `tickets` (
  `ticket_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `assigned_to_department_id` INT,
  `assigned_staff_id` INT,
  `subject` VARCHAR(255) NOT NULL,
  `status` ENUM('open', 'in_progress', 'closed', 'pending') DEFAULT 'open',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments`(`department_id`),
  FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff`(`staff_id`)
);

-- Table: chat_messages
-- Stores chat messages related to tickets.
CREATE TABLE `chat_messages` (
  `message_id` INT PRIMARY KEY AUTO_INCREMENT,
  `ticket_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `sender_role` VARCHAR(255) NOT NULL,
  `message` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `is_read` BOOLEAN DEFAULT FALSE,
  FOREIGN KEY (`ticket_id`) REFERENCES `tickets`(`ticket_id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`user_id`)
);

-- Table: reports
-- Stores citizen reports, distinct from tickets.
-- Renamed `citizen_id` to `user_id`.
CREATE TABLE `reports` (
  `report_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT,
  `category` VARCHAR(255) NOT NULL,
  `sub_category` VARCHAR(255),
  `description` TEXT,
  `location_lat` DECIMAL(10, 8),
  `location_lon` DECIMAL(11, 8),
  `status` ENUM('new', 'in_progress', 'resolved', 'escalated') DEFAULT 'new',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  `assigned_staff_id` INT,
  `assigned_to_department_id` INT,
  `priority` ENUM('low', 'medium', 'high') DEFAULT 'low',
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`assigned_staff_id`) REFERENCES `staff`(`staff_id`),
  FOREIGN KEY (`assigned_to_department_id`) REFERENCES `departments`(`department_id`)
);

-- Table: report_attachments
-- Stores attachments for reports.
CREATE TABLE `report_attachments` (
  `attachment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `report_id` INT NOT NULL,
  `file_path` VARCHAR(255) NOT NULL,
  `file_type` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`report_id`) REFERENCES `reports`(`report_id`)
);

-- Table: report_messages
-- Stores messages related to reports.
CREATE TABLE `report_messages` (
  `message_id` INT PRIMARY KEY AUTO_INCREMENT,
  `report_id` INT NOT NULL,
  `sender_id` INT NOT NULL,
  `message_text` TEXT NOT NULL,
  `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`report_id`) REFERENCES `reports`(`report_id`),
  FOREIGN KEY (`sender_id`) REFERENCES `users`(`user_id`)
);

-- Table: meter_readings
-- `photo_path` and `photo_urls` are redundant, kept `photo_path` for simplicity.
-- `location_lat/lon` and `gps_lat/lon` are redundant, kept one pair.
CREATE TABLE `meter_readings` (
  `reading_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `reading_value` DECIMAL(10, 2) NOT NULL,
  `photo_path` VARCHAR(255),
  `location_lat` DECIMAL(10, 8),
  `location_lon` DECIMAL(11, 8),
  `status` ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
  `it_staff_id` INT,
  `submission_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`it_staff_id`) REFERENCES `staff`(`staff_id`)
);

-- Table: bills
-- Removed redundant `bill_amount` and simplified the structure.
CREATE TABLE `bills` (
  `bill_id` INT PRIMARY KEY AUTO_INCREMENT,
  `bill_type` VARCHAR(255) NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `user_id` INT NOT NULL,
  `meter_reading_id` INT,
  `due_date` DATE NOT NULL,
  `is_paid` BOOLEAN DEFAULT FALSE,
  `pdf_path` VARCHAR(255),
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`meter_reading_id`) REFERENCES `meter_readings`(`reading_id`)
);

-- Table: payments
-- Stores payment records.
CREATE TABLE `payments` (
  `payment_id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `bill_id` INT NOT NULL,
  `amount` DECIMAL(10, 2) NOT NULL,
  `payment_method` VARCHAR(255),
  `payment_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `status` VARCHAR(255) DEFAULT 'successful',
  `receipt_url` VARCHAR(255),
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
  FOREIGN KEY (`bill_id`) REFERENCES `bills`(`bill_id`)
);

-- Table: notices
-- For internal announcements or public notices.
CREATE TABLE `notices` (
  `notice_id` INT PRIMARY KEY AUTO_INCREMENT,
  `title` VARCHAR(255) NOT NULL,
  `content` TEXT NOT NULL,
  `department_id` INT,
  `admin_id` INT,
  `is_urgent` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
);

-- Table: notifications
-- Used for sending alerts to users.
CREATE TABLE `notifications` (
  `id` INT PRIMARY KEY AUTO_INCREMENT,
  `user_id` INT NOT NULL,
  `title` VARCHAR(255),
  `message` TEXT NOT NULL,
  `type` VARCHAR(255),
  `is_read` BOOLEAN DEFAULT FALSE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
);

-- Table: quick_replies
-- Quick reply templates for staff.
CREATE TABLE `quick_replies` (
  `reply_id` INT PRIMARY KEY AUTO_INCREMENT,
  `department_id` INT NOT NULL,
  `template_name` VARCHAR(255) NOT NULL,
  `template_text` TEXT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  FOREIGN KEY (`department_id`) REFERENCES `departments`(`department_id`)
);

-- Table: password_resets
-- Used for password reset functionality.
CREATE TABLE `password_resets` (
  `email` VARCHAR(255) NOT NULL PRIMARY KEY,
  `token` VARCHAR(255) NOT NULL,
  `expires_at` TIMESTAMP NOT NULL
);
