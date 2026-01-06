-- SDO CTS Admin Panel Database Schema
-- San Pedro Division Office Complaint Tracking System
-- Admin Tables for Authentication, Roles, and Activity Logging

USE sdo_cts;

-- Admin Roles Table
CREATE TABLE IF NOT EXISTS admin_roles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    description VARCHAR(255),
    permissions JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Admin Users Table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(255) UNIQUE NOT NULL,
    password_hash VARCHAR(255) DEFAULT NULL,
    full_name VARCHAR(255) NOT NULL,
    role_id INT NOT NULL,
    google_id VARCHAR(255) DEFAULT NULL,
    avatar_url VARCHAR(500) DEFAULT NULL,
    unit VARCHAR(50) DEFAULT NULL,
    is_active TINYINT(1) DEFAULT 1,
    last_login TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    created_by INT DEFAULT NULL,
    
    FOREIGN KEY (role_id) REFERENCES admin_roles(id),
    FOREIGN KEY (created_by) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_email (email),
    INDEX idx_google_id (google_id)
) ENGINE=InnoDB;

-- Password Reset Tokens
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    token VARCHAR(255) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE CASCADE,
    INDEX idx_token (token)
) ENGINE=InnoDB;

-- Activity Log Table (for accountability)
CREATE TABLE IF NOT EXISTS activity_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    action_type ENUM('login', 'logout', 'view', 'create', 'update', 'delete', 'status_change', 'forward', 'accept', 'return') NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT DEFAULT NULL,
    description TEXT,
    old_value JSON DEFAULT NULL,
    new_value JSON DEFAULT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (user_id) REFERENCES admin_users(id) ON DELETE SET NULL,
    INDEX idx_user_id (user_id),
    INDEX idx_action_type (action_type),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB;

-- Complaint Assignments (for internal forwarding)
CREATE TABLE IF NOT EXISTS complaint_assignments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    assigned_to_unit VARCHAR(50) NOT NULL,
    assigned_by INT NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES admin_users(id),
    INDEX idx_complaint_id (complaint_id)
) ENGINE=InnoDB;

-- Extend complaints table with new fields
ALTER TABLE complaints 
    ADD COLUMN accepted_at TIMESTAMP NULL AFTER status,
    ADD COLUMN accepted_by INT NULL AFTER accepted_at,
    ADD COLUMN returned_at TIMESTAMP NULL AFTER accepted_by,
    ADD COLUMN returned_by INT NULL AFTER returned_at,
    ADD COLUMN return_reason TEXT NULL AFTER returned_by,
    ADD COLUMN assigned_unit VARCHAR(50) NULL AFTER return_reason,
    ADD COLUMN handled_by INT NULL AFTER assigned_unit;

-- Update complaint_history to include admin user reference
ALTER TABLE complaint_history
    ADD COLUMN admin_user_id INT NULL AFTER updated_by;

-- Update status enum to match new workflow
ALTER TABLE complaints 
    MODIFY COLUMN status ENUM('pending', 'accepted', 'in_progress', 'resolved', 'returned', 'closed') NOT NULL DEFAULT 'pending';

ALTER TABLE complaint_history
    MODIFY COLUMN status ENUM('pending', 'accepted', 'in_progress', 'resolved', 'returned', 'closed') NOT NULL;

-- Insert default roles
INSERT INTO admin_roles (name, description, permissions) VALUES
('Super Admin', 'Full system access', '{"all": true}'),
('Admin', 'Manage complaints and users', '{"complaints": true, "users": true, "reports": true}'),
('Staff', 'Process complaints', '{"complaints": true, "reports": false}'),
('Viewer', 'View only access', '{"complaints_view": true}');

-- Insert default super admin (password: admin123 - CHANGE IN PRODUCTION!)
INSERT INTO admin_users (email, password_hash, full_name, role_id, unit) VALUES
('admin@deped-sanpedro.ph', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'System Administrator', 1, 'OSDS');

