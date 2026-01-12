-- Email Logs Table
-- SDO CTS - San Pedro Division Office Complaint Tracking System
-- Tracks all email notifications sent by the system

CREATE TABLE IF NOT EXISTS email_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    recipient_email VARCHAR(255) NOT NULL,
    subject VARCHAR(500) NOT NULL,
    event_type VARCHAR(100) NOT NULL COMMENT 'Type of event that triggered the email (complaint_submitted, complaint_resolved, etc.)',
    reference_id INT DEFAULT NULL COMMENT 'Reference to complaint ID if applicable',
    status ENUM('sent', 'failed', 'skipped') NOT NULL DEFAULT 'sent',
    error_message TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_recipient (recipient_email),
    INDEX idx_event_type (event_type),
    INDEX idx_reference_id (reference_id),
    INDEX idx_status (status),
    INDEX idx_created_at (created_at),
    INDEX idx_duplicate_check (recipient_email, event_type, reference_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
