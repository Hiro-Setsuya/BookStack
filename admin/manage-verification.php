<?php
session_start();
require_once '../config/db.php';

// Check if admin is logged in
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Get status message from session (after redirect)
if (isset($_SESSION['status_message'])) {
    $statusMessage = $_SESSION['status_message'];
    $statusType = $_SESSION['status_type'];
    unset($_SESSION['status_message']);
    unset($_SESSION['status_type']);
} else {
    $statusMessage = '';
    $statusType = '';
}

require_once '../notifications/send-email.php';
require_once '../notifications/send-sms.php';

// Handle sending verification code to user
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['send_verification_code'])) {
    $message_id = (int)$_POST['message_id'];
    $user_id = (int)$_POST['user_id'];
    $contact_method = $_POST['contact_method'];
    $contact_info = $_POST['contact_info'];
    $user_name = $_POST['user_name'];

    // Generate 6-digit verification code
    $verification_code = sprintf("%06d", mt_rand(1, 999999));

    // Update message with verification code
    $update_query = "UPDATE messages SET verification_code = '$verification_code', code_sent_at = NOW() WHERE message_id = $message_id";
    $update_result = executeQuery($update_query);

    if ($update_result) {
        $sent_successfully = false;

        // Send via email
        if ($contact_method === 'email') {
            $email_subject = "BookStack Account Verification Code";
            $email_body = "
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                    .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                    .header { background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 30px; text-align: center; border-radius: 10px 10px 0 0; }
                    .content { background: #f9f9f9; padding: 30px; border-radius: 0 0 10px 10px; }
                    .code-box { background: white; border: 3px dashed #667eea; border-radius: 12px; padding: 25px; text-align: center; margin: 20px 0; }
                    .code { font-size: 36px; font-weight: bold; letter-spacing: 2px; color: #667eea; font-family: monospace; word-break: break-all; }
                    .footer { text-align: center; margin-top: 20px; color: #666; font-size: 12px; }
                    .info-box { background: #e3f2fd; border-left: 4px solid #2196f3; padding: 15px; margin: 15px 0; border-radius: 4px; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <div class='header'>
                        <h1 style='margin: 0;'>📚 BookStack</h1>
                        <p style='margin: 10px 0 0 0;'>Account Verification</p>
                    </div>
                    <div class='content'>
                        <p>Hi <strong>" . htmlspecialchars($user_name) . "</strong>,</p>
                        <p>Your account verification request has been processed. Please use the confirmation code below to complete your verification:</p>
                        
                        <div class='code-box'>
                            <p style='margin: 0 0 10px 0; color: #666; font-size: 14px;'>Your Verification Code</p>
                            <div class='code'>$verification_code</div>
                            <p style='margin: 10px 0 0 0; color: #666; font-size: 12px;'>Valid for 24 hours</p>
                        </div>
                        
                        <div class='info-box'>
                            <p style='margin: 0;'><strong>📌 Important:</strong> Please reply to the verification request with this code to confirm your identity.</p>
                        </div>
                        
                        <p style='margin-top: 20px;'>If you didn't request this verification, please ignore this email or contact support.</p>
                        
                        <p style='margin-top: 30px;'>Best regards,<br><strong>BookStack Team</strong></p>
                    </div>
                    <div class='footer'>
                        <p>This is an automated message. Please do not reply to this email.</p>
                        <p>&copy; 2024 BookStack. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
            ";

            $sent_successfully = sendEmail($contact_info, $email_subject, $email_body);
        }
        // Send via SMS
        elseif ($contact_method === 'phone') {
            $sms_message = "BookStack Verification Code: $verification_code\n\nPlease reply with this code to complete your account verification. Valid for 24 hours.\n\n- BookStack Team";
            $sent_successfully = sendSMS($contact_info, $sms_message);
        }

        if ($sent_successfully) {
            $_SESSION['status_message'] = "Verification code sent successfully via " . strtoupper($contact_method) . " to " . htmlspecialchars($contact_info);
            $_SESSION['status_type'] = 'success';
        } else {
            $_SESSION['status_message'] = "Code generated but failed to send via " . strtoupper($contact_method) . ". Code: $verification_code";
            $_SESSION['status_type'] = 'warning';
        }
    } else {
        $_SESSION['status_message'] = 'Error generating verification code.';
        $_SESSION['status_type'] = 'danger';
    }

    // Redirect to prevent form resubmission
    header('Location: manage-verification.php');
    exit;
}

// Handle matching user response with verification code
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['verify_code_match'])) {
    $message_id = (int)$_POST['message_id'];
    $user_id = (int)$_POST['user_id'];

    // Get the message details
    $check_query = "SELECT verification_code, user_response FROM messages WHERE message_id = $message_id";
    $check_result = executeQuery($check_query);

    if ($check_result && mysqli_num_rows($check_result) > 0) {
        $msg_data = mysqli_fetch_assoc($check_result);
        $sent_code = strtoupper(trim($msg_data['verification_code']));
        $user_reply = strtoupper(trim($msg_data['user_response']));

        if (!empty($user_reply) && $sent_code === $user_reply) {
            // Codes match! Mark as verified
            $update_message = "UPDATE messages SET code_verified = 1, status = 'resolved' WHERE message_id = $message_id";
            $update_user = "UPDATE users SET is_account_verified = 1 WHERE user_id = $user_id";

            if (executeQuery($update_message) && executeQuery($update_user)) {
                $_SESSION['status_message'] = '✓ Code matched! User account has been verified successfully.';
                $_SESSION['status_type'] = 'success';
            } else {
                $_SESSION['status_message'] = 'Error updating verification status.';
                $_SESSION['status_type'] = 'danger';
            }
        } else {
            $_SESSION['status_message'] = "Code mismatch! Sent: $sent_code, User replied: $user_reply. Cannot verify.";
            $_SESSION['status_type'] = 'warning';
        }
    }

    // Redirect to prevent form resubmission
    header('Location: manage-verification.php');
    exit;
}

// Handle verification approval
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['approve_verification'])) {
    $message_id = (int)$_POST['message_id'];
    $user_id = (int)$_POST['user_id'];

    // Update user verification status
    $update_user = "UPDATE users SET is_account_verified = TRUE WHERE user_id = $user_id";
    $result = executeQuery($update_user);

    if ($result) {
        // Update message status
        $update_message = "UPDATE messages SET status = 'resolved', code_verified = TRUE WHERE message_id = $message_id";
        executeQuery($update_message);

        $_SESSION['status_message'] = 'User account verified successfully!';
        $_SESSION['status_type'] = 'success';
    } else {
        $_SESSION['status_message'] = 'Error verifying account.';
        $_SESSION['status_type'] = 'danger';
    }

    // Redirect to prevent form resubmission
    header('Location: manage-verification.php');
    exit;
}

// Handle verification rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reject_verification'])) {
    $message_id = (int)$_POST['message_id'];

    $update_message = "UPDATE messages SET status = 'resolved' WHERE message_id = $message_id";
    if (executeQuery($update_message)) {
        $_SESSION['status_message'] = 'Verification request rejected.';
        $_SESSION['status_type'] = 'info';
    }

    // Redirect to prevent form resubmission
    header('Location: manage-verification.php');
    exit;
}

