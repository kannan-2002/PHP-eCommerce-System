-- Create Admin Account
-- This creates an admin account if one doesn't exist
-- Email: admin@ecommerce.com
-- Password: admin123

USE ecommerce_db;

-- Insert new admin
-- The password hash below is for: admin123
INSERT INTO admins (email, password) 
VALUES ('admin@ecommerce.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi')
ON DUPLICATE KEY UPDATE password = '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi';

-- Verify admin was created
SELECT id, email, 'Password: admin123' as password_info FROM admins WHERE email = 'admin@ecommerce.com';