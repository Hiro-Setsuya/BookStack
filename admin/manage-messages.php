<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../config/db.php';

// Include mail configuration
require_once '../config/mail.php';

// Sync Admin Name from session variable established in login.php
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Get filter status from URL parameter
$filter_status = isset($_GET['status']) ? $_GET['status'] : null;
if (!in_array($filter_status, ['pending', 'read', 'resolved'])) {
    $filter_status = null;
}

// Helper function to get messages using your executeQuery function
function getMessages($status = null)
{
    global $conn; // Use your mysqli connection

    $query = "SELECT m.*, u.user_name, u.email FROM messages m LEFT JOIN users u ON m.user_id = u.user_id";

    if ($status) {
        $status_escaped = mysqli_real_escape_string($conn, $status);
        $query .= " WHERE m.status = '$status_escaped'";
    }

    $query .= " ORDER BY m.created_at DESC";

    $result = executeQuery($query);
    $messages = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }

    return $messages;
}

// Group messages by user_id and get latest 3 per user
function groupMessagesByUser($messages)
{
    $grouped = [];
    $counters = [];

    foreach ($messages as $message) {
        $user_id = $message['user_id'] ?: $message['contact_info'];

        if (!isset($grouped[$user_id])) {
            $grouped[$user_id] = [];
            $counters[$user_id] = 0;
        }

        // Limit to 3 messages per user
        if ($counters[$user_id] < 3) {
            $grouped[$user_id][] = $message;
            $counters[$user_id]++;
        }
    }

    return $grouped;
}

// Helper function to update message status
function updateMessageStatus($message_id, $status)
{
    global $conn;

    $message_id = (int)$message_id;
    $status_escaped = mysqli_real_escape_string($conn, $status);

    $query = "UPDATE messages SET status = '$status_escaped' WHERE message_id = $message_id";
    return executeQuery($query);
}

// Handle email response (POST-Redirect-GET pattern)
if (isset($_POST['send_response'])) {
    $message_id = (int)$_POST['message_id'];
    $response_subject = mysqli_real_escape_string($conn, $_POST['response_subject']);
    $response_body = $_POST['response_body']; // Don't escape HTML content

    // Get message details
    $query = "SELECT m.*, u.email FROM messages m LEFT JOIN users u ON m.user_id = u.user_id WHERE m.message_id = $message_id";
    $result = executeQuery($query);

    if ($result && mysqli_num_rows($result) === 1) {
        $message = mysqli_fetch_assoc($result);
        $to_email = $message['contact_info'] ?? $message['email'];

        if (sendEmail($to_email, $response_subject, $response_body)) {
            // Update status to resolved after sending response
            updateMessageStatus($message_id, 'resolved');
            $_SESSION['success_message'] = "Email response sent successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to send email response.";
        }
    }

    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . ($filter_status ? "?status=$filter_status" : ""));
    exit;
}

// Handle message status updates
if (isset($_POST['update_status'])) {
    $message_id = (int)$_POST['message_id'];
    $new_status = mysqli_real_escape_string($conn, $_POST['status']);

    updateMessageStatus($message_id, $new_status);

    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . ($filter_status ? "?status=$filter_status" : ""));
    exit;
}

// Handle refresh button click
if (isset($_GET['refresh'])) {
    // Get the base URL for the current site
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https' : 'http';
    $host = $_SERVER['HTTP_HOST'];
    $base_url = $protocol . '://' . $host;

    // Use absolute URL for the API call
    $api_url = $base_url . dirname(dirname($_SERVER['SCRIPT_NAME'])) . '/api/check-email.php';

    // Use cURL to call the check-email.php script
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $api_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_MAXREDIRS, 3);

    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($error) {
        $_SESSION['error_message'] = "Curl error: " . $error;
    } else if ($http_code !== 200) {
        $_SESSION['error_message'] = "HTTP error: " . $http_code . " - Response: " . substr($response, 0, 100);
    } else {
        $result = json_decode($response, true);

        if ($result && isset($result['status'])) {
            if ($result['status'] === 'success') {
                $_SESSION['success_message'] = "Email check completed: " . $result['message'];
            } else {
                $_SESSION['error_message'] = "Email check failed: " . ($result['message'] ?? 'Unknown error');
            }
        } else {
            $_SESSION['error_message'] = "Invalid response format: " . substr($response, 0, 100);
        }
    }

    // Redirect to prevent resubmission
    header("Location: " . $_SERVER['PHP_SELF'] . ($filter_status ? "?status=$filter_status" : ""));
    exit;
}

// Get messages based on filter
$all_messages = getMessages($filter_status);
$pending_messages = getMessages('pending');
$read_messages = getMessages('read');
$resolved_messages = getMessages('resolved');

// Group messages by user (limit to 3 per user)
$grouped_messages = groupMessagesByUser($all_messages);

// Get messages from session if set
$success_message = $_SESSION['success_message'] ?? null;
$error_message = $_SESSION['error_message'] ?? null;

