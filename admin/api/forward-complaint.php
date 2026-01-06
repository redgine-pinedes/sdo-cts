<?php
/**
 * API: Forward Complaint to Unit
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../models/ComplaintAdmin.php';

$auth = auth();

// Check authentication and permission
if (!$auth->isLoggedIn()) {
    header('Location: /SDO-cts/admin/login.php');
    exit;
}

if (!$auth->hasPermission('complaints.forward')) {
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
$unit = $_POST['unit'] ?? '';
$notes = trim($_POST['notes'] ?? '');

if (!$complaintId || !$unit) {
    $_SESSION['flash_error'] = 'Invalid request.';
    header('Location: /SDO-cts/admin/complaints.php');
    exit;
}

// Validate unit
$validUnits = array_keys(UNITS);
if (!in_array($unit, $validUnits)) {
    $_SESSION['flash_error'] = 'Invalid unit selected.';
    header('Location: /SDO-cts/admin/complaint-view.php?id=' . $complaintId);
    exit;
}

try {
    $complaintModel = new ComplaintAdmin();
    $user = $auth->getUser();
    $complaint = $complaintModel->getById($complaintId);
    
    if (!$complaint) {
        throw new Exception('Complaint not found.');
    }
    
    $complaintModel->forward($complaintId, $unit, $notes, $user['id'], $user['full_name']);
    
    $auth->logActivity('forward', 'complaint', $complaintId, 
        "Forwarded complaint " . $complaint['reference_number'] . " to " . $unit);
    
    $_SESSION['flash_success'] = 'Complaint forwarded to ' . $unit . ' successfully.';
    
} catch (Exception $e) {
    $_SESSION['flash_error'] = $e->getMessage();
}

// Redirect back to complaint view
header('Location: /SDO-cts/admin/complaint-view.php?id=' . $complaintId);
exit;

