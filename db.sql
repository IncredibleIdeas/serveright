-- Create database
CREATE DATABASE serveright;
USE serveright;

-- Users table
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    phone VARCHAR(20),
    password VARCHAR(255) NOT NULL,
    user_type ENUM('customer', 'admin') DEFAULT 'customer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Services table
CREATE TABLE services (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2),
    category VARCHAR(50),
    image_url VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE
);

-- Service bookings table
CREATE TABLE service_bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    service_id INT,
    booking_date DATE NOT NULL,
    booking_time TIME NOT NULL,
    special_requests TEXT,
    status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
    total_amount DECIMAL(10,2),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (service_id) REFERENCES services(id)
);

-- Delivery categories table
CREATE TABLE delivery_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    icon_class VARCHAR(50)
);

-- Delivery items table
CREATE TABLE delivery_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    image_url VARCHAR(255),
    is_available BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (category_id) REFERENCES delivery_categories(id)
);

-- Delivery orders table
CREATE TABLE delivery_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    item_id INT,
    quantity INT DEFAULT 1,
    delivery_address TEXT NOT NULL,
    delivery_instructions TEXT,
    delivery_time VARCHAR(50),
    total_amount DECIMAL(10,2),
    status ENUM('pending', 'confirmed', 'out_for_delivery', 'delivered', 'cancelled') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (item_id) REFERENCES delivery_items(id)
);

-- Insert sample data
INSERT INTO users (full_name, email, phone, password, user_type) VALUES 
('Admin User', 'admin@serveright.com', '555-123-4567', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('John Doe', 'john@example.com', '555-987-6543', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'customer');

INSERT INTO services (name, description, price, category, image_url) VALUES 
('Handyman Services', 'Fixing, assembling, and repairing - we''ve got you covered for all your home maintenance needs.', 45.00, 'home', 'handyman.jpg'),
('Cleaning Services', 'Professional cleaning for your home or office. Regular or one-time cleaning available.', 35.00, 'home', 'cleaning.jpg'),
('Pet Care', 'Pet sitting, walking, grooming, and vet visits. Your furry friends are in good hands.', 25.00, 'pet', 'petcare.jpg'),
('Car Washing', 'Professional car washing and detailing services at your home or office.', 30.00, 'vehicle', 'carwash.jpg'),
('Plumbing', 'Fix leaks, unclog drains, and install fixtures with our certified plumbers.', 65.00, 'home', 'plumbing.jpg'),
('Electrical', 'Install fixtures, repair wiring, and handle all your electrical needs safely.', 70.00, 'home', 'electrical.jpg');

INSERT INTO delivery_categories (name, description, icon_class) VALUES 
('Pharmacy', 'Prescriptions and over-the-counter medications delivered safely.', 'fas fa-pills'),
('Groceries', 'Fresh produce, pantry staples, and household essentials.', 'fas fa-shopping-basket'),
('Gifts & Flowers', 'Thoughtful gifts and beautiful flowers for any occasion.', 'fas fa-gift'),
('Food & Restaurants', 'Meals from your favorite local restaurants delivered hot.', 'fas fa-utensils');

INSERT INTO delivery_items (category_id, name, description, price, image_url) VALUES 
(1, 'Pain Relief Tablets', 'Effective pain relief medication', 8.99, 'pain_relief.jpg'),
(1, 'Multivitamins', 'Daily multivitamin supplements', 15.99, 'multivitamins.jpg'),
(2, 'Fresh Bananas', 'Fresh organic bananas', 2.99, 'bananas.jpg'),
(2, 'Organic Milk', 'Fresh organic milk', 4.49, 'milk.jpg'),
(3, 'Bouquet of Roses', 'Beautiful fresh rose bouquet', 29.99, 'roses.jpg'),
(3, 'Chocolate Box', 'Assorted premium chocolates', 19.99, 'chocolates.jpg'),
(4, 'Pizza Margherita', 'Classic Italian pizza', 16.99, 'pizza.jpg'),
(4, 'Chicken Burger', 'Juicy chicken burger with fries', 12.99, 'burger.jpg');

-- Add some additional sample data for testing
INSERT INTO service_bookings (user_id, service_id, booking_date, booking_time, special_requests, status, total_amount) VALUES 
(2, 1, '2024-01-15', '10:00', 'Need help assembling IKEA furniture', 'completed', 45.00),
(2, 2, '2024-01-20', '14:00', 'Deep cleaning required', 'confirmed', 35.00),
(2, 3, '2024-01-25', '09:00', 'Two dogs need walking', 'pending', 25.00);

INSERT INTO delivery_orders (user_id, item_id, quantity, delivery_address, delivery_instructions, delivery_time, total_amount, status) VALUES 
(2, 1, 2, '123 Main St, Apt 4B, New York, NY 10001', 'Leave at front door', 'asap', 17.98, 'delivered'),
(2, 5, 1, '123 Main St, Apt 4B, New York, NY 10001', 'Call upon arrival', '3-6', 29.99, 'confirmed'),
(2, 7, 1, '123 Main St, Apt 4B, New York, NY 10001', 'Extra napkins please', '6-9', 16.99, 'pending');