<?php
session_start();
require_once '../config/db.php';
require_once '../notifications/send-email.php';
require_once '../notifications/send-sms.php';

// Admin check
if (!isset($_SESSION['admin_id']) || $_SESSION['admin_role'] !== 'admin') {
    header('Location: login.php');
    exit;
}

// Flash messages helper
function setFlash($message, $type = 'info')
{
    $_SESSION['status_message'] = $message;
    $_SESSION['status_type'] = $type;
}

// Generate verification code
function generateCode()
{
    return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
}

// Send verification code via email or SMS
function sendVerificationCode($message_id, $user_id, $contact_method, $contact_info, $user_name, $conn)
{
    $verification_code = generateCode();
    $stmt = $conn->prepare("UPDATE messages SET verification_code = ?, code_sent_at = NOW() WHERE message_id = ?");
    $stmt->bind_param("si", $verification_code, $message_id);
    if (!$stmt->execute()) {
        setFlash("Error generating verification code.", "danger");
        return false;
    }

    $sent_successfully = false;
    if ($contact_method === 'email') {
        $subject = "BookStack Account Verification Code";
        $body = "Hi $user_name, your verification code is: $verification_code (valid for 24 hours)";
        $sent_successfully = sendEmail($contact_info, $subject, $body);
    } elseif ($contact_method === 'phone') {
        $sms_message = "BookStack Verification Code: $verification_code (valid 24h)";
        $sent_successfully = sendSMS($contact_info, $sms_message);
    }

    if ($sent_successfully) {
        setFlash("Verification code sent successfully via " . strtoupper($contact_method) . " to " . htmlspecialchars($contact_info), "success");
    } else {
        setFlash("Code generated but failed to send via " . strtoupper($contact_method) . ". Code: $verification_code", "warning");
    }
}

// Verify user response
function verifyCodeMatch($message_id, $user_id, $conn)
{
    $stmt = $conn->prepare("SELECT verification_code, user_response FROM messages WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($row = $result->fetch_assoc()) {
        $sent_code = strtoupper(trim($row['verification_code']));
        $user_reply = strtoupper(trim($row['user_response']));
        if ($sent_code === $user_reply && !empty($user_reply)) {
            $stmt1 = $conn->prepare("UPDATE messages SET code_verified = 1, status = 'resolved' WHERE message_id = ?");
            $stmt1->bind_param("i", $message_id);
            $stmt2 = $conn->prepare("UPDATE users SET is_account_verified = 1 WHERE user_id = ?");
            $stmt2->bind_param("i", $user_id);
            if ($stmt1->execute() && $stmt2->execute()) {
                setFlash("✓ Code matched! User account verified successfully.", "success");
            } else {
                setFlash("Error updating verification status.", "danger");
            }
        } else {
            setFlash("Code mismatch! Sent: $sent_code, User replied: $user_reply", "warning");
        }
    }
}

// Approve verification manually
function approveVerification($message_id, $user_id, $conn)
{
    $stmt1 = $conn->prepare("UPDATE users SET is_account_verified = TRUE WHERE user_id = ?");
    $stmt1->bind_param("i", $user_id);
    if ($stmt1->execute()) {
        $stmt2 = $conn->prepare("UPDATE messages SET status = 'resolved', code_verified = TRUE WHERE message_id = ?");
        $stmt2->bind_param("i", $message_id);
        $stmt2->execute();
        setFlash("User account verified successfully!", "success");
    } else {
        setFlash("Error verifying account.", "danger");
    }
}

// Reject verification
function rejectVerification($message_id, $conn)
{
    $stmt = $conn->prepare("UPDATE messages SET status = 'resolved' WHERE message_id = ?");
    $stmt->bind_param("i", $message_id);
    $stmt->execute();
    setFlash("Verification request rejected.", "info");
}

// Handle POST requests
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['send_verification_code'])) {
        sendVerificationCode(
            (int)$_POST['message_id'],
            (int)$_POST['user_id'],
            $_POST['contact_method'],
            $_POST['contact_info'],
            $_POST['user_name'],
            $conn
        );
    } elseif (isset($_POST['verify_code_match'])) {
        verifyCodeMatch((int)$_POST['message_id'], (int)$_POST['user_id'], $conn);
    } elseif (isset($_POST['approve_verification'])) {
        approveVerification((int)$_POST['message_id'], (int)$_POST['user_id'], $conn);
    } elseif (isset($_POST['reject_verification'])) {
        rejectVerification((int)$_POST['message_id'], $conn);
    }

    header('Location: manage-verification.php');
    exit;
}

// Fetch verification requests
$query = "
SELECT m.message_id, m.user_id, m.contact_method, m.contact_info, m.subject, m.content, m.status,
       m.created_at, m.verification_code, m.code_sent_at, m.user_response, m.responded_at, m.code_verified,
       u.user_name, u.email, u.phone_number, u.is_account_verified
