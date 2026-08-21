-- ==========================================
-- Advaya Watch Store Database Schema
-- Target Database: advaya_db
-- ==========================================

-- Create Database if not exists
CREATE DATABASE IF NOT EXISTS `advaya_db` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `advaya_db`;

-- 1. Users Table
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `email` VARCHAR(150) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `phone` VARCHAR(20) DEFAULT NULL,
    `address` TEXT DEFAULT NULL,
    `role` VARCHAR(20) DEFAULT 'customer', -- 'customer', 'admin'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_email` (`email`),
    INDEX `idx_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. Categories Table
CREATE TABLE IF NOT EXISTS `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL UNIQUE,
    `description` TEXT DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. Products Table
CREATE TABLE IF NOT EXISTS `products` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `category_id` INT DEFAULT NULL,
    `name` VARCHAR(150) NOT NULL,
    `brand` VARCHAR(100) NOT NULL,
    `description` TEXT DEFAULT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    `stock` INT NOT NULL DEFAULT 0,
    `image` VARCHAR(255) DEFAULT NULL,
    `status` VARCHAR(20) DEFAULT 'active', -- 'active', 'inactive'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_products_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE SET NULL,
    INDEX `idx_status` (`status`),
    INDEX `idx_category` (`category_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. Cart Table
CREATE TABLE IF NOT EXISTS `cart` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_cart_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_cart_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_user_product` (`user_id`, `product_id`),
    INDEX `idx_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. Orders Table
CREATE TABLE IF NOT EXISTS `orders` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NOT NULL,
    `total_amount` DECIMAL(10,2) NOT NULL,
    `shipping_address` TEXT NOT NULL,
    `payment_method` VARCHAR(50) NOT NULL,
    `order_status` VARCHAR(20) DEFAULT 'pending', -- 'pending', 'processing', 'shipped', 'delivered', 'cancelled'
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_orders_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_order_user` (`user_id`),
    INDEX `idx_order_status` (`order_status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. Order Items Table
CREATE TABLE IF NOT EXISTS `order_items` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `product_id` INT NOT NULL,
    `quantity` INT NOT NULL,
    `price` DECIMAL(10,2) NOT NULL,
    CONSTRAINT `fk_items_order` FOREIGN KEY (`order_id`) REFERENCES `orders` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_items_product` FOREIGN KEY (`product_id`) REFERENCES `products` (`id`) ON DELETE RESTRICT,
    INDEX `idx_item_order` (`order_id`),
    INDEX `idx_item_product` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;


-- 7. Payments Table
CREATE TABLE IF NOT EXISTS `payments` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `order_id` INT NOT NULL,
    `razorpay_order_id` VARCHAR(100) DEFAULT NULL,
    `razorpay_payment_id` VARCHAR(100) DEFAULT NULL,
    `razorpay_signature` VARCHAR(255) DEFAULT NULL,
    `amount` DECIMAL(10,2) NOT NULL,
    `currency` VARCHAR(10) DEFAULT 'INR',
    `status` VARCHAR(30) DEFAULT 'created',
    `payment_method` VARCHAR(50) DEFAULT 'razorpay',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,

    CONSTRAINT `fk_payments_order`
        FOREIGN KEY (`order_id`)
        REFERENCES `orders` (`id`)
        ON DELETE CASCADE,

    INDEX `idx_payment_order` (`order_id`),
    INDEX `idx_razorpay_order` (`razorpay_order_id`),
    INDEX `idx_razorpay_payment` (`razorpay_payment_id`),
    INDEX `idx_payment_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ==========================================
-- Insert Sample Categories
-- ==========================================
INSERT INTO `categories` (`id`, `name`, `description`) VALUES
(1, 'Luxury', 'Prestige Swiss-made timepieces, precious materials, and mechanical excellence.'),
(2, 'Chronograph', 'Precision stopwatches with distinct sub-dials and timeless racing aesthetics.'),
(3, 'Sports & Dive', 'Durable, highly water-resistant watches built for marine life and outdoor adventures.'),
(4, 'Minimalist & Dress', 'Sleek, elegant, ultra-thin profiles suitable for business and black-tie events.'),
(5, 'Smartwatch', 'Advanced hybrid and digital watches with health tracking and connected features.')
ON DUPLICATE KEY UPDATE `description` = VALUES(`description`);

-- ==========================================
-- Insert Sample Products
-- ==========================================
INSERT INTO `products` (`id`, `category_id`, `name`, `brand`, `description`, `price`, `stock`, `image`, `status`) VALUES
(1, 1, 'Submariner Date', 'Rolex', 'The archetype of the diver\'s watch, featuring a stunning black dial, large luminescent hour markers, and a unidirectional Oystersteel bezel.', 14500.00, 5, 'rolex_submariner.png', 'active'),
(2, 2, 'Speedmaster Professional', 'Omega', 'Known as the Moonwatch, this iconic chronograph features an asymmetric case, matte black dial, and the legendary manual-winding calibre 3861.', 7200.00, 10, 'omega_speedmaster.png', 'active'),
(3, 3, 'Prospex Turtle Diver', 'Seiko', 'A reliable automatic diver watch featuring a cushion-shaped case, date window, hand-winding capability, and 200m water resistance.', 495.00, 20, 'seiko_prospex.png', 'active'),
(4, 4, 'Gentleman Powermatic 80', 'Tissot', 'A clean, ergonomic dress watch powered by the Powermatic 80 automatic movement with an outstanding 80 hours of power reserve.', 775.00, 15, 'tissot_gentleman.png', 'active'),
(5, 5, 'Smart Watch Ultra 2', 'Apple', 'Designed for endurance, exploration, and adventure. High-strength titanium case, up to 36-hour battery life, and high-precision dual-frequency GPS.', 799.00, 12, 'apple_watch_ultra.png', 'active')
ON DUPLICATE KEY UPDATE 
    `category_id` = VALUES(`category_id`),
    `name` = VALUES(`name`),
    `brand` = VALUES(`brand`),
    `description` = VALUES(`description`),
    `price` = VALUES(`price`),
    `stock` = VALUES(`stock`),
    `image` = VALUES(`image`),
    `status` = VALUES(`status`);
