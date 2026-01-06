<?php
/**
 * View Complaint Details Page
 * SDO CTS - San Pedro Division Office Complaint Tracking System
 */

require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../models/ComplaintAdmin.php';

$auth = auth();
$auth->requirePermission('complaints.view');

$complaintModel = new ComplaintAdmin();

// Get complaint ID
$id = intval($_GET['id'] ?? 0);
if (!$id) {
    header('Location: /SDO-cts/admin/complaints.php');
    exit;
}

// Get complaint details
$complaint = $complaintModel->getById($id);
if (!$complaint) {
    header('Location: /SDO-cts/admin/complaints.php?error=not_found');
    exit;
}

// Log view action
$auth->logActivity('view', 'complaint', $id, 'Viewed complaint ' . $complaint['reference_number']);

// Get related data
$documents = $complaintModel->getDocuments($id);
$history = $complaintModel->getStatusHistory($id);
$assignments = $complaintModel->getAssignments($id);

// Status config
$statusConfig = STATUS_CONFIG;
$units = UNITS;
$statusWorkflow = STATUS_WORKFLOW;

// Prepare checkmarks for referred to section
$checkOSDS = $complaint['referred_to'] === 'OSDS' ? '✓' : '';
$checkSGOD = $complaint['referred_to'] === 'SGOD' ? '✓' : '';
$checkCID = $complaint['referred_to'] === 'CID' ? '✓' : '';
$checkOthers = $complaint['referred_to'] === 'Others' ? '✓' : '';
$othersText = ($complaint['referred_to'] === 'Others' && !empty($complaint['referred_to_other'])) ? $complaint['referred_to_other'] : '';

include __DIR__ . '/includes/header.php';
?>

