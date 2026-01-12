<?php
/**
 * Complaint Email Notifications
 * SDO CTS - San Pedro Division Office Complaint Tracking System
 * 
 * Handles specific email notifications for complaint events
 */

require_once __DIR__ . '/EmailService.php';
require_once __DIR__ . '/../../config/admin_config.php';

class ComplaintNotification {
    private $emailService;

    public function __construct() {
        $this->emailService = new EmailService();
    }

    /**
     * Set custom sender email address
     */
    public function setFromAddress($email, $name = null) {
        return $this->emailService->setFrom($email, $name);
    }

    /**
     * Send notification when complaint is submitted
     * Notifies both complainant and admin
     */
    public function sendComplaintSubmittedNotification($complaintData) {
        $referenceNumber = $complaintData['reference_number'];
        $complainantEmail = $complaintData['email_address'];
        $complainantName = $complaintData['name_pangalan'];
        $submittedDate = date('F j, Y \a\t g:i A');
        $complaintId = $complaintData['id'] ?? null;

        // Send to complainant
        $this->sendToComplainant($complainantEmail, $complainantName, $referenceNumber, $submittedDate, $complaintId);

        // Send to admin/assigned office
        $this->sendToAdmin($complaintData, $submittedDate);

        return true;
    }

    /**
     * Send notification to complainant when complaint is submitted
     */
    private function sendToComplainant($email, $name, $referenceNumber, $date, $complaintId) {
        $subject = "Complaint Submitted - Reference: {$referenceNumber}";
        
        $body = $this->getEmailTemplate('complaint_submitted_complainant', [
            'name' => $name,
            'reference_number' => $referenceNumber,
            'date' => $date,
            'tracking_url' => TRACKING_URL
        ]);

        return $this->emailService->send(
            $email,
            $subject,
            $body,
            'complaint_submitted_complainant',
            $complaintId
        );
    }

    /**
     * Send notification to admin when complaint is submitted
     */
    private function sendToAdmin($complaintData, $date) {
        $adminEmails = $this->getAdminEmails($complaintData['referred_to'] ?? 'OSDS');
        
        if (empty($adminEmails)) {
            return false;
        }

        $subject = "New Complaint Received - Reference: {$complaintData['reference_number']}";
        
        $body = $this->getEmailTemplate('complaint_submitted_admin', [
            'reference_number' => $complaintData['reference_number'],
            'complainant_name' => $complaintData['name_pangalan'],
            'complainant_email' => $complaintData['email_address'],
            'complainant_contact' => $complaintData['contact_number'] ?? 'N/A',
            'referred_to' => $complaintData['referred_to'] ?? 'OSDS',
            'date' => $date,
            'complaint_preview' => $this->truncateText($complaintData['narration_complaint'] ?? '', 200),
            'admin_url' => SYSTEM_BASE_URL . '/admin/complaints.php'
        ]);

        foreach ($adminEmails as $adminEmail) {
            $this->emailService->send(
                $adminEmail,
                $subject,
                $body,
                'complaint_submitted_admin',
                $complaintData['id'] ?? null
            );
        }

        return true;
    }

    /**
     * Send notification when complaint is resolved
     */
    public function sendComplaintResolvedNotification($complaintData, $resolutionNotes = '') {
        $referenceNumber = $complaintData['reference_number'];
        $complainantEmail = $complaintData['email_address'];
        $complainantName = $complaintData['name_pangalan'];
        $resolvedDate = date('F j, Y \a\t g:i A');
        $complaintId = $complaintData['id'] ?? null;

        $subject = "Complaint Resolved - Reference: {$referenceNumber}";
        
        $body = $this->getEmailTemplate('complaint_resolved', [
            'name' => $complainantName,
            'reference_number' => $referenceNumber,
            'date' => $resolvedDate,
            'resolution_notes' => $resolutionNotes ?: 'Your complaint has been reviewed and resolved.',
            'tracking_url' => TRACKING_URL
        ]);

        return $this->emailService->send(
            $complainantEmail,
            $subject,
            $body,
            'complaint_resolved',
            $complaintId
        );
    }

    /**
     * Send notification for status changes (optional)
     */
    public function sendStatusChangeNotification($complaintData, $newStatus, $notes = '') {
        $referenceNumber = $complaintData['reference_number'];
        $complainantEmail = $complaintData['email_address'];
        $complainantName = $complaintData['name_pangalan'];
        $updateDate = date('F j, Y \a\t g:i A');
        $complaintId = $complaintData['id'] ?? null;

        $statusLabel = STATUS_CONFIG[$newStatus]['label'] ?? ucfirst($newStatus);
        
        $subject = "Complaint Status Update - Reference: {$referenceNumber}";
        
        $body = $this->getEmailTemplate('status_update', [
            'name' => $complainantName,
            'reference_number' => $referenceNumber,
            'status' => $statusLabel,
            'notes' => $notes,
            'date' => $updateDate,
            'tracking_url' => TRACKING_URL
        ]);

        return $this->emailService->send(
            $complainantEmail,
            $subject,
            $body,
            'status_change_' . $newStatus,
            $complaintId
        );
    }

