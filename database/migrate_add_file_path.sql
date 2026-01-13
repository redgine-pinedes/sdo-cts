-- Migration: Add file_path column to complaint_documents table
-- Purpose: Store relative paths (assets/uploads/images/ or assets/uploads/documents/) 
-- instead of absolute local device paths

USE sdo_cts;

-- Add file_path column to store relative paths
ALTER TABLE complaint_documents 
ADD COLUMN file_path VARCHAR(500) NOT NULL DEFAULT '' 
AFTER file_name;

-- Update existing records to use new path structure
-- This will need to be run manually or adjusted based on existing data
-- Example: UPDATE complaint_documents SET file_path = CONCAT('assets/uploads/documents/', file_name) WHERE category = 'supporting';
