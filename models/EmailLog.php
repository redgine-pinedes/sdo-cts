<?php
/**
 * Email Logs Model
 * SDO CTS - San Pedro Division Office Complaint Tracking System
 */

require_once __DIR__ . '/../config/database.php';

class EmailLog {
    private $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    /**
     * Get all email logs with pagination
     */
    public function getAll($filters = [], $page = 1, $perPage = 20) {
        $offset = ($page - 1) * $perPage;
        
        $sql = "SELECT el.*, c.reference_number 
                FROM email_logs el
                LEFT JOIN complaints c ON el.reference_id = c.id
                WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND el.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $sql .= " AND el.event_type LIKE ?";
            $params[] = '%' . $filters['event_type'] . '%';
        }

        if (!empty($filters['recipient'])) {
            $sql .= " AND el.recipient_email LIKE ?";
            $params[] = '%' . $filters['recipient'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(el.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(el.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $sql .= " ORDER BY el.created_at DESC LIMIT ? OFFSET ?";
        $params[] = $perPage;
        $params[] = $offset;

        return $this->db->query($sql, $params)->fetchAll();
    }

    /**
     * Get total count with filters
     */
    public function getCount($filters = []) {
        $sql = "SELECT COUNT(*) as total FROM email_logs el WHERE 1=1";
        $params = [];

        if (!empty($filters['status'])) {
            $sql .= " AND el.status = ?";
            $params[] = $filters['status'];
        }

        if (!empty($filters['event_type'])) {
            $sql .= " AND el.event_type LIKE ?";
            $params[] = '%' . $filters['event_type'] . '%';
        }

        if (!empty($filters['recipient'])) {
            $sql .= " AND el.recipient_email LIKE ?";
            $params[] = '%' . $filters['recipient'] . '%';
        }

        if (!empty($filters['date_from'])) {
            $sql .= " AND DATE(el.created_at) >= ?";
            $params[] = $filters['date_from'];
        }

        if (!empty($filters['date_to'])) {
            $sql .= " AND DATE(el.created_at) <= ?";
            $params[] = $filters['date_to'];
        }

        $result = $this->db->query($sql, $params)->fetch();
        return $result['total'];
    }

    /**
     * Get statistics
     */
    public function getStatistics() {
        $stats = [];

        // Total sent
        $result = $this->db->query("SELECT COUNT(*) as total FROM email_logs WHERE status = 'sent'")->fetch();
        $stats['total_sent'] = $result['total'];

        // Total failed
        $result = $this->db->query("SELECT COUNT(*) as total FROM email_logs WHERE status = 'failed'")->fetch();
        $stats['total_failed'] = $result['total'];

        // Total skipped
        $result = $this->db->query("SELECT COUNT(*) as total FROM email_logs WHERE status = 'skipped'")->fetch();
        $stats['total_skipped'] = $result['total'];

        // Today's emails
        $result = $this->db->query("SELECT COUNT(*) as total FROM email_logs WHERE DATE(created_at) = CURDATE()")->fetch();
        $stats['today'] = $result['total'];

        // By event type
        $result = $this->db->query("SELECT event_type, COUNT(*) as count FROM email_logs GROUP BY event_type ORDER BY count DESC LIMIT 5")->fetchAll();
        $stats['by_event_type'] = $result;

        return $stats;
    }

    /**
     * Get email log by ID
     */
    public function getById($id) {
        $sql = "SELECT el.*, c.reference_number 
                FROM email_logs el
                LEFT JOIN complaints c ON el.reference_id = c.id
                WHERE el.id = ?";
        return $this->db->query($sql, [$id])->fetch();
    }

    /**
     * Get recent failed emails
     */
    public function getRecentFailed($limit = 10) {
        $sql = "SELECT el.*, c.reference_number 
                FROM email_logs el
                LEFT JOIN complaints c ON el.reference_id = c.id
                WHERE el.status = 'failed'
                ORDER BY el.created_at DESC
                LIMIT ?";
        return $this->db->query($sql, [$limit])->fetchAll();
    }
}
