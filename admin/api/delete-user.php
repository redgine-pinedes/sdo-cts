<?php
/**
 * API: Delete User(s)
 * Only Super Admin can delete users
 * No PIN verification required - instant deletion
 */

require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../../models/AdminUser.php';

header('Content-Type: application/json');

$auth = auth();

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized. Please log in.']);
    exit;
}

// Only Super Admin can delete users
if (!$auth->isSuperAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Access denied. Only Super Admin can delete users.']);
    exit;
}

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed.']);
    exit;
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    // Try form data
    $input = $_POST;
}

// Verify CSRF token
$csrfToken = $input['csrf_token'] ?? '';
if (!$auth->verifyCsrfToken($csrfToken)) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid security token. Please refresh the page and try again.']);
    exit;
}

// Validate input
$userIds = $input['user_ids'] ?? [];

// Ensure userIds is an array
if (!is_array($userIds)) {
    $userIds = [$userIds];
}

// Filter and validate user IDs
$userIds = array_filter(array_map('intval', $userIds), function($id) {
    return $id > 0;
});

if (empty($userIds)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'No valid user IDs provided.']);
    exit;
}

try {
    $userModel = new AdminUser();
    $currentUserId = $auth->getUserId();
    
    // Check if trying to delete self
    if (in_array($currentUserId, $userIds)) {
        // Remove current user from list
        $userIds = array_diff($userIds, [$currentUserId]);
        if (empty($userIds)) {
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'You cannot delete your own account.']);
            exit;
        }
    }
    
    // Get user info before deletion for logging
    $usersToDelete = [];
    $userNames = [];
    foreach ($userIds as $id) {
        $user = $userModel->getById($id);
        if ($user) {
            $usersToDelete[] = $user;
            $userNames[$id] = $user['full_name'];
        }
    }
    
    if (empty($usersToDelete)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'No valid users found to delete.']);
        exit;
    }
    
    // Delete users
    $deleted = 0;
    $skipped = 0;
    $errors = [];
    
    foreach ($usersToDelete as $user) {
        // Skip if trying to delete self
        if ($user['id'] == $currentUserId) {
            $skipped++;
            continue;
        }
        
        // Delete the user
        if ($userModel->delete($user['id'])) {
            $deleted++;
        } else {
            $errors[] = "Failed to delete user: {$user['full_name']}";
        }
    }
    
    // Log the deletion activity
    if ($deleted > 0) {
        $deletedNames = [];
        foreach ($userIds as $id) {
            if (isset($userNames[$id])) {
                $deletedNames[] = $userNames[$id];
            }
        }
        
        $description = $deleted === 1 
            ? "Deleted user: " . ($deletedNames[0] ?? 'Unknown')
            : "Deleted {$deleted} users: " . implode(', ', array_slice($deletedNames, 0, 5)) . ($deleted > 5 ? ' and more' : '');
        
        $auth->logActivity('delete', 'user', null, $description);
    }
    
    // Prepare response
    $result = [
        'deleted' => $deleted,
        'skipped' => $skipped,
        'total' => count($userIds),
        'errors' => $errors
    ];
    
    if ($deleted === count($userIds)) {
        // All users deleted successfully
        $message = $deleted === 1 
            ? 'User deleted successfully.' 
            : "{$deleted} users deleted successfully.";
        echo json_encode(['success' => true, 'message' => $message, 'result' => $result]);
    } elseif ($deleted > 0) {
        // Some users deleted
        $message = "{$deleted} of {$result['total']} users deleted.";
        if ($skipped > 0) {
            $message .= " {$skipped} skipped (cannot delete own account).";
        }
        echo json_encode(['success' => true, 'message' => $message, 'result' => $result]);
    } else {
        // No users deleted
        http_response_code(400);
        $errorMessage = !empty($errors) ? $errors[0] : 'Failed to delete users.';
        echo json_encode(['success' => false, 'message' => $errorMessage, 'result' => $result]);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'An error occurred: ' . $e->getMessage()]);
}
