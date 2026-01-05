<?php
/**
 * SDO CTS - Review Submission Page
 * Displays the form data for review before final submission
 */

session_start();

// Check if form data exists
if (!isset($_SESSION['form_data']) || empty($_SESSION['form_data'])) {
    header('Location: index.php');
    exit;
}

$data = $_SESSION['form_data'];
$files = $_SESSION['form_files'] ?? [];

// Handle final submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['confirm_submit'])) {
    require_once __DIR__ . '/models/Complaint.php';
    
    try {
        $complaint = new Complaint();
        
        // Prepare data for database
        $complaintData = [
            'referred_to' => $data['referred_to'],
            'referred_to_other' => $data['referred_to_other'] ?? null,
            'complainant_name' => $data['complainant_name'],
            'complainant_address' => $data['complainant_address'],
            'complainant_contact' => $data['complainant_contact'],
            'complainant_email' => $data['complainant_email'],
            'involved_name' => $data['involved_name'],
            'involved_position' => $data['involved_position'],
            'involved_address' => $data['involved_address'],
            'involved_school_office' => $data['involved_school_office'],
            'complaint_narration' => $data['complaint_narration'],
            'desired_action' => $data['desired_action'],
            'certification_agreed' => isset($data['certification_agreed']),
            'printed_name' => $data['printed_name'],
            'signature_type' => $data['signature_type'],
            'signature_data' => $data['signature_type'] === 'digital' 
                ? $data['signature_data'] 
                : $data['typed_signature']
        ];
        
        $result = $complaint->create($complaintData);
        $complaintId = $result['id'];
        $referenceNumber = $result['reference_number'];
        
        // Move uploaded files to permanent location
        if (!empty($files)) {
            $uploadDir = __DIR__ . '/uploads/complaints/' . $complaintId . '/';
            $tempDir = __DIR__ . '/uploads/temp/';
            
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            
            foreach ($files as $file) {
                $tempPath = $tempDir . $file['temp_name'];
                $newPath = $uploadDir . $file['temp_name'];
                
                if (file_exists($tempPath)) {
                    rename($tempPath, $newPath);
                    $complaint->addDocument(
                        $complaintId,
                        $file['temp_name'],
                        $file['original_name'],
                        $file['type'],
                        $file['size']
                    );
                }
            }
        }
        
        // Clear session data
        unset($_SESSION['form_data']);
        unset($_SESSION['form_files']);
        
        // Store success info for display
        $_SESSION['submission_success'] = [
            'reference_number' => $referenceNumber,
            'email' => $complaintData['complainant_email']
        ];
        
        header('Location: success.php');
        exit;
        
    } catch (Exception $e) {
        $error = "An error occurred while submitting your complaint. Please try again.";
    }
}