    /**
     * Get admin emails for notification
     */
    private function getAdminEmails($referredTo = null) {
        $emails = [];

        // Get from environment variable
        if (ADMIN_EMAIL_RECIPIENTS) {
            $configEmails = array_map('trim', explode(',', ADMIN_EMAIL_RECIPIENTS));
            $emails = array_merge($emails, $configEmails);
        }

        // Optionally, get from database based on unit assignment
        // This can be expanded to fetch emails based on $referredTo unit

        return array_unique(array_filter($emails));
    }

    /**
     * Get email template with variable substitution
     */
    private function getEmailTemplate($templateName, $variables = []) {
        $templatePath = EMAIL_TEMPLATES_PATH . $templateName . '.html';
        
        if (file_exists($templatePath)) {
            $template = file_get_contents($templatePath);
        } else {
            // Use default inline templates
            $template = $this->getDefaultTemplate($templateName);
        }

        // Replace variables
        foreach ($variables as $key => $value) {
            $template = str_replace('{{' . $key . '}}', htmlspecialchars($value ?? ''), $template);
        }

        return $template;
    }

    /**
     * Default email templates (fallback)
     */
    private function getDefaultTemplate($templateName) {
        $header = $this->getEmailHeader();
        $footer = $this->getEmailFooter();

        switch ($templateName) {
            case 'complaint_submitted_complainant':
                return $header . '
                <h2 style="color: #1e40af; margin-bottom: 20px;">Complaint Submitted Successfully</h2>
                
                <p>Dear <strong>{{name}}</strong>,</p>
                
                <p>Your complaint has been successfully submitted to the San Pedro Division Office Complaint Tracking System.</p>
                
                <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #64748b;"><strong>Event Type:</strong> Complaint Submission</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #64748b;"><strong>Date & Time:</strong> {{date}}</p>
                    <p style="margin: 10px 0 0 0;"><strong>Reference Number:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold; color: #0284c7; font-family: monospace;">{{reference_number}}</p>
                </div>
                
                <p><strong>Important:</strong> Please save this reference number. You will need it to track the status of your complaint.</p>
                
                <p>You can track your complaint status at any time by visiting:</p>
                <p><a href="{{tracking_url}}" style="color: #0284c7;">{{tracking_url}}</a></p>
                
                <h3 style="color: #1e40af; margin-top: 30px;">What Happens Next?</h3>
                <ol style="color: #475569;">
                    <li>Your complaint will be reviewed by the appropriate office.</li>
                    <li>You may be contacted for additional information if needed.</li>
                    <li>You will receive email updates as your complaint progresses.</li>
                </ol>
                ' . $footer;

            case 'complaint_submitted_admin':
                return $header . '
                <h2 style="color: #1e40af; margin-bottom: 20px;">New Complaint Received</h2>
                
                <p>A new complaint has been submitted to the system and requires attention.</p>
                
                <div style="background: #fef3c7; border-left: 4px solid #f59e0b; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #92400e;"><strong>Event Type:</strong> New Complaint Submission</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #92400e;"><strong>Date & Time:</strong> {{date}}</p>
                    <p style="margin: 10px 0 0 0;"><strong>Reference Number:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 20px; font-weight: bold; color: #b45309; font-family: monospace;">{{reference_number}}</p>
                </div>
                
                <h3 style="color: #1e40af;">Complainant Information</h3>
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 20px;">
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b; width: 40%;"><strong>Name:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{complainant_name}}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;"><strong>Email:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{complainant_email}}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;"><strong>Contact:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{complainant_contact}}</td>
                    </tr>
                    <tr>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0; color: #64748b;"><strong>Referred To:</strong></td>
                        <td style="padding: 8px; border-bottom: 1px solid #e2e8f0;">{{referred_to}}</td>
                    </tr>
                </table>
                
                <h3 style="color: #1e40af;">Complaint Preview</h3>
                <p style="background: #f8fafc; padding: 15px; border-radius: 4px; color: #475569;">{{complaint_preview}}</p>
                
                <p style="margin-top: 25px;">
                    <a href="{{admin_url}}" style="display: inline-block; background: #1e40af; color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; font-weight: bold;">View in Admin Panel</a>
                </p>
                ' . $footer;

            case 'complaint_resolved':
                return $header . '
                <h2 style="color: #059669; margin-bottom: 20px;">Complaint Resolved</h2>
                
                <p>Dear <strong>{{name}}</strong>,</p>
                
                <p>We are pleased to inform you that your complaint has been <strong style="color: #059669;">resolved</strong>.</p>
                
                <div style="background: #d1fae5; border-left: 4px solid #10b981; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #065f46;"><strong>Event Type:</strong> Complaint Resolved</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #065f46;"><strong>Date & Time:</strong> {{date}}</p>
                    <p style="margin: 10px 0 0 0;"><strong>Reference Number:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold; color: #059669; font-family: monospace;">{{reference_number}}</p>
                </div>
                
                <h3 style="color: #1e40af;">Resolution Details</h3>
                <p style="background: #f8fafc; padding: 15px; border-radius: 4px; color: #475569;">{{resolution_notes}}</p>
                
                <p>You can view the complete details and history of your complaint at:</p>
                <p><a href="{{tracking_url}}" style="color: #0284c7;">{{tracking_url}}</a></p>
                
                <p style="margin-top: 20px;">Thank you for using the SDO CTS complaint tracking system. If you have any further concerns, please do not hesitate to file another complaint.</p>
                ' . $footer;

            case 'status_update':
                return $header . '
                <h2 style="color: #1e40af; margin-bottom: 20px;">Complaint Status Update</h2>
                
                <p>Dear <strong>{{name}}</strong>,</p>
                
                <p>The status of your complaint has been updated.</p>
                
                <div style="background: #f0f9ff; border-left: 4px solid #0284c7; padding: 15px; margin: 20px 0;">
                    <p style="margin: 0; font-size: 14px; color: #64748b;"><strong>Event Type:</strong> Status Update</p>
                    <p style="margin: 5px 0 0 0; font-size: 14px; color: #64748b;"><strong>Date & Time:</strong> {{date}}</p>
                    <p style="margin: 10px 0 0 0;"><strong>Reference Number:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 24px; font-weight: bold; color: #0284c7; font-family: monospace;">{{reference_number}}</p>
                    <p style="margin: 15px 0 0 0;"><strong>New Status:</strong></p>
                    <p style="margin: 5px 0 0 0; font-size: 18px; font-weight: bold; color: #1e40af;">{{status}}</p>
                </div>
                
                <h3 style="color: #1e40af;">Notes</h3>
                <p style="background: #f8fafc; padding: 15px; border-radius: 4px; color: #475569;">{{notes}}</p>
                
                <p>You can track the full history and status of your complaint at:</p>
                <p><a href="{{tracking_url}}" style="color: #0284c7;">{{tracking_url}}</a></p>
                ' . $footer;

            default:
                return $header . '<p>{{content}}</p>' . $footer;
        }
    }

    /**
     * Email header template
     */
    private function getEmailHeader() {
        return '<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
</head>
<body style="margin: 0; padding: 0; font-family: Arial, sans-serif; background-color: #f4f4f4;">
    <table width="100%" cellpadding="0" cellspacing="0" style="background-color: #f4f4f4; padding: 20px;">
        <tr>
            <td align="center">
                <table width="600" cellpadding="0" cellspacing="0" style="background-color: #ffffff; border-radius: 8px; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
                    <!-- Header -->
                    <tr>
                        <td style="background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); padding: 30px; border-radius: 8px 8px 0 0; text-align: center;">
                            <h1 style="color: #ffffff; margin: 0; font-size: 24px;">SDO CTS</h1>
                            <p style="color: #bfdbfe; margin: 5px 0 0 0; font-size: 14px;">San Pedro Division Office<br>Complaint Tracking System</p>
                        </td>
                    </tr>
                    <!-- Content -->
                    <tr>
                        <td style="padding: 30px;">';
    }

    /**
     * Email footer template
     */
    private function getEmailFooter() {
        $year = date('Y');
        return '
                        </td>
                    </tr>
                    <!-- Footer -->
                    <tr>
                        <td style="background-color: #f8fafc; padding: 20px; border-radius: 0 0 8px 8px; text-align: center; border-top: 1px solid #e2e8f0;">
                            <p style="margin: 0; color: #64748b; font-size: 12px;">This is an automated message from the SDO CTS.</p>
                            <p style="margin: 5px 0 0 0; color: #64748b; font-size: 12px;">Department of Education - San Pedro Division Office</p>
                            <p style="margin: 10px 0 0 0; color: #94a3b8; font-size: 11px;">© ' . $year . ' SDO CTS. All rights reserved.</p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>';
    }

    /**
     * Truncate text with ellipsis
     */
    private function truncateText($text, $maxLength = 200) {
        $text = strip_tags($text);
        if (strlen($text) <= $maxLength) {
            return $text;
        }
        return substr($text, 0, $maxLength) . '...';
    }

    /**
     * Get email service for advanced operations
     */
    public function getEmailService() {
        return $this->emailService;
    }
}
