# SDO CTS - Complainant Information Enhancement

## Summary of Changes
This implementation adds required complainant contact information (Name, Email, Contact Number) to the review page, stores them in the database, and displays them in all email notifications.

## Files Modified

### 1. review.php
**Changes:**
- Added a new "Complainant Information" section with three required input fields:
  - Full Name
  - Email Address
  - Contact Number
- Implemented client-side validation:
  - Name must be at least 2 characters
  - Email must be valid format
  - Contact must have at least 7 digits
  - All fields required before form submission
- Added hidden input fields to capture and transmit the data
- Enhanced JavaScript validation to prevent submit if any field is empty
- Updated form submission handler to:
  - Validate all three fields on the server side
  - Return error if any field is missing
  - Store values in session/database
  - Pass data to email notifications

### 2. models/Complaint.php
**Changes:**
- Updated the `create()` method to include three new database fields:
  - `complaint_name` - Complainant's full name from review page
  - `complaint_email` - Complainant's email from review page
  - `complaint_contact` - Complainant's contact number from review page
- These fields are now inserted into the database when a complaint is created

### 3. database/migration_add_complaint_contact_fields.sql
**Created:**
- New migration file to add the three new columns to the complaints table:
  - `complaint_name VARCHAR(255)`
  - `complaint_email VARCHAR(255)`
  - `complaint_contact VARCHAR(20)`
- Includes indexes for email and name for faster lookups

### 4. services/email/ComplaintNotification.php
**Changes:**
- Updated `sendComplaintSubmittedNotification()` to:
  - Validate that all three required fields (complaint_name, complaint_email, complaint_contact) are present
  - Skip email sending and log error if any field is missing
  - Pass complaint data to both complainant and admin notification methods
- Updated `sendToComplainant()` signature to accept complaint data
  - Now passes complaint_name, complaint_email, complaint_contact to email template
- Updated `sendToAdmin()` to use the new complaint_* fields instead of form fields
- Updated default inline email templates to display Complainant Information section

### 5. services/email/templates/complaint_submitted_complainant.html
**Changes:**
- Added "Complainant Information" section displaying:
  - Name: {{complaint_name}}
  - Email: {{complaint_email}} (as clickable mailto link)
  - Contact: {{complaint_contact}}
- Inserted before "What Happens Next?" section

### 6. services/email/templates/complaint_submitted_admin.html
**Created:**
- New email template for admin notifications
- Includes "Complainant Information" section displaying:
  - Name: {{complaint_name}}
  - Email: {{complaint_email}} (as clickable mailto link)
  - Contact: {{complaint_contact}}
- Maintains existing structure with complaint preview and admin panel link

## Data Flow

1. **Submission (review.php)**
   - Complainant fills in Name, Email, Contact fields
   - Client-side validation prevents empty submissions
   - Form posts hidden field values to review.php

2. **Validation (review.php)**
   - Server-side validation ensures all fields are non-empty
   - Error returned if validation fails
   - Data stored in session and passed to Complaint model

3. **Storage (models/Complaint.php)**
   - New fields stored in database (complaint_name, complaint_email, complaint_contact)
   - Reference number generated
   - Complaint record created

4. **Notification (services/email/ComplaintNotification.php)**
   - Validates that all three fields exist
   - Skips email sending if any field is missing (logs error)
   - Passes data to email templates
   - Sends to complainant with attachments
   - Sends to admin without attachments

5. **Email Display**
   - Both complainant and admin emails display the Complainant Information section
   - Table format with Name, Email (linked), and Contact

## Validation Rules

### Client-Side (JavaScript):
- Name: At least 2 characters, non-empty
- Email: Valid email format (regex validation)
- Contact: At least 7 digits

### Server-Side (PHP):
- All three fields required (non-empty check)
- Error message returned if validation fails

### Email Sending:
- Email notification skipped if any required field is missing
- Error logged for debugging

## Database Migration

Run the migration script to add the new columns:
```bash
mysql -u[username] -p[password] < database/migration_add_complaint_contact_fields.sql
```

Or execute in phpMyAdmin:
```sql
USE sdo_cts;
ALTER TABLE complaints 
ADD COLUMN complaint_name VARCHAR(255) DEFAULT NULL,
ADD COLUMN complaint_email VARCHAR(255) DEFAULT NULL,
ADD COLUMN complaint_contact VARCHAR(20) DEFAULT NULL;
```

## Testing Checklist

- [ ] Attempt to submit without filling Name field - should fail
- [ ] Attempt to submit without filling Email field - should fail
- [ ] Attempt to submit without filling Contact field - should fail
- [ ] Attempt to submit with invalid email - should fail
- [ ] Attempt to submit with contact number < 7 digits - should fail
- [ ] Successful submission with all fields filled correctly
- [ ] Database stores all three fields correctly
- [ ] Complainant email received with Complainant Information section
- [ ] Admin email received with Complainant Information section
- [ ] Email not sent if any field is missing (test edge case)

## Security Considerations

- All user input is escaped with `htmlspecialchars()` in email templates
- Email validation prevents invalid addresses from being stored
- Server-side validation prevents bypass of client-side validation
- Hidden fields used to prevent manipulation of input fields (pre-filled with validated data)

## Backward Compatibility

- Existing complaint records will have NULL values for the new fields
- Email sending gracefully handles missing fields (logs error, skips email)
- No breaking changes to existing functionality
