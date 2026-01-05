-- SDO CTS Database Schema
-- San Pedro Division Office Complaint Tracking System

CREATE DATABASE IF NOT EXISTS sdo_cts CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sdo_cts;

-- Complaints Table
CREATE TABLE IF NOT EXISTS complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reference_number VARCHAR(20) UNIQUE NOT NULL,
    
    -- Routing Fields
    referred_to ENUM('OSDS', 'SGOD', 'CID', 'Others') NOT NULL,
    referred_to_other VARCHAR(255) DEFAULT NULL,
    date_submitted DATETIME NOT NULL,
    
    -- Complainant Information
    complainant_name VARCHAR(255) NOT NULL,
    complainant_address TEXT NOT NULL,
    complainant_contact VARCHAR(20) NOT NULL,
    complainant_email VARCHAR(255) NOT NULL,
    
    -- Person/Office Involved
    involved_name VARCHAR(255) NOT NULL,
    involved_position VARCHAR(255) NOT NULL,
    involved_address TEXT NOT NULL,
    involved_school_office VARCHAR(255) NOT NULL,
    
    -- Complaint Details
    complaint_narration TEXT NOT NULL,
    desired_action TEXT NOT NULL,
    
    -- Certification
    certification_agreed TINYINT(1) NOT NULL DEFAULT 0,
    
    -- Signature
    printed_name VARCHAR(255) NOT NULL,
    signature_type ENUM('digital', 'typed') NOT NULL DEFAULT 'typed',
    signature_data TEXT DEFAULT NULL,
    date_signed DATE NOT NULL,
    
    -- Status Tracking
    status ENUM('pending', 'under_review', 'in_progress', 'resolved', 'closed') NOT NULL DEFAULT 'pending',
    is_locked TINYINT(1) NOT NULL DEFAULT 1,
    
    -- Timestamps
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    INDEX idx_reference (reference_number),
    INDEX idx_status (status),
    INDEX idx_date_submitted (date_submitted)
) ENGINE=InnoDB;

-- Supporting Documents Table
CREATE TABLE IF NOT EXISTS complaint_documents (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_type VARCHAR(50) NOT NULL,
    file_size INT NOT NULL,
    upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    INDEX idx_complaint_id (complaint_id)
) ENGINE=InnoDB;

-- Complaint Status History Table
CREATE TABLE IF NOT EXISTS complaint_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    status ENUM('pending', 'under_review', 'in_progress', 'resolved', 'closed') NOT NULL,
    notes TEXT DEFAULT NULL,
    updated_by VARCHAR(255) DEFAULT 'System',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    FOREIGN KEY (complaint_id) REFERENCES complaints(id) ON DELETE CASCADE,
    INDEX idx_complaint_id (complaint_id)
) ENGINE=InnoDB;

