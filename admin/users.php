<?php
/**
 * User Management Page
 * SDO CTS - San Pedro Division Office Complaint Tracking System
 * Access restricted to Super Admin only
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../models/AdminUser.php';

$auth = auth();
$auth->requireLogin();

// Only Super Admin can access User Management
if (!$auth->isSuperAdmin()) {
    header('HTTP/1.1 403 Forbidden');
    include __DIR__ . '/403.php';
    exit;
}

$userModel = new AdminUser();

// Get filter parameters
$filters = [
    'role_id' => $_GET['role_id'] ?? '',
    'is_active' => isset($_GET['is_active']) ? $_GET['is_active'] : '',
    'unit' => $_GET['unit'] ?? '',
    'search' => $_GET['search'] ?? ''
];

// Get users
$users = $userModel->getAll($filters);
$roles = $userModel->getRoles();
$registeredUnits = $userModel->getRegisteredUnits();

// Create role lookup
$rolesLookup = [];
foreach ($roles as $role) {
    $rolesLookup[$role['id']] = $role;
}

$units = UNITS;

include __DIR__ . '/includes/header.php';
?>

<div class="page-header">
    <div class="page-header-left">
        <p class="page-subtitle">Manage admin users and their access levels</p>
    </div>
    <div class="page-header-right">
        <?php if ($auth->isSuperAdmin()): ?>
        <button type="button" class="btn btn-outline" id="toggleDeleteModeBtn" onclick="toggleDeleteMode()">
            <i class="fas fa-trash-alt"></i> Select Users to Delete
        </button>
        <button type="button" class="btn btn-danger" id="deleteSelectedBtn" style="display: none;" onclick="openDeleteModal()">
            <i class="fas fa-trash"></i> Delete Selected (<span id="selectedCount">0</span>)
        </button>
        <button type="button" class="btn btn-outline" id="cancelDeleteModeBtn" style="display: none;" onclick="toggleDeleteMode()">
            <i class="fas fa-times"></i> Cancel
        </button>
        <button type="button" class="btn btn-primary" onclick="openUserModal()">
            <i class="fas fa-user-plus"></i> Add User
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- Filters -->
<div class="filter-bar">
    <form method="GET" action="" class="filter-form">
        <div class="filter-group">
            <label>Search</label>
            <input type="text" name="search" value="<?php echo htmlspecialchars($filters['search']); ?>" 
                   placeholder="Name or email..." class="filter-input">
        </div>
        
        <div class="filter-group">
            <label>Role</label>
            <select name="role_id" class="filter-select">
                <option value="">All Roles</option>
                <?php foreach ($roles as $role): ?>
                <option value="<?php echo $role['id']; ?>" <?php echo $filters['role_id'] == $role['id'] ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($role['name']); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Status</label>
            <select name="is_active" class="filter-select">
                <option value="">All Status</option>
                <option value="1" <?php echo $filters['is_active'] === '1' ? 'selected' : ''; ?>>Active</option>
                <option value="0" <?php echo $filters['is_active'] === '0' ? 'selected' : ''; ?>>Inactive</option>
            </select>
        </div>
        
        <div class="filter-group">
            <label>Unit</label>
            <select name="unit" class="filter-select">
                <option value="">All Units</option>
                <?php foreach ($registeredUnits as $unit): ?>
                <option value="<?php echo htmlspecialchars($unit); ?>" <?php echo $filters['unit'] === $unit ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($unit); ?><?php echo isset(UNITS[$unit]) ? ' - ' . htmlspecialchars(UNITS[$unit]) : ''; ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="filter-actions">
            <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filter</button>
            <a href="/SDO-cts/admin/users.php" class="btn btn-outline btn-sm">Clear</a>
        </div>
    </form>
</div>

<!-- Users Table -->
<div class="data-card">
    <?php if (empty($users)): ?>
    <div class="empty-state">
        <span class="empty-icon"><i class="fas fa-users"></i></span>
        <h3>No users found</h3>
        <p>Try adjusting your filters or add a new user</p>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="data-table" id="usersTable">
            <thead>
                <tr>
                    <?php if ($auth->isSuperAdmin()): ?>
                    <th class="checkbox-column delete-mode-column" style="display: none;">
                        <input type="checkbox" id="selectAllUsers" onchange="toggleSelectAll(this)" title="Select all users">
                    </th>
                    <?php endif; ?>
                    <th>User</th>
                    <th>Role</th>
                    <th>Unit</th>
                    <th>Status</th>
                    <th>Last Login</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $user): ?>
                <tr data-user-id="<?php echo $user['id']; ?>">
                    <?php if ($auth->isSuperAdmin()): ?>
                    <td class="checkbox-column delete-mode-column" style="display: none;">
                        <?php if ($user['id'] !== $auth->getUserId()): ?>
                        <input type="checkbox" class="user-checkbox" value="<?php echo $user['id']; ?>" 
                               data-user-name="<?php echo htmlspecialchars($user['full_name']); ?>"
                               onchange="updateSelectedCount()">
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                    <td>
                        <div class="user-cell">
                            <?php if (!empty($user['avatar_url'])): ?>
                            <img src="<?php echo htmlspecialchars($user['avatar_url']); ?>" alt="Avatar" class="user-avatar-sm">
                            <?php else: ?>
                            <div class="user-avatar-placeholder-sm">
                                <?php echo strtoupper(substr($user['full_name'], 0, 1)); ?>
                            </div>
                            <?php endif; ?>
                            <div>
                                <div class="cell-primary"><?php echo htmlspecialchars($user['full_name']); ?></div>
                                <div class="cell-secondary"><?php echo htmlspecialchars($user['email']); ?></div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="role-badge role-<?php echo strtolower(str_replace(' ', '-', $user['role_name'])); ?>">
                            <?php echo htmlspecialchars($user['role_name']); ?>
                        </span>
                    </td>
                    <td><?php echo htmlspecialchars($user['unit'] ?? '-'); ?></td>
                    <td>
                        <?php if ($user['is_active']): ?>
                        <span class="status-badge status-active">Active</span>
                        <?php else: ?>
                        <span class="status-badge status-inactive">Inactive</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'Never'; ?>
                    </td>
                    <td>
                        <div class="cell-primary"><?php echo date('M j, Y', strtotime($user['created_at'])); ?></div>
                        <?php if ($user['created_by_name']): ?>
                        <div class="cell-secondary">by <?php echo htmlspecialchars($user['created_by_name']); ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="action-buttons">
                            <?php if ($auth->isSuperAdmin()): ?>
                            <button type="button" class="btn btn-sm btn-outline" 
                                    onclick='openEditModal(<?php echo json_encode($user); ?>)'
                                    title="Edit User">Edit</button>
                            
                            <?php if ($user['id'] !== $auth->getUserId()): ?>
                                <?php if ($user['is_active']): ?>
                                <button type="button" class="btn btn-sm btn-outline" title="Deactivate" 
                                        onclick="openStatusModal(<?php echo $user['id']; ?>, 'deactivate', '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')">Deactivate</button>
                                <?php else: ?>
                                <button type="button" class="btn btn-sm btn-outline" title="Activate"
                                        onclick="openStatusModal(<?php echo $user['id']; ?>, 'activate', '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')">Activate</button>
                                <?php endif; ?>
                                <button type="button" class="btn btn-sm btn-danger-outline" title="Delete User"
                                        onclick="openDeleteModal(<?php echo $user['id']; ?>, '<?php echo htmlspecialchars(addslashes($user['full_name'])); ?>')">
                                    <i class="fas fa-trash"></i>
                                </button>
                            <?php endif; ?>
                            <?php else: ?>
                            <span class="text-muted">—</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php if ($auth->isSuperAdmin()): ?>
<!-- Add/Edit User Modal -->
<div class="modal-overlay" id="userModal">
    <div class="modal modal-lg">
        <div class="modal-header">
            <h3 id="userModalTitle">Add User</h3>
            <button type="button" class="modal-close" onclick="closeModal('userModal')">&times;</button>
        </div>
        <form method="POST" action="/SDO-cts/admin/api/save-user.php" id="userForm">
            <div class="modal-body">
                <input type="hidden" name="user_id" id="userId" value="">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Full Name <span class="required">*</span></label>
                        <input type="text" name="full_name" id="userFullName" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Email <span class="required">*</span></label>
                        <input type="email" name="email" id="userEmail" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Role <span class="required">*</span></label>
                        <select name="role_id" id="userRole" class="form-control" required>
                            <?php foreach ($roles as $role): ?>
                            <option value="<?php echo $role['id']; ?>"><?php echo htmlspecialchars($role['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Unit/Section</label>
                        <select name="unit" id="userUnit" class="form-control">
                            <option value="">-- Select Unit --</option>
                            <?php foreach ($units as $key => $name): ?>
                            <option value="<?php echo $key; ?>"><?php echo $key; ?> - <?php echo $name; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label class="form-label">Password <span id="passwordReq" class="required">*</span></label>
                        <input type="password" name="password" id="userPassword" class="form-control" minlength="8">
                        <small class="form-hint" id="passwordHint">Minimum 8 characters. Leave blank to keep current password when editing.</small>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Confirm Password</label>
                        <input type="password" name="password_confirm" id="userPasswordConfirm" class="form-control">
                    </div>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" name="is_active" id="userActive" value="1" checked>
                        User is active
                    </label>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('userModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="userSubmitBtn">Save User</button>
            </div>
        </form>
    </div>
</div>

<script>
function openUserModal() {
    document.getElementById('userModalTitle').textContent = 'Add User';
    document.getElementById('userForm').reset();
    document.getElementById('userId').value = '';
    document.getElementById('userPassword').required = true;
    document.getElementById('passwordReq').style.display = 'inline';
    document.getElementById('passwordHint').textContent = 'Minimum 8 characters';
    document.getElementById('userActive').checked = true;
    document.getElementById('userModal').classList.add('active');
}

function openEditModal(user) {
    document.getElementById('userModalTitle').textContent = 'Edit User';
    document.getElementById('userId').value = user.id;
    document.getElementById('userFullName').value = user.full_name;
    document.getElementById('userEmail').value = user.email;
    document.getElementById('userRole').value = user.role_id;
    document.getElementById('userUnit').value = user.unit || '';
    document.getElementById('userActive').checked = user.is_active == 1;
    document.getElementById('userPassword').value = '';
    document.getElementById('userPasswordConfirm').value = '';
    document.getElementById('userPassword').required = false;
    document.getElementById('passwordReq').style.display = 'none';
    document.getElementById('passwordHint').textContent = 'Leave blank to keep current password';
    document.getElementById('userModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Password confirmation validation
document.getElementById('userForm').addEventListener('submit', function(e) {
    const password = document.getElementById('userPassword').value;
    const confirm = document.getElementById('userPasswordConfirm').value;
    
    if (password && password !== confirm) {
        e.preventDefault();
        alert('Passwords do not match!');
        return false;
    }
});

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            // Special handling for PIN modal to restore transitions
            if (overlay.id === 'pinModal') {
                closePinModal();
            } else {
                overlay.classList.remove('active');
            }
        }
    });
});
</script>

<!-- User Status Change Confirmation Modal -->
<div class="modal-overlay" id="statusModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="statusModalTitle">Confirm Action</h3>
            <button type="button" class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <div class="modal-body">
            <p id="statusModalMessage">Are you sure you want to proceed?</p>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
            <form method="POST" action="/SDO-cts/admin/api/user-status.php" id="statusForm" style="display:inline;">
                <input type="hidden" name="user_id" id="statusUserId" value="">
                <input type="hidden" name="action" id="statusAction" value="">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                <button type="submit" class="btn btn-primary" id="statusConfirmBtn">Confirm</button>
            </form>
        </div>
    </div>
</div>

<script>
function openStatusModal(userId, action, userName) {
    document.getElementById('statusUserId').value = userId;
    document.getElementById('statusAction').value = action;
    
    if (action === 'deactivate') {
        document.getElementById('statusModalTitle').textContent = 'Deactivate User';
        document.getElementById('statusModalMessage').textContent = 'Are you sure you want to deactivate ' + userName + '? They will no longer be able to log in.';
        document.getElementById('statusConfirmBtn').textContent = 'Deactivate';
        document.getElementById('statusConfirmBtn').className = 'btn btn-danger';
    } else {
        document.getElementById('statusModalTitle').textContent = 'Activate User';
        document.getElementById('statusModalMessage').textContent = 'Are you sure you want to activate ' + userName + '? They will be able to log in again.';
        document.getElementById('statusConfirmBtn').textContent = 'Activate';
        document.getElementById('statusConfirmBtn').className = 'btn btn-primary';
    }
    
    document.getElementById('statusModal').classList.add('active');
}
</script>

<!-- Delete User Confirmation Modal -->
<div class="modal-overlay" id="deleteModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="deleteModalTitle">Delete User</h3>
            <button type="button" class="modal-close" onclick="closeModal('deleteModal')">&times;</button>
        </div>
        <div class="modal-body">
            <div class="delete-warning">
                <i class="fas fa-exclamation-triangle delete-warning-icon"></i>
                <p id="deleteModalMessage">Are you sure you want to delete this user? This action cannot be undone.</p>
                <div id="deleteUserList" class="delete-user-list"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closeModal('deleteModal')">Cancel</button>
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">Yes, delete this user</button>
        </div>
    </div>
</div>

<!-- Security PIN Modal -->
<div class="modal-overlay" id="pinModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Security Verification</h3>
            <button type="button" class="modal-close" onclick="closePinModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="pin-verification-info">
                <i class="fas fa-shield-alt pin-icon"></i>
                <p>Enter your 6-digit security PIN to confirm this action.</p>
            </div>
            <div class="form-group">
                <label class="form-label">Security PIN</label>
                <div class="pin-input-container">
                    <input type="password" id="securityPin" class="form-control pin-input" 
                           maxlength="6" pattern="\d{6}" inputmode="numeric" 
                           placeholder="• • • • • •" autocomplete="off">
                </div>
                <small class="form-hint">Enter your 6-digit numerical PIN</small>
                <div id="attemptsInfo" class="attempts-info"></div>
            </div>
            <div id="pinError" class="alert alert-error" style="display: none;"></div>
            <div id="cooldownMessage" class="alert alert-warning" style="display: none;">
                <i class="fas fa-clock"></i> Too many failed attempts. Please wait <span id="cooldownTimer">60</span> seconds.
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-outline" onclick="closePinModal()">Cancel</button>
            <button type="button" class="btn btn-danger" id="pinConfirmBtn" onclick="executeDelete()">
                <i class="fas fa-trash"></i> Confirm Delete
            </button>
        </div>
    </div>
</div>

<script>
// Delete mode state
let isDeleteMode = false;

// Toggle delete mode (show/hide checkboxes)
function toggleDeleteMode() {
    isDeleteMode = !isDeleteMode;
    const checkboxColumns = document.querySelectorAll('.delete-mode-column');
    const toggleBtn = document.getElementById('toggleDeleteModeBtn');
    const cancelBtn = document.getElementById('cancelDeleteModeBtn');
    const deleteSelectedBtn = document.getElementById('deleteSelectedBtn');
    const selectAllCheckbox = document.getElementById('selectAllUsers');
    
    if (isDeleteMode) {
        // Show checkbox columns
        checkboxColumns.forEach(col => col.style.display = '');
        toggleBtn.style.display = 'none';
        cancelBtn.style.display = 'inline-flex';
        
        // Add visual indicator to table
        document.getElementById('usersTable').classList.add('delete-mode-active');
    } else {
        // Hide checkbox columns
        checkboxColumns.forEach(col => col.style.display = 'none');
        toggleBtn.style.display = 'inline-flex';
        cancelBtn.style.display = 'none';
        deleteSelectedBtn.style.display = 'none';
        
        // Uncheck all checkboxes
        document.querySelectorAll('.user-checkbox').forEach(cb => cb.checked = false);
        if (selectAllCheckbox) selectAllCheckbox.checked = false;
        
        // Remove visual indicator
        document.getElementById('usersTable').classList.remove('delete-mode-active');
    }
}

// Toggle select all checkboxes
function toggleSelectAll(checkbox) {
    const userCheckboxes = document.querySelectorAll('.user-checkbox');
    userCheckboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
    updateSelectedCount();
}

// Update the selected count display
function updateSelectedCount() {
    const checkboxes = document.querySelectorAll('.user-checkbox:checked');
    const count = checkboxes.length;
    const deleteBtn = document.getElementById('deleteSelectedBtn');
    const countSpan = document.getElementById('selectedCount');
    
    if (count > 0) {
        deleteBtn.style.display = 'inline-flex';
        countSpan.textContent = count;
    } else {
        deleteBtn.style.display = 'none';
    }
    
    // Update select all checkbox state
    const allCheckboxes = document.querySelectorAll('.user-checkbox');
    const selectAll = document.getElementById('selectAllUsers');
    if (selectAll) {
        if (allCheckboxes.length > 0 && count === allCheckboxes.length) {
            selectAll.checked = true;
            selectAll.indeterminate = false;
        } else if (count > 0) {
            selectAll.checked = false;
            selectAll.indeterminate = true;
        } else {
            selectAll.checked = false;
            selectAll.indeterminate = false;
        }
    }
}

// Store user IDs to delete
let usersToDelete = [];

// Open delete modal for single user or selected users
function openDeleteModal(userId = null, userName = null) {
    usersToDelete = [];
    const deleteUserList = document.getElementById('deleteUserList');
    deleteUserList.innerHTML = '';
    
    if (userId) {
        // Single user deletion
        usersToDelete = [userId];
        document.getElementById('deleteModalTitle').textContent = 'Delete User';
        document.getElementById('deleteModalMessage').textContent = 'Are you sure you want to delete this user? This action cannot be undone.';
        deleteUserList.innerHTML = '<div class="delete-user-item"><i class="fas fa-user"></i> ' + userName + '</div>';
    } else {
        // Multiple user deletion from checkboxes
        const checkboxes = document.querySelectorAll('.user-checkbox:checked');
        if (checkboxes.length === 0) {
            alert('Please select at least one user to delete.');
            return;
        }
        
        checkboxes.forEach(cb => {
            usersToDelete.push(parseInt(cb.value));
            deleteUserList.innerHTML += '<div class="delete-user-item"><i class="fas fa-user"></i> ' + cb.dataset.userName + '</div>';
        });
        
        const count = usersToDelete.length;
        document.getElementById('deleteModalTitle').textContent = count === 1 ? 'Delete User' : 'Delete ' + count + ' Users';
        document.getElementById('deleteModalMessage').textContent = count === 1 
            ? 'Are you sure you want to delete this user? This action cannot be undone.'
            : 'Are you sure you want to delete these ' + count + ' users? This action cannot be undone.';
    }
    
    document.getElementById('deleteModal').classList.add('active');
}

// PIN attempt tracking
let pinAttempts = 0;
const maxAttempts = 3;
let cooldownEndTime = null;
let cooldownInterval = null;

// Close PIN modal
function closePinModal() {
    const pinModal = document.getElementById('pinModal');
    
    // Re-enable transitions before closing (in case they were disabled)
    pinModal.style.transition = '';
    const modalInner = pinModal.querySelector('.modal');
    if (modalInner) {
        modalInner.style.transition = '';
    }
    
    // Close the modal
    pinModal.classList.remove('active');
    
    // Clear cooldown interval if running
    if (cooldownInterval) {
        clearInterval(cooldownInterval);
        cooldownInterval = null;
    }
    
    // Reset the PIN input
    document.getElementById('securityPin').value = '';
    document.getElementById('pinError').style.display = 'none';
}

// Update attempts display
function updateAttemptsDisplay() {
    const attemptsInfo = document.getElementById('attemptsInfo');
    const remaining = maxAttempts - pinAttempts;
    
    if (pinAttempts > 0 && remaining > 0) {
        attemptsInfo.innerHTML = '<i class="fas fa-info-circle"></i> ' + remaining + ' attempt' + (remaining !== 1 ? 's' : '') + ' remaining';
        attemptsInfo.style.display = 'block';
        attemptsInfo.className = 'attempts-info attempts-warning';
    } else if (remaining <= 0) {
        attemptsInfo.innerHTML = '<i class="fas fa-exclamation-circle"></i> No attempts remaining';
        attemptsInfo.style.display = 'block';
        attemptsInfo.className = 'attempts-info attempts-danger';
    } else {
        attemptsInfo.style.display = 'none';
    }
}

// Start cooldown timer
function startCooldown() {
    cooldownEndTime = Date.now() + 60000; // 1 minute cooldown
    document.getElementById('cooldownMessage').style.display = 'flex';
    document.getElementById('pinConfirmBtn').disabled = true;
    document.getElementById('securityPin').disabled = true;
    document.getElementById('pinError').style.display = 'none';
    
    updateCooldownDisplay();
    
    cooldownInterval = setInterval(() => {
        const remaining = Math.ceil((cooldownEndTime - Date.now()) / 1000);
        
        if (remaining <= 0) {
            // Cooldown finished - reset attempts
            clearInterval(cooldownInterval);
            cooldownInterval = null;
            cooldownEndTime = null;
            pinAttempts = 0;
            
            document.getElementById('cooldownMessage').style.display = 'none';
            document.getElementById('pinConfirmBtn').disabled = false;
            document.getElementById('securityPin').disabled = false;
            document.getElementById('securityPin').value = '';
            updateAttemptsDisplay();
        } else {
            document.getElementById('cooldownTimer').textContent = remaining;
        }
    }, 1000);
}

// Update cooldown display
function updateCooldownDisplay() {
    const remaining = Math.ceil((cooldownEndTime - Date.now()) / 1000);
    document.getElementById('cooldownTimer').textContent = Math.max(0, remaining);
}

// Confirm and execute delete immediately (no PIN verification)
async function confirmDelete() {
    const deleteModal = document.getElementById('deleteModal');
    const confirmBtn = deleteModal.querySelector('button.btn-danger[onclick="confirmDelete()"]');
    
    // Disable button and show loading
    if (confirmBtn) {
        confirmBtn.disabled = true;
        const originalText = confirmBtn.innerHTML;
        confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Deleting...';
        
        try {
            const response = await fetch('/SDO-cts/admin/api/delete-user.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    user_ids: usersToDelete,
                    csrf_token: '<?php echo $auth->generateCsrfToken(); ?>'
                })
            });
            
            const result = await response.json();
            
            if (result.success) {
                // Close modal immediately
                deleteModal.classList.remove('active');
                
                // Show success message
                showToast(result.message, 'success');
                
                // Reload page immediately
                window.location.reload();
            } else {
                // Show error message
                alert(result.message || 'Failed to delete user(s). Please try again.');
                
                // Re-enable button
                confirmBtn.disabled = false;
                confirmBtn.innerHTML = originalText;
            }
        } catch (error) {
            console.error('Delete error:', error);
            alert('An error occurred while deleting. Please try again.');
            
            // Re-enable button
            confirmBtn.disabled = false;
            confirmBtn.innerHTML = originalText;
        }
    }
}

// Execute the delete operation
async function executeDelete() {
    const pin = document.getElementById('securityPin').value;
    const pinError = document.getElementById('pinError');
    const confirmBtn = document.getElementById('pinConfirmBtn');
    
    // Check if in cooldown
    if (cooldownEndTime && Date.now() < cooldownEndTime) {
        pinError.textContent = 'Please wait for the cooldown to finish.';
        pinError.style.display = 'block';
        return;
    }
    
    // Validate PIN format
    if (!/^\d{6}$/.test(pin)) {
        pinError.textContent = 'Please enter a valid 6-digit PIN.';
        pinError.style.display = 'block';
        return;
    }
    
    // Disable button and show loading (1.5-2 sec max)
    confirmBtn.disabled = true;
    confirmBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Verifying...';
    pinError.style.display = 'none';
    
    // Minimum visual feedback time (1.5 seconds)
    const startTime = Date.now();
    
    try {
        const response = await fetch('/SDO-cts/admin/api/delete-user.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                user_ids: usersToDelete,
                security_pin: pin,
                csrf_token: '<?php echo $auth->generateCsrfToken(); ?>'
            })
        });
        
        const result = await response.json();
        
        // Ensure minimum 1.5 second loading time for smooth UX
        const elapsed = Date.now() - startTime;
        const remainingTime = Math.max(0, 1500 - elapsed);
        
        await new Promise(resolve => setTimeout(resolve, remainingTime));
        
        if (result.success) {
            // Reset attempts on success
            pinAttempts = 0;
            
            closePinModal();
            
            // Show success message and reload
            showToast(result.message, 'success');
            
            // Remove deleted rows from table or reload page
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        } else {
            // Increment attempts on failure
            pinAttempts++;
            updateAttemptsDisplay();
            
            if (pinAttempts >= maxAttempts) {
                // Start cooldown
                startCooldown();
            } else {
                pinError.textContent = result.message;
                pinError.style.display = 'block';
            }
            
            // If redirect is suggested (PIN not set)
            if (result.redirect) {
                setTimeout(() => {
                    window.location.href = result.redirect;
                }, 2000);
            }
        }
    } catch (error) {
        pinError.textContent = 'An error occurred. Please try again.';
        pinError.style.display = 'block';
    } finally {
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fas fa-trash"></i> Confirm Delete';
    }
}

// Toast notification function
function showToast(message, type = 'success') {
    // Check if toast container exists, if not create it
    let toastContainer = document.getElementById('toastContainer');
    if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'toastContainer';
        toastContainer.className = 'toast-container';
        document.body.appendChild(toastContainer);
    }
    
    const toast = document.createElement('div');
    toast.className = 'toast toast-' + type;
    toast.innerHTML = '<i class="fas fa-' + (type === 'success' ? 'check-circle' : 'exclamation-circle') + '"></i> ' + message;
    
    toastContainer.appendChild(toast);
    
    // Trigger animation
    setTimeout(() => toast.classList.add('show'), 10);
    
    // Remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 300);
    }, 3000);
}

// Handle Enter key in PIN input
document.addEventListener('DOMContentLoaded', function() {
    const pinInput = document.getElementById('securityPin');
    if (pinInput) {
        pinInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                executeDelete();
            }
        });
        
        // Only allow numeric input
        pinInput.addEventListener('input', function(e) {
            this.value = this.value.replace(/[^0-9]/g, '');
        });
    }
});
</script>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>

