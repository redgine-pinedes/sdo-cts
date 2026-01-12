<?php
/**
 * Email Service
 * SDO CTS - San Pedro Division Office Complaint Tracking System
 * 
 * Handles all email operations using PHPMailer with SMTP
 */

require_once __DIR__ . '/../../vendor/autoload.php';
require_once __DIR__ . '/../../config/mail_config.php';
require_once __DIR__ . '/../../config/database.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class EmailService {
    private $mailer;
    private $db;
    private $lastError = '';

    public function __construct() {
        $this->db = Database::getInstance();
        $this->initializeMailer();
    }

    /**
     * Initialize PHPMailer with SMTP configuration
     */
    private function initializeMailer() {
        $this->mailer = new PHPMailer(true);

        try {
            // Server settings
            $this->mailer->SMTPDebug = MAIL_DEBUG;
            $this->mailer->isSMTP();
            $this->mailer->Host = SMTP_HOST;
            $this->mailer->SMTPAuth = SMTP_AUTH;
            $this->mailer->Username = SMTP_USERNAME;
            $this->mailer->Password = SMTP_PASSWORD;
            
            // Set encryption
            if (SMTP_ENCRYPTION === 'tls') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
            } elseif (SMTP_ENCRYPTION === 'ssl') {
                $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
            }
            
            $this->mailer->Port = SMTP_PORT;
            $this->mailer->CharSet = MAIL_CHARSET;

            // Set default sender
            if (MAIL_FROM_ADDRESS) {
                $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            }

            // Set reply-to if configured
            if (MAIL_REPLY_TO) {
                $this->mailer->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
            }

            // HTML email
            $this->mailer->isHTML(true);

        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
        }
    }

    /**
     * Reset mailer for new email
     */
    private function resetMailer() {
        $this->mailer->clearAddresses();
        $this->mailer->clearAttachments();
        $this->mailer->clearCCs();
        $this->mailer->clearBCCs();
        $this->mailer->clearReplyTos();
        $this->lastError = '';

        // Re-set default sender
        if (MAIL_FROM_ADDRESS) {
            try {
                $this->mailer->setFrom(MAIL_FROM_ADDRESS, MAIL_FROM_NAME);
            } catch (Exception $e) {
                $this->lastError = $e->getMessage();
            }
        }

        // Re-set reply-to if configured
        if (MAIL_REPLY_TO) {
            try {
                $this->mailer->addReplyTo(MAIL_REPLY_TO, MAIL_FROM_NAME);
            } catch (Exception $e) {
                // Non-critical error
            }
        }
    }

    /**
     * Set custom sender address
     */
    public function setFrom($email, $name = null) {
        try {
            $this->mailer->setFrom($email, $name ?: MAIL_FROM_NAME);
            return true;
        } catch (Exception $e) {
            $this->lastError = $e->getMessage();
            return false;
        }
    }

    /**
     * Send email
     */
    public function send($to, $subject, $body, $eventType = 'general', $referenceId = null) {
        if (!MAIL_ENABLED) {
            $this->logEmail($to, $subject, $eventType, $referenceId, 'skipped', 'Email notifications disabled');
            return true; // Return true to not interrupt processing
        }

        // Check for duplicate notification
        if ($this->isDuplicateNotification($to, $eventType, $referenceId)) {
            $this->logEmail($to, $subject, $eventType, $referenceId, 'skipped', 'Duplicate notification prevented');
            return true;
        }

        $this->resetMailer();

        try {
            // Add recipient(s)
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress(trim($recipient));
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));

            $this->mailer->send();
            
            // Log successful send
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'sent');
            }

            return true;

        } catch (Exception $e) {
            $this->lastError = $this->mailer->ErrorInfo;
            
            // Log failed send
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'failed', $this->lastError);
            }

            return false;
        }
    }

    /**
     * Send email with attachment
     */
    public function sendWithAttachment($to, $subject, $body, $attachmentPath, $attachmentName = '', $eventType = 'general', $referenceId = null) {
        if (!MAIL_ENABLED) {
            return true;
        }

        $this->resetMailer();

        try {
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress(trim($recipient));
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));

            if (file_exists($attachmentPath)) {
                $this->mailer->addAttachment($attachmentPath, $attachmentName ?: basename($attachmentPath));
            }

            $this->mailer->send();
            
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'sent');
            }

            return true;

        } catch (Exception $e) {
            $this->lastError = $this->mailer->ErrorInfo;
            
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'failed', $this->lastError);
            }

            return false;
        }
    }

    /**
     * Send email with multiple attachments
     * @param string|array $to Recipient email(s)
     * @param string $subject Email subject
     * @param string $body Email body (HTML)
     * @param array $attachments Array of attachments, each with 'path' and optional 'name' keys
     * @param string $eventType Event type for logging
     * @param int|null $referenceId Reference ID for logging
     * @return bool Success status
     */
    public function sendWithMultipleAttachments($to, $subject, $body, $attachments = [], $eventType = 'general', $referenceId = null) {
        if (!MAIL_ENABLED) {
            $this->logEmail(is_array($to) ? $to[0] : $to, $subject, $eventType, $referenceId, 'skipped', 'Email notifications disabled');
            return true;
        }

        // Check for duplicate notification
        if ($this->isDuplicateNotification($to, $eventType, $referenceId)) {
            $this->logEmail(is_array($to) ? $to[0] : $to, $subject, $eventType, $referenceId, 'skipped', 'Duplicate notification prevented');
            return true;
        }

        $this->resetMailer();

        try {
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->mailer->addAddress(trim($recipient));
            }

            $this->mailer->Subject = $subject;
            $this->mailer->Body = $body;
            $this->mailer->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<br />', '</p>'], "\n", $body));

            // Add all attachments
            foreach ($attachments as $attachment) {
                if (isset($attachment['path']) && file_exists($attachment['path'])) {
                    $attachmentName = $attachment['name'] ?? basename($attachment['path']);
                    $this->mailer->addAttachment($attachment['path'], $attachmentName);
                }
            }

            $this->mailer->send();
            
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'sent');
            }

            return true;

        } catch (Exception $e) {
            $this->lastError = $this->mailer->ErrorInfo;
            
            $recipients = is_array($to) ? $to : [$to];
            foreach ($recipients as $recipient) {
                $this->logEmail(trim($recipient), $subject, $eventType, $referenceId, 'failed', $this->lastError);
            }

            return false;
        }
    }

    /**
     * Check if notification was already sent (prevent duplicates)
     */
    private function isDuplicateNotification($to, $eventType, $referenceId) {
        if (!$referenceId) {
            return false;
        }

        $recipient = is_array($to) ? $to[0] : $to;

        try {
            $sql = "SELECT id FROM email_logs 
                    WHERE recipient_email = ? 
                    AND event_type = ? 
                    AND reference_id = ? 
                    AND status = 'sent'
                    AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)
                    LIMIT 1";
            
            $result = $this->db->query($sql, [trim($recipient), $eventType, $referenceId])->fetch();
            return !empty($result);
        } catch (Exception $e) {
            // If table doesn't exist, allow email
            return false;
        }
    }

    /**
     * Log email sending attempt
     */
    private function logEmail($recipient, $subject, $eventType, $referenceId, $status, $errorMessage = null) {
        try {
            $sql = "INSERT INTO email_logs (recipient_email, subject, event_type, reference_id, status, error_message)
                    VALUES (?, ?, ?, ?, ?, ?)";
            $this->db->query($sql, [$recipient, $subject, $eventType, $referenceId, $status, $errorMessage]);
        } catch (Exception $e) {
            // Log to error_log if database logging fails
            error_log("Email Log Error: " . $e->getMessage());
        }
    }

    /**
     * Get last error message
     */
    public function getLastError() {
        return $this->lastError;
    }

    /**
     * Test SMTP connection
     */
    public function testConnection() {
        try {
            $this->mailer->smtpConnect();
            $this->mailer->smtpClose();
            return ['success' => true, 'message' => 'SMTP connection successful'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