FROM messages m
INNER JOIN users u ON m.user_id = u.user_id
WHERE m.subject LIKE '%Account Verification Request%'
ORDER BY CASE WHEN m.status = 'pending' THEN 0 ELSE 1 END, m.created_at DESC
";

$result = executeQuery($query);
$verification_requests = $result ? mysqli_fetch_all($result, MYSQLI_ASSOC) : [];

?>

<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Verifications - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        :root {
            --brand-green: #198754;
            --sidebar-bg: #ffffff;
            --main-bg: #f8f9fa;
        }

        body {
            background-color: var(--main-bg);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
        }

        .text-green {
            color: var(--brand-green) !important;
        }

        .brand-title {
            font-size: 1.75rem;
            letter-spacing: -0.5px;
            display: flex;
            align-items: center;
        }

        .sidebar {
            width: 260px;
            height: 100vh;
            position: fixed;
            background: var(--sidebar-bg);
            border-right: 1px solid #e5e7eb;
            z-index: 1000;
            transition: transform 0.3s ease;
        }

        .nav-link {
            color: #64748b;
            font-weight: 500;
            padding: 0.75rem 1.25rem;
            border-radius: 8px;
            margin: 0.2rem 1rem;
            text-decoration: none;
        }

        .nav-link.active {
            background-color: #f0fdf4;
            color: var(--brand-green) !important;
        }

        .main-content {
            margin-left: 260px;
            padding: 2rem;
            min-height: 100vh;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                padding-top: 1rem;
            }

            .sidebar.show {
                transform: translateX(0);
            }

            .main-content {
                margin-left: 0;
                padding: 1rem;
            }

            .sidebar .sidebar-brand {
                display: none;
            }
        }

        .data-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .stat-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .user-avatar {
            width: 48px;
            height: 48px;
            border-radius: 10px;
            background: linear-gradient(135deg, var(--brand-green) 0%, #059669 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.25rem;
            font-weight: 700;
            flex-shrink: 0;
        }

        .verification-badge {
            font-size: 0.75rem;
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-weight: 600;
        }

        .badge-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .badge-resolved {
            background: #e5e7eb;
            color: #374151;
        }

        .badge-verified {
            background: #d1fae5;
            color: #065f46;
        }

        .verification-code-box {
            background: #f0f9ff;
            border: 2px dashed #0ea5e9;
            padding: 1rem;
            border-radius: 8px;
            font-family: 'Courier New', monospace;
            font-weight: 700;
            color: #0ea5e9;
            text-align: center;
            word-wrap: break-word;
        }

        .info-row {
            display: flex;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f3f4f6;
        }

        .info-row:last-child {
            border-bottom: none;
        }

        .info-label {
            font-size: 0.875rem;
            color: #6b7280;
            font-weight: 500;
            min-width: 120px;
        }

        .info-value {
            font-size: 0.875rem;
            color: #1f2937;
            font-weight: 500;
        }
    </style>
</head>

<body>

    <div class="d-lg-none bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="navbar-brand fw-bold text-green brand-title">
            <span>BookStack</span>
        </div>
        <button class="btn btn-light border" type="button" onclick="document.getElementById('sidebar-menu').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="container-fluid p-0">
        <div class="d-flex">

            <nav class="sidebar d-flex flex-column pb-4" id="sidebar-menu">
                <div class="p-4 mb-2 sidebar-brand">
                    <div class="navbar-brand fw-bold text-green brand-title">
                        <span>BookStack</span>
                    </div>
                </div>

                <div class="nav flex-column mb-auto">
                    <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill me-3"></i>Dashboard</a>
                    <a href="manage-ebooks.php" class="nav-link"><i class="bi bi-journal-text me-3"></i>E-Books</a>
                    <a href="manage-categories.php" class="nav-link"><i class="bi bi-layers me-3"></i>Categories</a>
                    <a href="manage-users.php" class="nav-link"><i class="bi bi-people me-3"></i>Users</a>
                    <a href="manage-orders.php" class="nav-link"><i class="bi bi-cart me-3"></i>Orders</a>
                    <a href="manage-verification.php" class="nav-link active"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="manage-reports.php" class="nav-link"><i class="bi bi-bar-chart me-3"></i>Reports</a>

                    <a href="logout.php" class="nav-link text-danger mt-2"><i class="bi bi-box-arrow-left me-3"></i>Logout</a>

                    <div class="px-3 mt-3">
                        <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($_SESSION['admin_name'] ?? 'Admin'); ?>&background=198754&color=fff" class="rounded-circle me-2" width="35" height="35">
                            <div>
                                <p class="mb-0 small fw-bold text-dark"><?php echo htmlspecialchars($_SESSION['admin_name'] ?? 'Admin'); ?></p>
                                <p class="mb-0 text-muted" style="font-size: 0.7rem;">System Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="main-content w-100">
                <?php if (!empty($statusMessage)): ?>
                    <div class="alert alert-<?= $statusType ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($statusMessage); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="mb-4">
                    <h5 class="fw-bold mb-0">Manage Verifications</h5>
                    <p class="text-muted small mb-0">Review and approve user verification requests.</p>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Pending Requests</p>
                            <h4 class="fw-bold mb-0 text-warning"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'pending')) ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Approved</p>
                            <h4 class="fw-bold mb-0 text-success"><?= count(array_filter($verification_requests, fn($r) => $r['status'] === 'resolved' && $r['is_account_verified'])) ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Requests</p>
                            <h4 class="fw-bold mb-0 text-secondary"><?= count($verification_requests) ?></h4>
                        </div>
                    </div>
                </div>

                <!-- Verification Requests -->
                <?php if (empty($verification_requests)): ?>
                    <div class="card data-card border-0 shadow-sm">
                        <div class="card-body">
                            <div class="text-center py-5">
                                <i class="bi bi-inbox" style="font-size: 4rem; color: #d1d5db;"></i>
                                <h5 class="mt-3">No verification requests yet</h5>
                                <p class="text-muted">Verification requests from users will appear here</p>
                            </div>
                        </div>
                    </div>
                <?php else: ?>
                    <?php foreach ($verification_requests as $request):
                        $name_parts = explode(' ', $request['user_name']);
                        $initials = strtoupper(substr($name_parts[0], 0, 1));
                        if (isset($name_parts[1])) {
                            $initials .= strtoupper(substr($name_parts[1], 0, 1));
                        }
                    ?>
                        <div class="card data-card mb-3">
                            <div class="card-body p-4">
                                <div class="row">
                                    <div class="col-md-8">
                                        <!-- User Header -->
                                        <div class="d-flex align-items-center mb-3">
                                            <div class="user-avatar me-3"><?= $initials ?></div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1 fw-bold">
                                                    <?= htmlspecialchars($request['user_name']) ?>
                                                    <?php if ($request['is_account_verified']): ?>
                                                        <i class="bi bi-patch-check-fill text-success ms-1" title="Verified"></i>
                                                    <?php endif; ?>
                                                </h6>
                                                <div class="d-flex gap-2 flex-wrap">
                                                    <span class="verification-badge <?= $request['status'] === 'pending' ? 'badge-pending' : 'badge-resolved' ?>">
                                                        <i class="bi bi-<?= $request['status'] === 'pending' ? 'hourglass-split' : 'check-circle' ?> me-1"></i>
                                                        <?= ucfirst($request['status']) ?>
                                                    </span>
                                                    <span class="verification-badge" style="background: #dbeafe; color: #1e40af;">
                                                        <i class="bi bi-<?= $request['contact_method'] === 'email' ? 'envelope' : 'phone' ?> me-1"></i>
                                                        <?= ucfirst($request['contact_method']) ?>
                                                    </span>
                                                </div>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('M d, Y', strtotime($request['created_at'])) ?>
                                            </small>
                                        </div>

                                        <!-- User Information -->
                                        <div class="mb-3">
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-person-badge me-2"></i>User ID:</span>
                                                <span class="info-value">#<?= $request['user_id'] ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-envelope me-2"></i>Email:</span>
                                                <span class="info-value"><?= htmlspecialchars($request['email']) ?></span>
                                            </div>
                                            <div class="info-row">
                                                <span class="info-label"><i class="bi bi-phone me-2"></i>Phone:</span>
                                                <span class="info-value"><?= htmlspecialchars($request['phone_number'] ?: 'Not provided') ?></span>
                                            </div>
                                        </div>

                                        <!-- Verification Details -->
                                        <div class="mb-3">
                                            <label class="form-label fw-bold small text-muted">VERIFICATION DETAILS</label>
                                            <div class="bg-light p-3 rounded">
                                                <pre class="mb-0" style="white-space: pre-wrap; font-size: 0.875rem;"><?= htmlspecialchars($request['content']) ?></pre>
                                            </div>
                                        </div>

                                        <!-- Code Sent to User -->
                                        <?php if (!empty($request['verification_code'])): ?>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold small text-muted">CODE SENT TO USER</label>
                                                <div class="verification-code-box">
                                                    <?= htmlspecialchars($request['verification_code']) ?>
                                                </div>
                                                <small class="text-muted">
                                                    <i class="bi bi-clock"></i> Sent: <?= date('M d, Y h:i A', strtotime($request['code_sent_at'])) ?> via <?= htmlspecialchars($request['contact_info']) ?>
                                                </small>
                                            </div>
                                        <?php endif; ?>

                                        <!-- User Response -->
                                        <?php if (!empty($request['user_response'])): ?>
                                            <?php
                                            $sent_code = strtoupper(trim($request['verification_code']));
                                            $user_reply = strtoupper(trim($request['user_response']));
                                            $codes_match = ($sent_code === $user_reply);
                                            ?>
                                            <div>
                                                <label class="form-label fw-bold small text-muted">USER RESPONSE</label>
                                                <div class="p-3 rounded" style="background: <?= $codes_match ? '#f0fdf4' : '#fef3c7' ?>; border: 2px solid <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                                    <p class="mb-2 fw-bold" style="font-family: 'Courier New', monospace; color: <?= $codes_match ? '#10b981' : '#f59e0b' ?>;">
                                                        <?= nl2br(htmlspecialchars($request['user_response'])) ?>
                                                    </p>
                                                    <small class="text-muted">
                                                        <i class="bi bi-clock"></i> <?= date('M d, Y h:i A', strtotime($request['responded_at'])) ?>
                                                        <?php if ($request['code_verified']): ?>
                                                            <span class="badge bg-success ms-2"><i class="bi bi-check-circle-fill"></i> Verified</span>
                                                        <?php elseif ($codes_match): ?>
                                                            <span class="badge bg-success ms-2"><i class="bi bi-check-circle"></i> Match!</span>
                                                        <?php else: ?>
                                                            <span class="badge bg-warning ms-2"><i class="bi bi-exclamation-triangle"></i> No Match</span>
                                                        <?php endif; ?>
                                                    </small>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Action Buttons -->
                                    <div class="col-md-4">
                                        <?php if ($request['status'] === 'pending' && !$request['is_account_verified']): ?>
                                            <div class="d-grid gap-2">
                                                <!-- Match & Verify Button -->
                                                <?php if (!empty($request['verification_code']) && !empty($request['user_response']) && !$request['code_verified']): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                        <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                        <button type="submit" name="verify_code_match" class="btn btn-success w-100" onclick="return confirm('Verify if user response matches?\n\nSent: <?= htmlspecialchars($request['verification_code']) ?>\nReply: <?= htmlspecialchars($request['user_response']) ?>')">
                                                            <i class="bi bi-shield-check me-2"></i>Match & Verify
                                                        </button>
                                                    </form>
                                                <?php endif; ?>

                                                <!-- Send/Resend Code Button -->
                                                <?php if (empty($request['verification_code'])): ?>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                        <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                        <input type="hidden" name="contact_method" value="<?= $request['contact_method'] ?>">
                                                        <input type="hidden" name="contact_info" value="<?= $request['contact_info'] ?>">
                                                        <input type="hidden" name="user_name" value="<?= $request['user_name'] ?>">
                                                        <button type="submit" name="send_verification_code" class="btn btn-warning w-100" onclick="return confirm('Send verification code to <?= htmlspecialchars($request['contact_info']) ?>?')">
                                                            <i class="bi bi-send-fill me-2"></i>Send Code
                                                        </button>
                                                    </form>
                                                <?php elseif (!empty($request['user_response'])): ?>
                                                    <div class="alert alert-success mb-2 small">
                                                        <i class="bi bi-check-circle-fill me-1"></i> User responded
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-info mb-2 small">
                                                        <i class="bi bi-info-circle-fill me-1"></i> Waiting for response
                                                    </div>
                                                <?php endif; ?>

                                                <!-- Approve Button -->
                                                <form method="POST" action="">
                                                    <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                    <input type="hidden" name="user_id" value="<?= $request['user_id'] ?>">
                                                    <button type="submit" name="approve_verification" class="btn btn-primary w-100" onclick="return confirm('Manually approve <?= htmlspecialchars($request['user_name']) ?>?')">
                                                        <i class="bi bi-check-circle me-2"></i>Approve
                                                    </button>
                                                </form>

                                                <!-- Reject Button -->
                                                <form method="POST" action="">
                                                    <input type="hidden" name="message_id" value="<?= $request['message_id'] ?>">
                                                    <button type="submit" name="reject_verification" class="btn btn-danger w-100" onclick="return confirm('Reject this request?')">
                                                        <i class="bi bi-x-circle me-2"></i>Reject
                                                    </button>
                                                </form>
                                            </div>
                                        <?php elseif ($request['is_account_verified']): ?>
                                            <div class="alert alert-success text-center">
                                                <i class="bi bi-shield-check fs-2 d-block mb-2"></i>
                                                <strong>Verified</strong>
                                            </div>
                                        <?php else: ?>
                                            <div class="alert alert-secondary text-center">
                                                <i class="bi bi-info-circle fs-2 d-block mb-2"></i>
                                                <strong>Processed</strong>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>