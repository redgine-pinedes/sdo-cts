<?php
/**
 * API: Update Complaint Status
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../models/ComplaintAdmin.php';

$auth = auth();

// Check authentication and permission
if (!$auth->isLoggedIn()) {
    header('Location: /SDO-cts/admin/login.php');
    exit;
}

if (!$auth->hasPermission('complaints.update')) {
    header('HTTP/1.1 403 Forbidden');
    exit;
}

// Verify CSRF token
if (!$auth->verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid security token. Please try again.';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit;
}

$complaintId = intval($_POST['complaint_id'] ?? 0);
$status = $_POST['status'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!$complaintId || !$status) {
    $_SESSION['flash_error'] = 'Invalid request.';
    header('Location: /SDO-cts/admin/complaints.php');
    exit;
}

try {
    $complaintModel = new ComplaintAdmin();
    $user = $auth->getUser();
    
    $complaintModel->updateStatus($complaintId, $status, $notes, $user['id'], $user['full_name']);
    
    // Log activity
    $complaint = $complaintModel->getById($complaintId);
    $auth->logActivity('status_change', 'complaint', $complaintId, 
        "Changed status to " . STATUS_CONFIG[$status]['label'] . " for " . $complaint['reference_number']);
    
    $_SESSION['flash_success'] = 'Status updated successfully.';
    
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}

// Redirect back
$referer = $_SERVER['HTTP_REFERER'] ?? '/SDO-cts/admin/complaints.php';
header('Location: ' . $referer);
exit;