// Format referred to display
$referredToDisplay = $data['referred_to'];
if ($data['referred_to'] === 'Others' && !empty($data['referred_to_other'])) {
    $referredToDisplay = 'Others: ' . htmlspecialchars($data['referred_to_other']);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Review Submission - SDO CTS</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Playfair+Display:wght@600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <!-- Navigation -->
        <nav class="nav-bar no-print">
            <div class="nav-links">
                <a href="index.php">📝 File Complaint</a>
                <a href="track.php">🔍 Track Complaint</a>
            </div>
        </nav>

        <!-- Header -->
        <header class="form-header review-header">
            <h1>📋 Review Your Submission</h1>
            <p class="subtitle">Please verify all information before final submission</p>
        </header>

        <?php if (isset($error)): ?>
        <div class="review-notice" style="background: #f8d7da; border-left-color: #dc3545; color: #721c24;">
            ⚠️ <?php echo $error; ?>
        </div>
        <?php endif; ?>

        <div class="review-notice">
            ⚠️ <strong>Important:</strong> Please carefully review all the information below. 
            Once submitted, your complaint will be locked and cannot be edited.
        </div>

        <!-- Official Form Preview -->
        <section class="form-section">
            <div class="section-header">
                <span class="section-icon">📄</span>
                Complaint Assisted Form Preview
            </div>
            <div class="section-content">
                <div class="official-form">
                    <div class="official-form-header">
                        <p style="font-size: 0.9rem;">Republic of the Philippines</p>
                        <h2>Department of Education</h2>
                        <h3>Schools Division of San Pedro, Laguna</h3>
                        <p style="margin-top: 0.5rem; font-weight: bold;">COMPLAINT ASSISTED FORM</p>
                    </div>

                    <!-- Routing Section -->
                    <div class="official-form-section">
                        <h4>I. Routing and Reference</h4>
                        <div class="official-form-row">
                            <span class="label">Referred To:</span>
                            <span class="value"><?php echo htmlspecialchars($referredToDisplay); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Date of Submission:</span>
                            <span class="value"><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>

                    <!-- Complainant Section -->
                    <div class="official-form-section">
                        <h4>II. Complainant / Requestor Information</h4>
                        <div class="official-form-row">
                            <span class="label">Full Name:</span>
                            <span class="value"><?php echo htmlspecialchars($data['complainant_name']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Complete Address:</span>
                            <span class="value"><?php echo htmlspecialchars($data['complainant_address']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Contact Number:</span>
                            <span class="value"><?php echo htmlspecialchars($data['complainant_contact']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Email Address:</span>
                            <span class="value"><?php echo htmlspecialchars($data['complainant_email']); ?></span>
                        </div>
                    </div>

                    <!-- Person Involved Section -->
                    <div class="official-form-section">
                        <h4>III. Office, School, or Person Involved</h4>
                        <div class="official-form-row">
                            <span class="label">Full Name:</span>
                            <span class="value"><?php echo htmlspecialchars($data['involved_name']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Position:</span>
                            <span class="value"><?php echo htmlspecialchars($data['involved_position']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Address:</span>
                            <span class="value"><?php echo htmlspecialchars($data['involved_address']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">School/Office/Unit:</span>
                            <span class="value"><?php echo htmlspecialchars($data['involved_school_office']); ?></span>
                        </div>
                    </div>

                    <!-- Complaint Details Section -->
                    <div class="official-form-section">
                        <h4>IV. Complaint / Inquiry Details</h4>
                        <div class="official-form-row" style="flex-direction: column;">
                            <span class="label" style="width: 100%; margin-bottom: 0.5rem;">Narration of Complaint, Inquiry, Request, or Suggestion:</span>
                            <span class="value" style="white-space: pre-wrap; line-height: 1.8; padding: 1rem; background: #f9f9f9; border: 1px solid #ddd;"><?php echo htmlspecialchars($data['complaint_narration']); ?></span>
                        </div>
                        <div class="official-form-row" style="flex-direction: column; margin-top: 1rem;">
                            <span class="label" style="width: 100%; margin-bottom: 0.5rem;">Desired Action or Relief Requested:</span>
                            <span class="value" style="white-space: pre-wrap; line-height: 1.8; padding: 1rem; background: #f9f9f9; border: 1px solid #ddd;"><?php echo htmlspecialchars($data['desired_action']); ?></span>
                        </div>
                    </div>

                    <!-- Supporting Documents Section -->
                    <?php if (!empty($files)): ?>
                    <div class="official-form-section">
                        <h4>V. Supporting Documents</h4>
                        <?php foreach ($files as $file): ?>
                        <div class="official-form-row">
                            <span class="label">📎 Document:</span>
                            <span class="value"><?php echo htmlspecialchars($file['original_name']); ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Certification Section -->
                    <div class="official-form-section">
                        <h4>VI. Certification on Non-Forum Shopping</h4>
                        <div style="padding: 1rem; background: #f9f9f9; border: 1px solid #ddd; font-size: 0.9rem; line-height: 1.8;">
                            <p>I have not commenced any other case/action or proceedings involving the same issues before this office, other DepEd administrative and disciplinary authority/machinery, or other government agencies, and that to the best of my knowledge no such action or other DepEd administrative and disciplinary authority/machinery, or other government agencies.</p>
                            <p style="margin-top: 0.5rem;">If I should thereafter have knowledge that a similar action or proceedings has been filed or is pending before this office, other DepEd administrative and disciplinary authority/machinery, or other government agencies, I undertake to promptly inform the said offices/agencies of that fact within five (5) days therefrom.</p>
                            <p style="margin-top: 1rem; font-weight: bold;">
                                ✅ Certification Agreed: <?php echo isset($data['certification_agreed']) ? 'Yes' : 'No'; ?>
                            </p>
                        </div>
                    </div>

                    <!-- Signature Section -->
                    <div class="official-form-section">
                        <h4>VII. Name and Signature</h4>
                        <div class="official-form-row">
                            <span class="label">Printed Name:</span>
                            <span class="value"><?php echo htmlspecialchars($data['printed_name']); ?></span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Signature:</span>
                            <span class="value">
                                <?php if ($data['signature_type'] === 'digital' && !empty($data['signature_data'])): ?>
                                <img src="<?php echo $data['signature_data']; ?>" alt="Digital Signature" 
                                     style="max-height: 60px; border-bottom: 1px solid #000;">
                                <?php else: ?>
                                <span style="font-family: 'Brush Script MT', cursive; font-size: 1.5rem;">
                                    <?php echo htmlspecialchars($data['typed_signature'] ?? ''); ?>
                                </span>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="official-form-row">
                            <span class="label">Date Signed:</span>
                            <span class="value"><?php echo date('F j, Y'); ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Action Buttons -->
        <form method="POST" class="form-actions no-print">
            <a href="index.php" class="btn btn-secondary">
                ⬅️ Go Back & Edit
            </a>
            <button type="submit" name="confirm_submit" class="btn btn-success btn-lg">
                ✅ Confirm & Submit Complaint
            </button>
        </form>

        <!-- Footer -->
        <footer class="form-footer no-print">
            <p>SDO CTS - San Pedro Division Office Complaint Tracking System</p>
            <p>Department of Education - San Pedro Division</p>
        </footer>
    </div>
</body>
</html>

