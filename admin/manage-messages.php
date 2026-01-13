<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../config/db.php';
require_once '../includes/admin-pagination.php';

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
function getMessages($status = null, $limit = null, $offset = null)
{
    global $conn; // Use your mysqli connection

    $query = "SELECT m.*, u.user_name, u.email FROM messages m LEFT JOIN users u ON m.user_id = u.user_id";

    // Exclude verification requests (they appear in manage-verification.php)
    $query .= " WHERE m.subject NOT LIKE '%Account Verification Request%'";

    if ($status) {
        $status_escaped = mysqli_real_escape_string($conn, $status);
        $query .= " AND m.status = '$status_escaped'";
    }

    $query .= " ORDER BY m.created_at DESC";

    if ($limit !== null && $offset !== null) {
        $query .= " LIMIT $limit OFFSET $offset";
    }

    $result = executeQuery($query);
    $messages = [];

    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $messages[] = $row;
        }
    }

    return $messages;
}

// Helper function to count messages
function countMessages($status = null)
{
    global $conn;

    $query = "SELECT COUNT(*) as total FROM messages m WHERE m.subject NOT LIKE '%Account Verification Request%'";

    if ($status) {
        $status_escaped = mysqli_real_escape_string($conn, $status);
        $query .= " AND m.status = '$status_escaped'";
    }

    $result = executeQuery($query);
    if ($result) {
        $row = mysqli_fetch_assoc($result);
        return $row['total'];
    }
    return 0;
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

// Handle email/SMS response (POST-Redirect-GET pattern)
if (isset($_POST['send_response'])) {
    $message_id = (int)$_POST['message_id'];
    $response_body = $_POST['response_body'];

    // Get message details
    $query = "SELECT m.*, u.email FROM messages m LEFT JOIN users u ON m.user_id = u.user_id WHERE m.message_id = $message_id";
    $result = executeQuery($query);

    if ($result && mysqli_num_rows($result) === 1) {
        $message = mysqli_fetch_assoc($result);
        $contact_method = $message['contact_method'];
        $contact_info = $message['contact_info'] ?? $message['email'];

        $sent_successfully = false;

        // Send via email
        if ($contact_method === 'email') {
            $response_subject = mysqli_real_escape_string($conn, $_POST['response_subject']);
            if (sendEmail($contact_info, $response_subject, $response_body)) {
                $sent_successfully = true;
            }
        }
        // Send via SMS
        elseif ($contact_method === 'phone') {
            require_once '../notifications/send-sms.php';
            if (sendSMS($contact_info, $response_body)) {
                $sent_successfully = true;
            }
        }

        if ($sent_successfully) {
            // Update status to resolved after sending response
            updateMessageStatus($message_id, 'resolved');
            $_SESSION['success_message'] = ucfirst($contact_method) . " response sent successfully!";
        } else {
            $_SESSION['error_message'] = "Failed to send " . $contact_method . " response.";
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

// Pagination setup
$pagination = getPaginationParams($_GET['page'] ?? 1, 10);
$page = $pagination['page'];
$offset = $pagination['offset'];
$items_per_page = $pagination['items_per_page'];

// Get total count for pagination
$total_messages_count = countMessages($filter_status);
$total_pages = calculateTotalPages($total_messages_count, $items_per_page);

// Get messages based on filter with pagination
$all_messages = getMessages($filter_status, $items_per_page, $offset);
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

$title = 'Manage Messages';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'messages';
    include '../includes/admin-nav.php'; ?>

    <?php include '../includes/notification.php'; ?>

    <header class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h5 class="fw-bold mb-0">Messages</h5>
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
            <div class="row align-items-center mb-3">
                <div class="col-md-6">
                    <h6 class="fw-bold mb-0 text-md-start text-center">All Conversations</h6>
                </div>
                <div class="col-md-6 text-md-end text-center mt-2 mt-md-0">
                    <div class="btn-group btn-group-sm">
                        <a href="manage-messages.php" class="btn btn-outline-secondary <?= $filter_status === null ? 'active' : ''; ?>">All</a>
                        <a href="manage-messages.php?status=pending" class="btn btn-outline-secondary <?= $filter_status === 'pending' ? 'active' : ''; ?>">Pending</a>
                        <a href="manage-messages.php?status=read" class="btn btn-outline-secondary <?= $filter_status === 'read' ? 'active' : ''; ?>">Read</a>
                        <a href="manage-messages.php?status=resolved" class="btn btn-outline-secondary <?= $filter_status === 'resolved' ? 'active' : ''; ?>">Resolved</a>
                    </div>
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

        <!-- Pagination -->
        <?php
        if ($total_pages > 1) {
            echo '<div class="card-footer">';
            renderAdminPagination(
                $page,
                $total_pages,
                $total_messages_count,
                ['status' => $filter_status]
            );
            echo '</div>';
        }
        ?>
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

    <!-- Response Modals -->
    <?php foreach ($all_messages as $message): ?>
        <div class="modal fade" id="responseModal<?php echo $message['message_id']; ?>" tabindex="-1">
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-<?php echo $message['contact_method'] === 'email' ? 'envelope' : 'phone'; ?> me-2"></i>
                            Respond via <?php echo ucfirst($message['contact_method']); ?>
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="POST">
                        <div class="modal-body">
                            <input type="hidden" name="message_id" value="<?php echo $message['message_id']; ?>">
                            <input type="hidden" name="send_response" value="1">

                            <div class="mb-3">
                                <label class="form-label fw-bold">To:</label>
                                <p class="mb-0">
                                    <i class="bi bi-<?php echo $message['contact_method'] === 'email' ? 'envelope' : 'phone'; ?> me-1"></i>
                                    <?php echo htmlspecialchars($message['contact_info'] ?? $message['email']); ?>
                                </p>
                            </div>

                            <?php if ($message['contact_method'] === 'email'): ?>
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Subject</label>
                                    <input type="text" class="form-control" name="response_subject" value="Re: <?php echo htmlspecialchars($message['subject'] ?? 'No Subject'); ?>" required>
                                </div>
                            <?php endif; ?>

                            <div class="mb-3">
                                <label class="form-label fw-bold">Message</label>
                                <textarea class="form-control" name="response_body" rows="8" placeholder="<?php echo $message['contact_method'] === 'email' ? 'Type your response here...' : 'Type your SMS message here (keep it concise)...'; ?>" required></textarea>
                                <?php if ($message['contact_method'] === 'phone'): ?>
                                    <small class="text-muted">
                                        <i class="bi bi-info-circle me-1"></i>
                                        SMS will be sent to this phone number. Keep your message concise.
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-send me-1"></i>
                                Send <?php echo ucfirst($message['contact_method']); ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <?php include '../includes/admin-footer.php'; ?>