# File Upload System - Implementation Summary

## ✅ Completed Changes

### 1. Created New Directory Structure
- ✅ `assets/uploads/images/` - For all image files (JPG, JPEG, PNG)
- ✅ `assets/uploads/documents/` - For all documents (PDF, etc.)
- ✅ Added `.gitkeep` files to preserve folders in git
- ✅ Updated `.gitignore` to exclude uploaded files but keep structure

### 2. Updated Database Schema
- ✅ Added `file_path` column to `complaint_documents` table in `database/schema.sql`
- ✅ Created migration script: `database/migrate_add_file_path.sql`
- ⚠️ **ACTION REQUIRED:** Run the migration SQL on your database

### 3. Updated File Upload Logic
**review.php** - Modified submission handling:
- Files are now organized by type (images vs documents)
- Filename format: `complaint_[id]_[category]_[timestamp]_[unique].ext`
- Example: `complaint_1023_supporting_1736832000_abc123.jpg`
- Stores relative paths: `assets/uploads/images/complaint_1023_supporting_1736832000_abc123.jpg`

### 4. Updated Database Model
**models/Complaint.php** - Enhanced `addDocument()` method:
- Now accepts `$filePath` parameter for relative path storage
- Backward compatible with existing code

### 5. Updated Admin Views
**admin/complaint-view.php** - Updated file URL generation:
- Uses `file_path` column from database
- Includes fallback to old structure for backward compatibility
- Updated all document sections: handwritten forms, valid IDs, supporting docs

### 6. Updated Email Service
**services/email/ComplaintNotification.php** - Updated attachment resolution:
- Uses `file_path` from database
- Fallback to old directory structure for existing files
- Maintains email functionality for all complaints

## 🎯 Key Benefits

1. **✅ Portable** - Files work on any device after repo pull
2. **✅ No Local Paths** - Only relative paths stored in database
3. **✅ Organized** - Images and documents separated
4. **✅ Complaint-Linked** - Filenames include complaint_id
5. **✅ Backward Compatible** - Old files still work
6. **✅ Deployment Ready** - Works after git pull/deployment

## 📋 Next Steps (Required)

### 1. Run Database Migration
Open MySQL/phpMyAdmin and run:
```sql
USE sdo_cts;
ALTER TABLE complaint_documents 
ADD COLUMN file_path VARCHAR(500) NOT NULL DEFAULT '' 
AFTER file_name;
```

Or from command line:
```bash
mysql -u root -p sdo_cts < database/migrate_add_file_path.sql
```

### 2. Test the System
1. Submit a new test complaint with files
2. Verify files appear in:
   - `assets/uploads/images/` (for JPG/PNG)
   - `assets/uploads/documents/` (for PDF)
3. Check the database `complaint_documents` table for relative paths
4. View the complaint in admin panel to verify files display correctly
5. Test file downloads and previews

### 3. Verify Permissions
Ensure web server has write permissions:
```bash
chmod 775 assets/uploads/images
chmod 775 assets/uploads/documents
```

Or for Apache on Windows (XAMPP):
- Verify the folders exist and are accessible

## 📁 File Structure

```
SDO-cts/
├── assets/
│   └── uploads/
│       ├── images/          ← New: All image files
│       │   ├── .gitkeep
│       │   └── complaint_*.jpg/png
│       └── documents/       ← New: All document files
│           ├── .gitkeep
│           └── complaint_*.pdf
├── uploads/
│   ├── temp/               ← Temporary upload staging
│   └── complaints/         ← Old structure (deprecated but working)
│       └── [id]/
└── database/
    ├── schema.sql          ← Updated with file_path column
    └── migrate_add_file_path.sql  ← Migration script
```

## 🔄 How It Works Now

### File Upload Flow:
1. User uploads file in complaint form
2. File temporarily stored in `uploads/temp/`
3. On submission:
   - File extension checked (JPG/PNG → images, PDF → documents)
   - File renamed: `complaint_[id]_[category]_[timestamp]_[unique].[ext]`
   - File moved to appropriate centralized folder
   - Relative path saved in database: `assets/uploads/images/...`
4. When viewing:
   - System reads `file_path` from database
   - Constructs URL: `/SDO-cts/assets/uploads/images/...`
   - Falls back to old structure if `file_path` is empty

### Example Database Record:
```
id: 42
complaint_id: 1023
file_name: complaint_1023_supporting_1736832000_abc123.jpg
file_path: assets/uploads/images/complaint_1023_supporting_1736832000_abc123.jpg
original_name: evidence-photo.jpg
category: supporting
```

## 🔍 Verification Checklist

- [ ] Database migration completed successfully
- [ ] New folders exist: `assets/uploads/images/` and `assets/uploads/documents/`
- [ ] Test complaint submitted with image and PDF
- [ ] Files appear in correct centralized folders
- [ ] Database shows relative paths in `file_path` column
- [ ] Files display correctly in admin panel
- [ ] File downloads work
- [ ] Old complaints (if any) still display files correctly
- [ ] Email notifications include attachments

## 📝 Notes

- **Old files** in `uploads/complaints/[id]/` will continue to work through fallback logic
- **No immediate migration** of existing files is required
- **New complaints** will use the new system automatically
- **Backward compatible** - system works with both old and new file locations

## 📖 Documentation

Full implementation guide available in: `FILE_UPLOAD_MIGRATION_GUIDE.md`

---

**Implementation Date:** January 13, 2026
**Status:** ✅ Complete - Ready for Testing
