-- Test data for FreelanceBD

-- Insert test users
INSERT INTO users (username, email, password, role, country, phone, des, created_at) VALUES
('admin_user', 'admin@freelancebd.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin', 'Bangladesh', '+8801234567890', 'System Administrator', NOW()),
('john_seller', 'seller@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'seller', 'USA', '+1234567890', 'Professional Web Developer', NOW()),
('jane_buyer', 'buyer@gmail.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'buyer', 'Canada', '+1987654321', 'Business Owner looking for services', NOW())
ON DUPLICATE KEY UPDATE email = email;

-- Insert test categories
INSERT INTO categories (title, description, created_at) VALUES
('Web Development', 'Website and web application development services', NOW()),
('Graphic Design', 'Logo, branding, and visual design services', NOW()),
('Digital Marketing', 'SEO, social media, and online marketing services', NOW()),
('Writing & Translation', 'Content writing and translation services', NOW()),
('Video & Animation', 'Video editing and animation services', NOW())
ON DUPLICATE KEY UPDATE title = title;

-- Insert test gigs (using seller user ID = 2)
INSERT INTO gigs (user_id, title, description, short_title, short_desc, delivery_time, revision_number, price, features, category_id, created_at) VALUES
(2, 'I will create a professional website for your business', 'I will design and develop a modern, responsive website tailored to your business needs. This includes custom design, mobile optimization, and basic SEO setup.', 'Professional Website Development', 'Custom business website with modern design', 7, 3, 299.99, '["Responsive Design", "SEO Optimized", "Contact Forms", "Social Media Integration"]', 1, NOW()),
(2, 'I will design a stunning logo for your brand', 'Professional logo design service with unlimited revisions until you are 100% satisfied. Includes multiple concepts, vector files, and brand guidelines.', 'Professional Logo Design', 'Custom logo with unlimited revisions', 3, 999, 89.99, '["Multiple Concepts", "Vector Files", "Brand Guidelines", "Unlimited Revisions"]', 2, NOW()),
(2, 'I will boost your website SEO ranking', 'Complete SEO optimization service including keyword research, on-page optimization, technical SEO, and monthly reporting.', 'SEO Optimization Service', 'Complete SEO package for better rankings', 14, 2, 199.99, '["Keyword Research", "On-Page SEO", "Technical SEO", "Monthly Reports"]', 3, NOW());

-- Test login credentials:
-- Admin: admin@freelancebd.com / password
-- Seller: seller@gmail.com / password  
-- Buyer: buyer@gmail.com / password
