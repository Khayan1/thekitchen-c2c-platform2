-- Thekitchen Database Schema
-- C2C E-Commerce Platform for South Africa

CREATE DATABASE IF NOT EXISTS Thekitchen;
USE Thekitchen;

-- Users table (supports RBAC roles)
CREATE TABLE users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(20),
    id_verified ENUM('pending','verified','rejected') DEFAULT 'pending',
    role ENUM('buyer','seller','moderator','admin') DEFAULT 'buyer',
    profile_pic VARCHAR(255) DEFAULT 'default.jpg',
    bio TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Listings table
CREATE TABLE listings (
    listing_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    category VARCHAR(100) NOT NULL,
    image_url VARCHAR(255) DEFAULT 'placeholder.jpg',
    location VARCHAR(150),
    status ENUM('active','sold','pending','removed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Orders table
CREATE TABLE orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    buyer_id INT NOT NULL,
    listing_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    status ENUM('pending','paid','shipped','completed','cancelled','refunded') DEFAULT 'pending',
    shipping_address TEXT,
    notes TEXT,
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (buyer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE
);

-- Payments table
CREATE TABLE payments (
    payment_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    gateway_ref VARCHAR(200),
    gateway_name VARCHAR(50) DEFAULT 'PayFast',
    status ENUM('pending','completed','failed','refunded') DEFAULT 'pending',
    amount DECIMAL(10,2) NOT NULL,
    paid_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE
);

-- Reviews table
CREATE TABLE reviews (
    review_id INT AUTO_INCREMENT PRIMARY KEY,
    reviewer_id INT NOT NULL,
    listing_id INT NOT NULL,
    order_id INT,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE CASCADE,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE SET NULL
);

-- Messages table (buyer-seller chat)
CREATE TABLE messages (
    message_id INT AUTO_INCREMENT PRIMARY KEY,
    sender_id INT NOT NULL,
    receiver_id INT NOT NULL,
    listing_id INT,
    message_text TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (listing_id) REFERENCES listings(listing_id) ON DELETE SET NULL
);

-- Sample data for demonstration
INSERT INTO users (full_name, email, password_hash, phone, id_verified, role) VALUES
('Admin User', 'admin@Thekitchen.co.za', '$2y$10$examplehash1', '0711234567', 'verified', 'admin'),
('Moderator One', 'mod@Thekitchen.co.za', '$2y$10$examplehash2', '0722345678', 'verified', 'moderator'),
('Thabo Mokoena', 'thabo@email.com', '$2y$10$examplehash3', '0733456789', 'verified', 'seller'),
('Naledi Dlamini', 'naledi@email.com', '$2y$10$examplehash4', '0744567890', 'verified', 'buyer');

INSERT INTO listings (user_id, title, description, price, category, location, status) VALUES
(3, 'iPhone 12 Pro – Excellent Condition', 'Used iPhone 12 Pro 128GB. No scratches. Comes with original charger. Selling because I upgraded.', 8500.00, 'Electronics', 'Soweto, Johannesburg', 'active'),
(3, 'Nike Air Max 270 – Size 10', 'Barely worn Nike Air Max 270. Size 10. White and black colourway. Original box included.', 950.00, 'Clothing & Shoes', 'Soweto, Johannesburg', 'active'),
(3, 'Vintage Couch Set – 3+2 Seater', '3+2 seater couch set in great condition. Light grey fabric. Must collect. No delivery.', 3200.00, 'Furniture', 'Soweto, Johannesburg', 'active'),
(4, 'Casio Scientific Calculator FX-991', 'Used once. Perfect for matric and varsity. Casio FX-991EX ClassWiz.', 320.00, 'Books & Stationery', 'Tembisa, Ekurhuleni', 'active');