// Fetch all verification requests
$query = "
    SELECT 
        m.message_id,
        m.user_id,
        m.contact_method,
        m.contact_info,
        m.subject,
        m.content,
        m.status,
        m.created_at,
        m.verification_code,
        m.code_sent_at,
        m.user_response,
        m.responded_at,
        m.code_verified,
        u.user_name,
        u.email,
        u.phone_number,
        u.is_account_verified
    FROM messages m
    INNER JOIN users u ON m.user_id = u.user_id
    WHERE m.subject LIKE '%Account Verification Request%'
    ORDER BY 
        CASE WHEN m.status = 'pending' THEN 0 ELSE 1 END,
        m.created_at DESC
";

$result = executeQuery($query);
$verification_requests = [];
if ($result) {
    while ($row = mysqli_fetch_assoc($result)) {
        $verification_requests[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Verification Requests - Admin</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --primary-color: #55b6e7;
            --success-color: #10b981;
            --danger-color: #ef4444;
            --warning-color: #f59e0b;
            --bg-light: #f8fafb;
            --text-dark: #1f2937;
            --text-muted: #6b7280;
            --border-color: #e5e7eb;
        }

        body {
            background-color: var(--bg-light);
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            color: var(--text-dark);
            line-height: 1.6;
        }

        .logo-svg {
            width: 36px;
            height: 36px;
            color: var(--primary-color);
            transition: transform 0.2s ease;
        }

        .logo-svg:hover {
            transform: scale(1.05);
        }

        /* Navbar Styling */
        .navbar {
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-size: 1.25rem;
            font-weight: 600;
        }

        .nav-link {
            font-weight: 500;
            transition: color 0.2s ease;
            padding: 0.5rem 1rem;
        }

        .nav-link:hover {
            color: var(--primary-color) !important;
        }

        /* Page Header */
        .page-header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2.5rem 0;
            margin-bottom: 2rem;
            border-radius: 0 0 20px 20px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.07);
        }

        .page-header h1 {
            font-weight: 700;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .page-header p {
            opacity: 0.95;
            font-size: 1.05rem;
        }

        /* Statistics Cards */
        .stat-card {
            border: none;
            border-radius: 16px;
            padding: 1.75rem;
            transition: all 0.3s ease;
            height: 100%;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .stat-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 24px rgba(0, 0, 0, 0.1);
        }

        .stat-icon {
            width: 56px;
            height: 56px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            margin-bottom: 1rem;
        }

        .stat-icon.warning {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #d97706;
        }

        .stat-icon.success {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #059669;
        }

        .stat-icon.primary {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #2563eb;
        }

        .stat-number {
            font-size: 2.25rem;
            font-weight: 700;
            line-height: 1;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .stat-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* Request Cards */
        .request-card {
            border: 1px solid var(--border-color);
            border-radius: 16px;
            transition: all 0.3s ease;
            background: white;
            overflow: hidden;
        }

        .request-card:hover {
            box-shadow: 0 8px 24px rgba(0, 0, 0, 0.08);
            border-color: var(--primary-color);
        }

        .request-header {
            background: linear-gradient(135deg, #f9fafb 0%, #f3f4f6 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .user-avatar {
            width: 56px;
            height: 56px;
            border-radius: 12px;
            background: linear-gradient(135deg, var(--primary-color) 0%, #4da5d1 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .user-info h5 {
            font-size: 1.125rem;
            font-weight: 600;
            margin-bottom: 0.25rem;
            color: var(--text-dark);
        }

        .info-label {
            font-size: 0.875rem;
            color: var(--text-muted);
            font-weight: 500;
            min-width: 100px;
        }

        .info-value {
            font-size: 0.9375rem;
            color: var(--text-dark);
        }

        /* Status Badges */
        .status-badge {
            font-size: 0.8125rem;
            padding: 0.4rem 0.9rem;
            border-radius: 8px;
            font-weight: 600;
            letter-spacing: 0.3px;
        }

        .badge-pending {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            color: #92400e;
        }

        .badge-resolved {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #374151;
        }

        .badge-email {
            background: linear-gradient(135deg, #dbeafe 0%, #bfdbfe 100%);
            color: #1e40af;
        }

        .badge-phone {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        /* Verification Details */
        .verification-details {
            background: #f9fafb;
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.25rem;
            margin-top: 1rem;
        }

        .verification-details h6 {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .verification-code {
            background: white;
            border: 2px dashed var(--primary-color);
            padding: 1rem;
            border-radius: 10px;
            font-family: 'Courier New', monospace;
            font-weight: 600;
            font-size: 1rem;
            color: var(--primary-color);
            text-align: center;
            margin: 1rem 0;
        }

        /* Action Buttons */
        .btn-approve {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(16, 185, 129, 0.2);
        }

        .btn-approve:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(16, 185, 129, 0.3);
            color: white;
        }

        .btn-reject {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            border: none;
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.2s ease;
            box-shadow: 0 2px 4px rgba(239, 68, 68, 0.2);
        }

        .btn-reject:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(239, 68, 68, 0.3);
            color: white;
        }

        /* Alert Boxes */
        .status-alert {
            border-radius: 12px;
            padding: 1.25rem;
            border: none;
            font-size: 0.9375rem;
        }

        .status-alert.verified {
            background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%);
            color: #065f46;
        }

        .status-alert.processed {
            background: linear-gradient(135deg, #e5e7eb 0%, #d1d5db 100%);
            color: #374151;
        }

        /* Empty State */
        .empty-state {
            padding: 4rem 2rem;
            text-align: center;
        }

        .empty-state i {
            font-size: 5rem;
            color: #d1d5db;
            margin-bottom: 1.5rem;
        }

        .empty-state h5 {
            color: var(--text-dark);
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .empty-state p {
            color: var(--text-muted);
        }

        /* Responsive Improvements */
        @media (max-width: 768px) {
            .page-header {
                padding: 2rem 0;
            }

            .page-header h1 {
                font-size: 1.5rem;
            }

            .stat-card {
                margin-bottom: 1rem;
            }

            .request-header {
                padding: 1rem;
            }

            .user-avatar {
                width: 48px;
                height: 48px;
                font-size: 1.25rem;
            }
        }

        /* Animations */
        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .request-card {
            animation: fadeIn 0.3s ease-out;
        }

        /* Filter Tabs */
        .filter-tabs {
            background: white;
            border-radius: 12px;
            padding: 0.5rem;
            margin-bottom: 1.5rem;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .filter-tab {
            border: none;
            background: transparent;
            color: var(--text-muted);
            padding: 0.625rem 1.25rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .filter-tab:hover {
            background: var(--bg-light);
            color: var(--text-dark);
        }

        .filter-tab.active {
            background: var(--primary-color);
            color: white;
        }
    </style>
</head>

<body>
    <!-- Header -->
    <nav class="navbar navbar-expand-lg bg-white border-bottom sticky-top">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand d-flex align-items-center gap-2" href="dashboard.php">
                <svg class="logo-svg" fill="currentColor" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M13.8261 17.4264C16.7203 18.1174 20.2244 18.5217 24 18.5217C27.7756 18.5217 31.2797 18.1174 34.1739 17.4264C36.9144 16.7722 39.9967 15.2331 41.3563 14.1648L24.8486 40.6391C24.4571 41.267 23.5429 41.267 23.1514 40.6391L6.64374 14.1648C8.00331 15.2331 11.0856 16.7722 13.8261 17.4264Z"></path>
                </svg>
                <strong>BookStack Admin</strong>
            </a>
            <div class="ms-auto d-flex align-items-center gap-3">
                <a class="nav-link text-secondary" href="dashboard.php"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                <a class="nav-link text-secondary" href="manage-users.php"><i class="bi bi-people me-1"></i>Users</a>
                <a class="nav-link text-secondary" href="logout.php"><i class="bi bi-box-arrow-right me-1"></i>Logout</a>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="page-header">
        <div class="container">
            <h1>
                <i class="bi bi-shield-check me-3"></i>
                Account Verification Requests
            </h1>
            <p class="mb-0">Review and approve user verification requests with ease</p>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container pb-5">
        <!-- Status Message -->
        <?php if (!empty($statusMessage)): ?>
            <div class="alert alert-<?= $statusType ?> alert-dismissible fade show shadow-sm" role="alert" style="border-radius: 12px;">
                <i class="bi bi-<?= $statusType === 'success' ? 'check-circle' : 'info-circle' ?>-fill me-2"></i>
                <?= htmlspecialchars($statusMessage) ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Statistics -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon warning">
                        <i class="bi bi-hourglass-split"></i>
                    </div>
                    <div class="stat-number"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'pending')) ?></div>
                    <div class="stat-label">Pending Requests</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon success">
                        <i class="bi bi-check-circle"></i>
                    </div>
                    <div class="stat-number"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'resolved' && $r['is_account_verified'])) ?></div>
                    <div class="stat-label">Approved</div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card stat-card">
                    <div class="stat-icon primary">
                        <i class="bi bi-inbox"></i>
                    </div>
                    <div class="stat-number"><?= count($verification_requests) ?></div>
                    <div class="stat-label">Total Requests</div>
                </div>
            </div>
        </div>

        <!-- Verification Requests -->
        <?php if (empty($verification_requests)): ?>
            <div class="card border-0 shadow-sm" style="border-radius: 16px;">
                <div class="card-body">
                    <div class="empty-state">
                        <i class="bi bi-inbox"></i>
                        <h5>No verification requests yet</h5>
                        <p>Verification requests from users will appear here</p>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php
            $initials_cache = [];
            foreach ($verification_requests as $request):
                // Get user initials
                $name_parts = explode(' ', $request['user_name']);
                $initials = strtoupper(substr($name_parts[0], 0, 1));
                if (isset($name_parts[1])) {
                    $initials .= strtoupper(substr($name_parts[1], 0, 1));
                }
            ?>
                <div class="card request-card mb-4">
                    <!-- Card Header -->
                    <div class="request-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="user-avatar">
                                <?= $initials ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="user-info">
                                    <h5 class="mb-1">
                                        <?= htmlspecialchars($request['user_name']) ?>
                                        <?php if ($request['is_account_verified']): ?>
                                            <i class="bi bi-patch-check-fill text-primary ms-1" title="Already Verified"></i>
                                        <?php endif; ?>
                                    </h5>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <span class="status-badge <?= $request['status'] === 'pending' ? 'badge-pending' : 'badge-resolved' ?>">
                                            <i class="bi bi-<?= $request['status'] === 'pending' ? 'hourglass-split' : 'check-circle' ?> me-1"></i>
                                            <?= ucfirst($request['status']) ?>
                                        </span>
                                        <span class="status-badge <?= $request['contact_method'] === 'email' ? 'badge-email' : 'badge-phone' ?>">
                                            <i class="bi bi-<?= $request['contact_method'] === 'email' ? 'envelope' : 'phone' ?> me-1"></i>
                                            <?= ucfirst($request['contact_method']) ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="text-end text-muted">
                                <small>
                                    <i class="bi bi-clock me-1"></i>
                                    <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                </small>
                            </div>
                        </div>
                    </div>

                    <!-- Card Body -->
                    <div class="card-body p-4">
                        <div class="row">
                            <div class="col-md-8">
                                <!-- User Information -->
                                <div class="mb-4">
                                    <div class="row g-3">
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <span class="info-label"><i class="bi bi-person-badge me-2"></i>User ID:</span>
                                                <span class="info-value">#<?= $request['user_id'] ?></span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <span class="info-label"><i class="bi bi-envelope me-2"></i>Email:</span>
                                                <span class="info-value"><?= htmlspecialchars($request['email']) ?></span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <span class="info-label"><i class="bi bi-phone me-2"></i>Phone:</span>
                                                <span class="info-value"><?= htmlspecialchars($request['phone_number'] ?: 'Not provided') ?></span>
                                            </div>
                                        </div>
                                        <div class="col-12">
                                            <div class="d-flex">
                                                <span class="info-label"><i class="bi bi-calendar-check me-2"></i>Requested:</span>
                                                <span class="info-value"><?= date('M d, Y h:i A', strtotime($request['created_at'])) ?></span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Verification Details -->
                                <div class="verification-details">
                                    <h6><i class="bi bi-file-text me-2"></i>Verification Details</h6>
                                    <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem; background: white; padding: 1rem; border-radius: 8px; border: 1px solid var(--border-color);"><?= htmlspecialchars($request['content']) ?></pre>
                                </div>

                                <!-- Verification Code Section (what was sent to user) -->
                                <?php if (!empty($request['verification_code'])): ?>
                                    <div class="verification-details mt-3" style="background: #f0f9ff; border-color: #0ea5e9;">
                                        <h6><i class="bi bi-key me-2"></i>Code Sent to User</h6>
                                        <div style="background: white; border: 2px dashed #0ea5e9; padding: 1rem; border-radius: 10px; text-align: center;">
                                            <p class="mb-2" style="font-family: 'Courier New', monospace; font-weight: 700; font-size: 1.2rem; color: #0ea5e9; margin: 0; word-wrap: break-word;">
                                                <?= htmlspecialchars($request['verification_code']) ?>
                                            </p>
                                            <small class="text-muted d-block mt-2">
                                                <i class="bi bi-clock"></i> Sent: <?= date('M d, Y h:i A', strtotime($request['code_sent_at'])) ?>
                                            </small>
                                        </div>
                                        <small class="text-muted mt-2 d-block">
                                            <i class="bi bi-<?= $request['contact_method'] === 'email' ? 'envelope' : 'phone' ?>"></i>
                                            Sent to: <?= htmlspecialchars($request['contact_info']) ?>
                                        </small>
                                    </div>
                                <?php endif; ?>

                                <!-- User Response Section -->
                                <?php if (!empty($request['user_response'])): ?>
                                    <?php
                                    $sent_code = strtoupper(trim($request['verification_code']));
                                    $user_reply = strtoupper(trim($request['user_response']));
                                    $codes_match = ($sent_code === $user_reply);
                                    ?>
                                    <div class="verification-details mt-3" style="background: <?= $codes_match ? '#f0fdf4' : '#fef3c7' ?>; border-color: <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                        <h6><i class="bi bi-chat-left-text me-2"></i>User Response</h6>
                                        <div style="background: white; padding: 1rem; border-radius: 8px; border: 2px solid <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                            <p class="mb-2" style="font-size: 0.9rem; font-family: 'Courier New', monospace; font-weight: 700; color: <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                                <?= nl2br(htmlspecialchars($request['user_response'])) ?>
                                            </p>
                                            <small class="text-muted">
                                                <i class="bi bi-clock"></i> Responded: <?= date('M d, Y h:i A', strtotime($request['responded_at'])) ?>
                                            </small>
                                            <?php if ($request['code_verified']): ?>
                                                <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> Code Verified</span>
                                            <?php elseif ($codes_match): ?>
                                                <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Codes Match!</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-triangle"></i> Does Not Match</span>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- Action Buttons -->
                            <div class="col-md-4">
                                <?php if ($request['status'] === 'pending' && !$request['is_account_verified']): ?>
                                    <div class="d-grid gap-3">
                                        <!-- Match & Verify Button (if code sent and user responded) -->
                                        <?php if (!empty($request['verification_code']) && !empty($request['user_response']) && !$request['code_verified']): ?>
                                            <form method="POST" action="" class="mb-0">
                                                <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                <button type="submit" name="verify_code_match" class="btn w-100" style="background: linear-gradient(135deg, #10b981 0%, #059669 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(16, 185, 129, 0.3);" onclick="return confirm('🔍 Verify if user response matches the sent code?\n\nSent Code: <?= htmlspecialchars($request['verification_code']) ?>\nUser Reply: <?= htmlspecialchars($request['user_response']) ?>')">
                                                    <i class="bi bi-shield-check me-2"></i>Match & Verify Code
                                                </button>
                                            </form>
                                        <?php endif; ?>

                                        <!-- Send Verification Code (only if no code was sent yet) -->
                                        <?php if (empty($request['verification_code'])): ?>
                                            <form method="POST" action="" class="mb-0">
                                                <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                <input type="hidden" name="contact_method" value="<?= $request['contact_method'] ?>">
                                                <input type="hidden" name="contact_info" value="<?= $request['contact_info'] ?>">
                                                <input type="hidden" name="user_name" value="<?= $request['user_name'] ?>">
                                                <button type="submit" name="send_verification_code" class="btn w-100" style="background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%); color: white; border: none; padding: 0.75rem 1.5rem; border-radius: 10px; font-weight: 600; box-shadow: 0 2px 4px rgba(245, 158, 11, 0.2);" onclick="return confirm('⚠️ Send a NEW verification code?\n\nNote: User already received a code when they submitted the request.\nOnly send a new code if the original was not received.\n\nMethod: <?= strtoupper($request['contact_method']) ?>\nTo: <?= htmlspecialchars($request['contact_info']) ?>')">
                                                    <i class="bi bi-send-fill me-2"></i>Resend New Code
                                                </button>
                                            </form>
                                        <?php elseif (!empty($request['user_response'])): ?>
                                            <div class="alert alert-success mb-0" style="border-radius: 10px; font-size: 0.875rem;">
                                                <i class="bi bi-check-circle-fill me-2"></i>
                                                <strong>User Responded</strong>
                                                <p class="mb-0 mt-1 small">Click "Match & Verify Code" above to check if response matches</p>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-info mb-0" style="border-radius: 10px; font-size: 0.875rem;">
                                                <i class="bi bi-info-circle-fill me-2"></i>
                                                <strong>Code Already Sent</strong>
                                                <p class="mb-0 mt-1 small">Waiting for user response. Code sent on <?= date('M d, h:i A', strtotime($request['code_sent_at'])) ?></p>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Approve Button -->
                                        <form method="POST" action="" class="mb-0">
                                            <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                            <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                            <button type="submit" name="approve_verification" class="btn btn-approve w-100" onclick="return confirm('✓ Manually approve without code verification?\n\nUser: <?= htmlspecialchars($request['user_name']) ?>\nEmail: <?= htmlspecialchars($request['email']) ?>\n\nUse this only if you verified the user through other means.')">
                                                <i class="bi bi-check-circle-fill me-2"></i>Approve Request
                                            </button>
                                        </form>

                                        <!-- Reject Button -->
                                        <form method="POST" action="" class="mb-0">
                                            <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                            <button type="submit" name="reject_verification" class="btn btn-reject w-100" onclick="return confirm('✗ Reject this verification request?\n\nThis action cannot be undone.')">
                                                <i class="bi bi-x-circle-fill me-2"></i>Reject Request
                                            </button>
                                        </form>
                                    </div>
                                <?php elseif ($request['is_account_verified']): ?>
                                    <div class="status-alert verified">
                                        <div class="text-center">
                                            <i class="bi bi-shield-check fs-1 mb-2 d-block"></i>
                                            <strong>Account Verified</strong>
                                            <p class="mb-0 small mt-2">This user's account has been successfully verified</p>
                                        </div>
                                    </div>
                                <?php else: ?>
                                    <div class="status-alert processed">
                                        <div class="text-center">
                                            <i class="bi bi-info-circle fs-1 mb-2 d-block"></i>
                                            <strong>Request Processed</strong>
                                            <p class="mb-0 small mt-2">This verification request has been handled</p>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>