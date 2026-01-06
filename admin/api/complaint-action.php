<?php
/**
 * API: Complaint Accept/Return Actions
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../models/ComplaintAdmin.php';

$auth = auth();

// Check authentication and permission
if (!$auth->isLoggedIn()) {
    header('Location: /SDO-cts/admin/login.php');
    exit;
}

if (!$auth->hasPermission('complaints.accept')) {
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
$action = $_POST['action'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!$complaintId || !in_array($action, ['accept', 'return'])) {
    $_SESSION['flash_error'] = 'Invalid request.';
    header('Location: /SDO-cts/admin/complaints.php');
    exit;
}

try {
    $complaintModel = new ComplaintAdmin();
    $user = $auth->getUser();
    $complaint = $complaintModel->getById($complaintId);
    
    if (!$complaint) {
        throw new Exception('Complaint not found.');
    }
    
    if ($action === 'accept') {
        $complaintModel->accept($complaintId, $notes, $user['id'], $user['full_name']);
        $auth->logActivity('accept', 'complaint', $complaintId, 
            "Accepted complaint " . $complaint['reference_number']);
        $_SESSION['flash_success'] = 'Complaint accepted successfully.';
        
    } elseif ($action === 'return') {
        if (empty($notes)) {
            throw new Exception('Please provide a reason for returning the complaint.');
        }
        $complaintModel->returnComplaint($complaintId, $notes, $user['id'], $user['full_name']);
        $auth->logActivity('return', 'complaint', $complaintId, 
            "Returned complaint " . $complaint['reference_number'] . ": " . $notes);
        $_SESSION['flash_success'] = 'Complaint returned successfully.';
    }
    
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}

// Redirect back to complaint view
header('Location: /SDO-cts/admin/complaint-view.php?id=' . $complaintId);
exit;