<style>
    /* Form Container - Fixed size matching the official form */
    .form-container {
        position: relative;
        width: 850px;
        max-width: 100%;
        margin: 0 auto;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    
    /* Background Image Layer */
    .form-background {
        width: 100%;
        display: block;
    }
    
    /* Text Overlay Layer */
    .form-overlay {
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        pointer-events: none;
    }
    
    /* Base Field Container */
    .field-box {
        position: absolute;
        font-family: Arial, sans-serif;
        color: #000;
        overflow: hidden;
        white-space: pre-wrap;
        word-break: break-word;
        line-height: 1.3;
    }

    /* CTS Ticket Number Field */
    .cts-ticket-box {
        top: 3.0%;
        left: 10%;
        width: 35%;
        height: 2%;
        font-size: 12px;
        font-weight: bold;
        color: #c00;
    }

    /* Checkmark Fields - precisely positioned in checkbox squares */
    .check-osds    { top: 5.65%; left: 52.8%; font-size: 14px; font-weight: bold; letter-spacing: 0.03em; }
    .check-sgod    { top: 5.65%; left: 62.05%; font-size: 14px; font-weight: bold; letter-spacing: 0.03em; }
    .check-cid     { top: 7.85%; left: 52.6%; font-size: 14px; font-weight: bold; letter-spacing: 0.03em; }
    .check-others  { top: 7.65%; left: 62.10%; font-size: 14px; font-weight: bold; letter-spacing: 0.03em; }

    /* Others Text Field */
    .others-text-box {
        top: 7.65%;
        left: 80%;
        width: 29%;
        height: 2%;
        font-size: 11px;
    }
    
    /* Date Field - on the Date/Petsa line */
    .date-box {
        top: 12.7%;
        left: 67%;
        width: 38%;
        height: 1.8%;
        font-size: 12px;
    }
    
    /* Complainant Name */
    .complainant-name-box {
        top: 31.5%;
        left: 24%;
        width: 66%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Complainant Address */
    .complainant-address-box {
        top: 33.2%;
        left: 24%;
        width: 66%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Complainant Contact */
    .complainant-contact-box {
        top: 34.9%;
        left: 24%;
        width: 66%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Complainant Email */
    .complainant-email-box {
        top: 36.6%;
        left: 24%;
        width: 66%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Involved Person Name */
    .involved-name-box {
        top: 42.1%;
        left: 24%;
        width: 73%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Involved Position */
    .involved-position-box {
        top: 44.0%;
        left: 24%;
        width: 73%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Involved Address */
    .involved-address-box {
        top: 45.7%;
        left: 24%;
        width: 73%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Involved School/Office */
    .involved-school-box {
        top: 47.4%;
        left: 24%;
        width: 69%;
        height: 1.2%;
        font-size: 10px;
    }
    
    /* Narration Box - Multi-line with controlled height */
    .narration-box {
        top: 55.5%;
        left: 10%;
        width: 80%;
        height: 15%;
        font-size: 9.5px;
        line-height: 2.05;
        overflow: hidden;
        white-space: pre-wrap;
        word-break: break-word;
    }
    
    /* Signature Box */
    .signature-box {
        top: 93%;
        left: 28%;
        width: 44%;
        height: 2%;
        font-family: 'Times New Roman', Times, serif;
        font-size: 16px;
        text-align: center;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        justify-content: center;
    }
    
    /* Admin View Header */
    .admin-view-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
        flex-wrap: wrap;
        gap: 15px;
    }
    
    .admin-view-header .back-link {
        color: #666;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .admin-view-header .back-link:hover {
        color: #333;
    }
    
    .header-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }
    
    .reference-badge {
        background: linear-gradient(135deg, #1a5a96, #0d3d6e);
        color: white;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 600;
        font-size: 14px;
    }
    
    .attached-notice {
        background: #f5f5f5;
        padding: 12px 15px;
        margin-top: 15px;
        border-radius: 6px;
        border: 1px solid #ddd;
    }
    
    .attached-notice ul {
        margin: 8px 0 0 20px;
        padding: 0;
    }
    
    .attached-notice a {
        color: #1a5a96;
        text-decoration: none;
    }
    
    .attached-notice a:hover {
        text-decoration: underline;
    }
    
    /* Additional Page Styles for Admin - Official Form Look */
    .additional-page {
        position: relative;
        width: 850px;
        max-width: 100%;
        margin: 30px auto 0;
        background: #fff;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        padding: 30px 40px;
        min-height: 1100px;
    }
    
    .additional-page-header {
        text-align: center;
        margin-bottom: 0;
        border: 1px solid #000;
        border-left: 3px solid #000;
        border-right: 3px solid #000;
        border-bottom: none;
        padding: 10px;
        background: #fff;
    }
    
    .additional-page-header h2 {
        color: #000;
        font-family: 'Times New Roman', Times, serif;
        font-size: 14px;
        font-weight: bold;
        margin: 0 0 5px;
        text-transform: uppercase;
        letter-spacing: 0;
    }
    
    .additional-page-header p {
        color: #000;
        font-family: 'Times New Roman', Times, serif;
        font-size: 12px;
        font-style: italic;
        margin: 0;
    }
    
    .additional-page-content {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11px;
        line-height: 28px;
        white-space: pre-wrap;
        word-break: break-word;
        min-height: 900px;
        padding: 5px 10px;
        background: repeating-linear-gradient(
            transparent,
            transparent 27px,
            #000 27px,
            #000 28px
        );
        border: 1px solid #000;
        border-left: 3px solid #000;
        border-right: 3px solid #000;
        border-bottom: 3px solid #000;
    }
    
    .page-indicator {
        text-align: center;
        color: #999;
        font-size: 0.85rem;
        margin-top: 15px;
    }
    
    .page-number-label {
        font-family: 'Times New Roman', Times, serif;
        font-size: 11px;
        text-align: right;
        margin-bottom: 10px;
        color: #000;
    }
    
    @page {
        size: auto;
        margin: 0;
    }
    
    @media print {
        /* Reset body */
        body {
            margin: 0 !important;
            padding: 0 !important;
            background: #fff !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* Hide admin UI elements */
        .sidebar,
        .top-bar,
        .admin-footer,
        .admin-view-header,
        .complaint-sidebar,
        .attached-notice,
        .no-print,
        .modal-overlay {
            display: none !important;
        }
        
        /* Reset layout containers */
        .admin-layout {
            display: block !important;
        }
        
        .main-content {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        .content-wrapper {
            margin: 0 !important;
            padding: 0 !important;
            width: 100% !important;
        }
        
        .complaint-detail-grid {
            display: block !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        .complaint-main {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        
        /* Form container styling for print */
        .form-container {
            width: 100% !important;
            max-width: 100% !important;
            margin: 0 !important;
            box-shadow: none !important;
            page-break-after: always;
        }
        
        .form-background {
            width: 100% !important;
        }
        
        /* Additional page print styling */
        .additional-page {
            box-shadow: none !important;
            margin: 0 !important;
            page-break-before: always;
        }
        
        .page-indicator {
            display: none !important;
        }
    }
    
    @media (max-width: 850px) {
        .form-container {
            width: 100%;
        }
        .field-box {
            font-size: 9px;
        }
        .narration-box {
            font-size: 8px;
        }
        .signature-box {
            font-size: 14px;
        }
    }
</style>

<div class="admin-view-header no-print">
    <a href="/SDO-cts/admin/complaints.php" class="back-link">← Back to Complaints</a>
    <div class="header-actions">
        <span class="reference-badge"><?php echo htmlspecialchars($complaint['reference_number']); ?></span>
        <span class="status-badge status-<?php echo $complaint['status']; ?> large">
            <?php echo $statusConfig[$complaint['status']]['icon'] . ' ' . $statusConfig[$complaint['status']]['label']; ?>
        </span>
        <button type="button" class="btn btn-outline" onclick="window.print()">🖨️ Print</button>
        <button type="button" class="btn btn-primary" onclick="saveAsPDF()">📄 Save as PDF</button>
    </div>
</div>

<div class="complaint-detail-grid">
    <!-- Main Content -->
    <div class="complaint-main">
        <!-- FORM WITH IMAGE BACKGROUND AND TEXT OVERLAY -->
        <div class="form-container">
            <!-- Background Image (Official Form) -->
            <img src="/SDO-cts/reference/COMPLAINT-ASSISTED-FORM_1.jpg" 
                 alt="Complaint Assisted Form" 
                 class="form-background">
            
            <!-- Text Overlay Layer with Positioned Field Boxes -->
            <div class="form-overlay">
                
                <!-- CTS Ticket Number -->
                <div class="field-box cts-ticket-box">CTS No: <?php echo htmlspecialchars($complaint['reference_number']); ?></div>
                
                <!-- Routing Checkmarks -->
                <div class="field-box check-osds"><?php echo $checkOSDS; ?></div>
                <div class="field-box check-sgod"><?php echo $checkSGOD; ?></div>
                <div class="field-box check-cid"><?php echo $checkCID; ?></div>
                <div class="field-box check-others"><?php echo $checkOthers; ?></div>
                
                <!-- Others Text -->
                <div class="field-box others-text-box"><?php echo htmlspecialchars($othersText); ?></div>
                
                <!-- Date -->
                <div class="field-box date-box"><?php echo date('F j, Y', strtotime($complaint['date_petsa'])); ?></div>
                
                <!-- Complainant Information -->
                <div class="field-box complainant-name-box"><?php echo htmlspecialchars($complaint['name_pangalan']); ?></div>
                <div class="field-box complainant-address-box"><?php echo htmlspecialchars($complaint['address_tirahan']); ?></div>
                <div class="field-box complainant-contact-box"><?php echo htmlspecialchars($complaint['contact_number']); ?></div>
                <div class="field-box complainant-email-box"><?php echo htmlspecialchars($complaint['email_address']); ?></div>
                
                <!-- Involved Person/Office -->
                <div class="field-box involved-name-box"><?php echo htmlspecialchars($complaint['involved_full_name']); ?></div>
                <div class="field-box involved-position-box"><?php echo htmlspecialchars($complaint['involved_position']); ?></div>
                <div class="field-box involved-address-box"><?php echo htmlspecialchars($complaint['involved_address']); ?></div>
                <div class="field-box involved-school-box"><?php echo htmlspecialchars($complaint['involved_school_office_unit']); ?></div>
                
                <!-- Narration (Multi-line, Controlled) -->
                <div class="field-box narration-box"><?php echo htmlspecialchars($complaint['narration_complaint']); ?></div>
                
                <!-- Signature -->
                <div class="field-box signature-box"><?php echo htmlspecialchars($complaint['signature_data'] ?? $complaint['printed_name_pangalan']); ?></div>
                
            </div>
        </div>
        <div class="page-indicator no-print">Page 1 of <?php echo !empty($complaint['narration_complaint_page2']) ? '2' : '1'; ?></div>

        <!-- PAGE 2: ADDITIONAL PAGE FOR NARRATION CONTINUATION (Only if content exists) -->
        <?php if (!empty($complaint['narration_complaint_page2'])): ?>
        <div class="additional-page">
            <div class="page-number-label">CTS No: <?php echo htmlspecialchars($complaint['reference_number']); ?> | Page 2</div>
            
            <div class="additional-page-header">
                <h2>NARRATION OF COMPLAINT/INQUIRY AND RELIEF</h2>
                <p>(Ano ang iyong reklamo, tanong, request o suhestiyon? Ano ang gusto mong aksiyon?)</p>
            </div>
            
            <div class="additional-page-content"><?php echo htmlspecialchars($complaint['narration_complaint_page2']); ?></div>
        </div>
        <div class="page-indicator no-print">Page 2 of 2</div>
        <?php endif; ?>

        <!-- Attached Files (Below Form) -->
        <?php if (!empty($documents)): ?>
        <div class="attached-notice no-print">
            <strong>📎 Attached Supporting Documents:</strong>
            <ul>
                <?php foreach ($documents as $doc): ?>
                <li>
                    <a href="/SDO-cts/uploads/complaints/<?php echo $complaint['id'] . '/' . $doc['file_name']; ?>" target="_blank">
                        <?php echo htmlspecialchars($doc['original_name']); ?>
                    </a>
                    <span style="color:#666;font-size:12px;">(<?php echo number_format($doc['file_size'] / 1024, 1); ?> KB)</span>
                </li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </div>

    <!-- Sidebar -->
    <div class="complaint-sidebar">
        <!-- Actions -->
        <?php if ($auth->hasPermission('complaints.update')): ?>
        <div class="detail-card action-card">
            <div class="detail-card-header">
                <h3><i class=""></i> Actions</h3>
            </div>
            <div class="detail-card-body">
                <?php $allowedTransitions = $statusWorkflow[$complaint['status']] ?? []; ?>
                
                <?php if ($complaint['status'] === 'pending' && $auth->hasPermission('complaints.accept')): ?>
                <button type="button" class="btn btn-success btn-block" onclick="openActionModal('accept')">
                    <i class=""></i> Accept Complaint
                </button>
                <button type="button" class="btn btn-outline btn-block btn-danger-outline" onclick="openActionModal('return')">
                    <i class=""></i> Return Complaint
                </button>
                <?php endif; ?>
                
                <?php if (!empty($allowedTransitions) && $complaint['status'] !== 'pending'): ?>
                <button type="button" class="btn btn-primary btn-block" onclick="openStatusModal()">
                    <i class=""></i> Update Status
                </button>
                <?php endif; ?>
                
                <?php if ($auth->hasPermission('complaints.forward') && in_array($complaint['status'], ['accepted', 'in_progress'])): ?>
                <button type="button" class="btn btn-outline btn-block" onclick="openForwardModal()">
                    <i class="></i> Forward to Unit
                </button>
                <?php endif; ?>
                
                <?php if (empty($allowedTransitions) && $complaint['status'] !== 'pending'): ?>
                <p class="action-note">No further actions available for this status.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Progress Tracker -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h3><i class="fas fa-tasks"></i> Progress Tracker</h3>
            </div>
            <div class="detail-card-body">
                <?php
                $statusOrder = ['pending', 'accepted', 'in_progress', 'resolved', 'closed'];
                $currentIndex = array_search($complaint['status'], $statusOrder);
                if ($complaint['status'] === 'returned') {
                    $currentIndex = -1;
                }
                ?>
                <div class="progress-tracker-vertical">
                    <?php foreach ($statusOrder as $index => $step): ?>
                    <?php
                    $stepClass = '';
                    if ($complaint['status'] === 'returned') {
                        $stepClass = 'returned';
                    } elseif ($index < $currentIndex) {
                        $stepClass = 'completed';
                    } elseif ($index === $currentIndex) {
                        $stepClass = 'current';
                    }
                    ?>
                    <div class="progress-step <?php echo $stepClass; ?>">
                        <div class="step-marker">
                            <?php if ($index < $currentIndex): ?>
                                <i class="fas fa-check"></i>
                            <?php else: ?>
                                <?php echo $statusConfig[$step]['icon']; ?>
                            <?php endif; ?>
                        </div>
                        <div class="step-content">
                            <span class="step-label"><?php echo $statusConfig[$step]['label']; ?></span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                    
                    <?php if ($complaint['status'] === 'returned'): ?>
                    <div class="progress-step returned current">
                        <div class="step-marker"><i class="fas fa-undo"></i></div>
                        <div class="step-content">
                            <span class="step-label">Returned</span>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Status History -->
        <div class="detail-card">
            <div class="detail-card-header">
                <h3><i class="fas fa-history"></i> Activity Log</h3>
            </div>
            <div class="detail-card-body">
                <div class="timeline">
                    <?php foreach ($history as $entry): ?>
                    <div class="timeline-item">
                        <div class="timeline-marker"></div>
                        <div class="timeline-content">
                            <div class="timeline-status">
                                <?php echo $statusConfig[$entry['status']]['icon'] . ' ' . $statusConfig[$entry['status']]['label']; ?>
                            </div>
                            <?php if ($entry['notes']): ?>
                            <div class="timeline-notes"><?php echo htmlspecialchars($entry['notes']); ?></div>
                            <?php endif; ?>
                            <div class="timeline-meta">
                                <span><?php echo htmlspecialchars($entry['admin_name'] ?? $entry['updated_by']); ?></span>
                                <span><?php echo date('M j, Y g:i A', strtotime($entry['created_at'])); ?></span>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <!-- Assignment History -->
        <?php if (!empty($assignments)): ?>
        <div class="detail-card">
            <div class="detail-card-header">
                <h3><i class="fas fa-exchange-alt"></i> Assignment History</h3>
            </div>
            <div class="detail-card-body">
                <div class="assignments-list">
                    <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-item">
                        <div class="assignment-unit"><?php echo htmlspecialchars($assignment['assigned_to_unit']); ?></div>
                        <div class="assignment-notes"><?php echo htmlspecialchars($assignment['notes']); ?></div>
                        <div class="assignment-meta">
                            By <?php echo htmlspecialchars($assignment['assigned_by_name']); ?> •
                            <?php echo date('M j, Y', strtotime($assignment['created_at'])); ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- Action Modals -->

<!-- Accept/Return Modal -->
<div class="modal-overlay" id="actionModal">
    <div class="modal">
        <div class="modal-header">
            <h3 id="actionModalTitle">Action</h3>
            <button type="button" class="modal-close" onclick="closeModal('actionModal')">&times;</button>
        </div>
        <form method="POST" action="/SDO-cts/admin/api/complaint-action.php" id="actionForm">
            <div class="modal-body">
                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                <input type="hidden" name="action" id="actionType">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label" id="actionNotesLabel">Notes</label>
                    <textarea name="notes" class="form-control" rows="4" id="actionNotes" placeholder="Add notes..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('actionModal')">Cancel</button>
                <button type="submit" class="btn btn-primary" id="actionSubmitBtn">Submit</button>
            </div>
        </form>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal-overlay" id="statusModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Update Status</h3>
            <button type="button" class="modal-close" onclick="closeModal('statusModal')">&times;</button>
        </div>
        <form method="POST" action="/SDO-cts/admin/api/update-status.php">
            <div class="modal-body">
                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label">New Status</label>
                    <select name="status" class="form-control" required>
                        <?php foreach ($allowedTransitions as $status): ?>
                        <option value="<?php echo $status; ?>">
                            <?php echo $statusConfig[$status]['icon'] . ' ' . $statusConfig[$status]['label']; ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Add notes about this status change..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('statusModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Update Status</button>
            </div>
        </form>
    </div>
</div>

<!-- Forward Modal -->
<div class="modal-overlay" id="forwardModal">
    <div class="modal">
        <div class="modal-header">
            <h3>Forward to Unit</h3>
            <button type="button" class="modal-close" onclick="closeModal('forwardModal')">&times;</button>
        </div>
        <form method="POST" action="/SDO-cts/admin/api/forward-complaint.php">
            <div class="modal-body">
                <input type="hidden" name="complaint_id" value="<?php echo $complaint['id']; ?>">
                <input type="hidden" name="csrf_token" value="<?php echo $auth->generateCsrfToken(); ?>">
                
                <div class="form-group">
                    <label class="form-label">Select Unit</label>
                    <select name="unit" class="form-control" required>
                        <?php foreach ($units as $key => $name): ?>
                        <option value="<?php echo $key; ?>"><?php echo $key; ?> - <?php echo $name; ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="form-group">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="3" placeholder="Reason for forwarding..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline" onclick="closeModal('forwardModal')">Cancel</button>
                <button type="submit" class="btn btn-primary">Forward</button>
            </div>
        </form>
    </div>
</div>

<script>
function openActionModal(action) {
    const modal = document.getElementById('actionModal');
    const title = document.getElementById('actionModalTitle');
    const notesLabel = document.getElementById('actionNotesLabel');
    const notesInput = document.getElementById('actionNotes');
    const actionInput = document.getElementById('actionType');
    const submitBtn = document.getElementById('actionSubmitBtn');
    
    actionInput.value = action;
    
    if (action === 'accept') {
        title.innerHTML = '<i class="fas fa-check"></i> Accept Complaint';
        notesLabel.textContent = 'Notes (Optional)';
        notesInput.placeholder = 'Add any notes about accepting this complaint...';
        notesInput.required = false;
        submitBtn.textContent = 'Accept Complaint';
        submitBtn.className = 'btn btn-success';
    } else if (action === 'return') {
        title.innerHTML = '<i class="fas fa-undo"></i> Return Complaint';
        notesLabel.textContent = 'Reason for Return *';
        notesInput.placeholder = 'Please provide the reason for returning this complaint...';
        notesInput.required = true;
        submitBtn.textContent = 'Return Complaint';
        submitBtn.className = 'btn btn-danger';
    }
    
    modal.classList.add('active');
}

function openStatusModal() {
    document.getElementById('statusModal').classList.add('active');
}

function openForwardModal() {
    document.getElementById('forwardModal').classList.add('active');
}

function closeModal(modalId) {
    document.getElementById(modalId).classList.remove('active');
}

// Close modal on overlay click
document.querySelectorAll('.modal-overlay').forEach(overlay => {
    overlay.addEventListener('click', (e) => {
        if (e.target === overlay) {
            overlay.classList.remove('active');
        }
    });
});

// Save as PDF function
function saveAsPDF() {
    // Show instruction alert
    const refNumber = '<?php echo htmlspecialchars($complaint['reference_number']); ?>';
    
    alert('To save as PDF:\n\n1. In the print dialog, select "Save as PDF" or "Microsoft Print to PDF" as the destination\n2. Uncheck "Headers and footers" in More settings\n3. Set margins to "None" for best results\n4. Click Save and name your file: ' + refNumber + '.pdf');
    
    // Trigger print dialog (user can choose Save as PDF)
    window.print();
}
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