// Clear messages from session
unset($_SESSION['success_message']);
unset($_SESSION['error_message']);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Messages - BookStack</title>
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

        .message-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: #fff;
            margin-bottom: 1rem;
            transition: box-shadow 0.2s ease;
        }

        .message-card:hover {
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-badge {
            padding: 0.25rem 0.5rem;
            border-radius: 12px;
            font-size: 0.75rem;
            font-weight: 500;
        }

        .status-pending {
            background-color: #fff3cd;
            color: #856404;
        }

        .status-read {
            background-color: #d1ecf1;
            color: #0c5460;
        }

        .status-resolved {
            background-color: #d4edda;
            color: #155724;
        }

        .conversation-header {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 0.75rem 1rem;
            margin-bottom: 0.5rem;
            cursor: pointer;
        }

        .conversation-header:hover {
            background-color: #e9ecef;
        }

        .message-item {
            border-left: 3px solid #e9ecef;
            padding-left: 1rem;
            margin-bottom: 1rem;
            padding: 0.75rem;
            border-radius: 4px;
            background-color: #f8f9fa;
        }

        .message-item.received {
            border-left-color: var(--brand-green);
        }

        .message-item.sent {
            border-left-color: #0d6efd;
        }

        .message-item.verification {
            border-left-color: #ffc107;
        }

        .accordion-button {
            padding: 1rem 1.25rem;
        }

        .accordion-body {
            padding: 1.25rem;
        }

        .unread-indicator {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: var(--brand-green);
            margin-left: 5px;
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
                    <a href="manage-verification.php" class="nav-link"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="manage-messages.php" class="nav-link active"><i class="bi bi-envelope me-3"></i>Messages</a>

                    <a href="logout.php" class="nav-link text-danger mt-2"><i class="bi bi-box-arrow-left me-3"></i>Logout</a>

                    <div class="px-3 mt-3">
                        <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=198754&color=fff" class="rounded-circle me-2" width="35" height="35">
                            <div>
                                <p class="mb-0 small fw-bold text-dark"><?php echo htmlspecialchars($adminName); ?></p>
                                <p class="mb-0 text-muted" style="font-size: 0.7rem;">System Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="main-content w-100">
                <header class="d-flex justify-content-between align-items-start mb-4">
                    <div>
                        <h3 class="fw-bold mb-0">Messages</h3>
                        <p class="text-muted small">Manage user communications and support requests.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="?refresh=1<?php echo $filter_status ? '&status=' . $filter_status : ''; ?>" class="btn btn-outline-primary btn-sm">
                            <i class="bi bi-envelope-check me-2"></i>Check Email
                        </a>
                        <button class="btn btn-outline-secondary btn-sm" onclick="location.reload()">
                            <i class="bi bi-arrow-clockwise me-2"></i>Refresh
                        </button>
                    </div>
                </header>

                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h4 class="fw-bold mb-0 text-secondary"><?php echo count($all_messages); ?></h4>
                            <p class="text-muted small mb-0">Total Messages</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h4 class="fw-bold mb-0 text-warning"><?php echo count($pending_messages); ?></h4>
                            <p class="text-muted small mb-0">Pending</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h4 class="fw-bold mb-0 text-info"><?php echo count($read_messages); ?></h4>
                            <p class="text-muted small mb-0">Read</p>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stat-card text-center">
                            <h4 class="fw-bold mb-0 text-success"><?php echo count($resolved_messages); ?></h4>
                            <p class="text-muted small mb-0">Resolved</p>
                        </div>
                    </div>
                </div>

                <div class="card message-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h6 class="fw-bold mb-0">All Conversations</h6>
                            <div class="btn-group btn-group-sm">
                                <a href="manage-messages.php" class="btn btn-outline-secondary <?php echo $filter_status === null ? 'active' : ''; ?>">All</a>
                                <a href="manage-messages.php?status=pending" class="btn btn-outline-secondary <?php echo $filter_status === 'pending' ? 'active' : ''; ?>">Pending</a>
                                <a href="manage-messages.php?status=read" class="btn btn-outline-secondary <?php echo $filter_status === 'read' ? 'active' : ''; ?>">Read</a>
                                <a href="manage-messages.php?status=resolved" class="btn btn-outline-secondary <?php echo $filter_status === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($grouped_messages)): ?>
                            <div class="accordion" id="conversationsAccordion">
                                <?php foreach ($grouped_messages as $user_id => $user_messages): ?>
                                    <?php
                                    $first_message = $user_messages[0];
                                    $user_name = $first_message['user_name'] ?? 'Unknown User';
                                    $contact_info = $first_message['contact_info'] ?? $first_message['email'];
                                    $user_status = $first_message['status'];

                                    // Count unread messages in this conversation
                                    $unread_count = 0;
                                    foreach ($user_messages as $msg) {
                                        if ($msg['status'] === 'pending') $unread_count++;
                                    }
                                    ?>
                                    <div class="card mb-3">
                                        <div class="card-header conversation-header" data-bs-toggle="collapse" data-bs-target="#collapse<?php echo $first_message['message_id']; ?>">
                                            <div class="d-flex align-items-center">
                                                <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user_name); ?>&background=198754&color=fff" class="rounded-circle me-3" width="40" height="40">
                                                <div>
                                                    <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($user_name); ?></h6>
                                                    <small class="text-muted">
                                                        <i class="bi bi-<?php echo $first_message['contact_method'] === 'email' ? 'envelope' : 'phone'; ?> me-1"></i>
                                                        <?php echo htmlspecialchars($contact_info); ?>
                                                    </small>
                                                </div>
                                                <div class="ms-auto">
                                                    <span class="status-badge status-<?php echo $user_status; ?>">
                                                        <?php echo ucfirst($user_status); ?>
                                                    </span>
                                                    <?php if ($unread_count > 0): ?>
                                                        <span class="badge bg-danger ms-2"><?php echo $unread_count; ?></span>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                        <div id="collapse<?php echo $first_message['message_id']; ?>" class="collapse">
                                            <div class="card-body">
                                                <?php foreach ($user_messages as $message): ?>
                                                    <div class="message-item <?php
                                                                                if (strpos($message['content'], 'Verification') !== false || strpos($message['subject'], 'Verification') !== false) {
                                                                                    echo 'verification';
                                                                                } elseif ($message['user_response']) {
                                                                                    echo 'sent';
                                                                                } else {
                                                                                    echo 'received';
                                                                                }
                                                                                ?>">
                                                        <div class="d-flex justify-content-between">
                                                            <div>
                                                                <strong><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></strong>
                                                                <div class="small text-muted">
                                                                    <?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?>
                                                                </div>
                                                            </div>
                                                            <div>
                                                                <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#viewMessageModal<?php echo $message['message_id']; ?>">
                                                                    <i class="bi bi-eye"></i>
                                                                </button>
                                                            </div>
                                                        </div>
                                                        <div class="mt-2">
                                                            <p class="mb-0"><?php echo htmlspecialchars(substr($message['content'], 0, 100)) . (strlen($message['content']) > 100 ? '...' : ''); ?></p>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                                <div class="mt-3">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#responseModal<?php echo $first_message['message_id']; ?>">
                                                        <i class="bi bi-reply me-1"></i>Reply
                                                    </button>
                                                    <a href="manage-messages.php<?php echo $filter_status ? '?status=' . $filter_status : ''; ?>" class="btn btn-outline-secondary btn-sm ms-1">
                                                        <i class="bi bi-three-dots me-1"></i>View All
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5">
                                <i class="bi bi-envelope-open text-muted" style="font-size: 3rem;"></i>
                                <h5 class="mt-3">No messages found</h5>
                                <p class="text-muted">Messages from users will appear here</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Message Detail Modals -->
    <?php foreach ($all_messages as $message): ?>
        <div class="modal fade" id="viewMessageModal<?php echo $message['message_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Message Details</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">From:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($message['user_name'] ?? 'Unknown User'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Date:</label>
                                <p class="mb-0"><?php echo date('M j, Y \a\t g:i A', strtotime($message['created_at'])); ?></p>
                            </div>
                        </div>
                        <div class="row mb-3">
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Contact Method:</label>
                                <p class="mb-0">
                                    <i class="bi bi-<?php echo $message['contact_method'] === 'email' ? 'envelope' : 'phone'; ?>"></i>
                                    <?php echo htmlspecialchars($message['contact_info']); ?>
                                </p>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-bold">Status:</label>
                                <p class="mb-0">
                                    <span class="status-badge status-<?php echo $message['status']; ?>">
                                        <?php echo ucfirst($message['status']); ?>
                                    </span>
                                </p>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Subject:</label>
                            <p class="mb-0"><?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?></p>
                        </div>
                        <div class="mb-3">
                            <label class="form-label fw-bold">Message:</label>
                            <div class="border p-3 rounded bg-light">
                                <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['content'])); ?></p>
                            </div>
                        </div>
                        <?php if ($message['user_response']): ?>
                            <div class="mb-3">
                                <label class="form-label fw-bold">User Response:</label>
                                <div class="border p-3 rounded bg-success-subtle">
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($message['user_response'])); ?></p>
                                    <small class="text-muted">Responded on <?php echo date('M j, Y \a\t g:i A', strtotime($message['responded_at'])); ?></small>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                            <input type="hidden" name="status" value="read">
                            <input type="hidden" name="update_status" value="1">
                            <button type="submit" class="btn btn-primary">Mark as Read</button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <!-- Email Response Modals -->
    <?php foreach ($all_messages as $message): ?>
        <div class="modal fade" id="responseModal<?php echo $message['message_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Respond to Message</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                            <input type="hidden" name="send_response" value="1">

                            <div class="mb-3">
                                <label class="form-label fw-bold">To:</label>
                                <p class="mb-0"><?php echo htmlspecialchars($message['contact_info'] ?? $message['email']); ?></p>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Subject</label>
                                <input type="text" class="form-control" name="response_subject" value="Re: <?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <textarea class="form-control" name="response_body" rows="8" placeholder="Type your response here..." required></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Send Response</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>