<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Sync Admin Name from session variable
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Database Connection
require_once '../config/db.php';
require_once '../includes/voucher-utils.php';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE: Issue Voucher to User
    if (isset($_POST['issue_voucher'])) {
        $user_id = (int)$_POST['user_id'];
        $external_system = mysqli_real_escape_string($conn, $_POST['external_system']);
        $discount_type = mysqli_real_escape_string($conn, $_POST['discount_type']);
        $discount_amount = (float)$_POST['discount_amount'];
        $min_order_amount = (float)$_POST['min_order_amount'];
        $max_uses = (int)$_POST['max_uses'];
        $expires_days = (int)$_POST['expires_days'];

        $result = createVoucher($conn, $user_id, $external_system, $discount_type, $discount_amount, $expires_days, $min_order_amount, $max_uses);

        if ($result['success']) {
            $_SESSION['success'] = "Voucher issued successfully! Code: " . $result['code'];
        } else {
            $_SESSION['error'] = "Failed to issue voucher";
        }
        header('Location: manage-vouchers.php');
        exit;
    }

    // DELETE: Remove Voucher
    if (isset($_POST['delete_voucher'])) {
        $voucher_id = (int)$_POST['voucher_id'];
        $query = "DELETE FROM vouchers WHERE voucher_id = $voucher_id";
        if (executeQuery($query)) {
            $_SESSION['success'] = "Voucher deleted successfully";
        } else {
            $_SESSION['error'] = "Failed to delete voucher";
        }
        header('Location: manage-vouchers.php');
        exit;
    }

    // BULK: Issue vouchers to multiple users
    if (isset($_POST['bulk_issue_vouchers'])) {
        $selected_users = $_POST['selected_users'] ?? [];
        $external_system = mysqli_real_escape_string($conn, $_POST['external_system']);
        $discount_type = mysqli_real_escape_string($conn, $_POST['discount_type']);
        $discount_amount = (float)$_POST['discount_amount'];
        $min_order_amount = (float)$_POST['min_order_amount'];
        $max_uses = (int)$_POST['max_uses'];
        $expires_days = (int)$_POST['expires_days'];

        $voucher_count = 0;
        $failed_count = 0;

        foreach ($selected_users as $user_id) {
            $user_id = (int)$user_id;
            // Issue voucher with the specified parameters
            $result = createVoucher($conn, $user_id, $external_system, $discount_type, $discount_amount, $expires_days, $min_order_amount, $max_uses);
            if ($result['success']) {
                $voucher_count++;
            } else {
                $failed_count++;
            }
        }

        if ($voucher_count > 0) {
            $system_name = ($external_system === 'ebook_store') ? 'BookStack' : 'Travel Agency';
            $discount_text = ($discount_type === 'percentage') ? "{$discount_amount}%" : "₱{$discount_amount}";
            $_SESSION['success'] = "Successfully issued {$voucher_count} {$system_name} vouchers ({$discount_text} discount) to " . count($selected_users) . " user(s).";
            if ($failed_count > 0) {
                $_SESSION['success'] .= " {$failed_count} failed.";
            }
        } else {
            $_SESSION['error'] = "No vouchers were issued. Please check the parameters.";
        }
        header('Location: manage-vouchers.php');
        exit;
    }
}

// Get messages from session and clear them
$success = $_SESSION['success'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['success'], $_SESSION['error']);

// Fetch all vouchers with user information
$vouchersQuery = "SELECT v.*, u.user_name, u.email 
                  FROM vouchers v 
                  LEFT JOIN users u ON v.user_id = u.user_id 
                  ORDER BY v.issued_at DESC";
$vouchersResult = executeQuery($vouchersQuery);

// Fetch all users for dropdown (include all users except admins)
$usersQuery = "SELECT user_id, user_name, email FROM users ORDER BY user_name ASC";
$usersResult = executeQuery($usersQuery);
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Vouchers - BookStack Admin</title>

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <?php $currentPage = 'vouchers';
    include '../includes/admin-nav.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Manage Vouchers</h5>
            <p class="text-muted small mb-0">Issue discounts, manage promotional codes, and monitor voucher usage.</p>
        </div>
        <button type="button" class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#issueVoucherModal" style="background-color: #00a3ff; border: none; border-radius: 10px;">
            <i class="bi bi-plus-lg me-2"></i>Issue New Voucher
        </button>
    </div>

    <?php if ($success): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars($success) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bi bi-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <!-- Vouchers Table -->
    <div class="card shadow-sm">
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code</th>
                            <th>User</th>
                            <th>Source</th>
                            <th>Discount</th>
                            <th>Min Order</th>
                            <th>Uses</th>
                            <th>Expires</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (mysqli_num_rows($vouchersResult) > 0): ?>
                            <?php while ($voucher = mysqli_fetch_assoc($vouchersResult)):
                                $is_expired = strtotime($voucher['expires_at']) <= time();
                                $is_fully_used = $voucher['times_used'] >= $voucher['max_uses'];
                                $is_active = !$is_expired && !$is_fully_used;
                            ?>
                                <tr>
                                    <td><?= $voucher['voucher_id'] ?></td>
                                    <td>
                                        <code class="text-primary fw-bold"><?= htmlspecialchars($voucher['code']) ?></code>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($voucher['user_name']) ?></div>
                                        <small class="text-muted"><?= htmlspecialchars($voucher['email']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge <?= $voucher['external_system'] === 'travel_agency' ? 'bg-info' : 'bg-primary' ?>">
                                            <?= $voucher['external_system'] === 'travel_agency' ? 'Travel Agency' : 'BookStack' ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($voucher['discount_type'] === 'percentage'): ?>
                                            <strong><?= number_format($voucher['discount_amount'], 0) ?>%</strong>
                                        <?php else: ?>
                                            <strong>₱<?= number_format($voucher['discount_amount'], 2) ?></strong>
                                        <?php endif; ?>
                                    </td>
                                    <td>₱<?= number_format($voucher['min_order_amount'], 2) ?></td>
                                    <td><?= $voucher['times_used'] ?>/<?= $voucher['max_uses'] ?></td>
                                    <td><?= date('M d, Y', strtotime($voucher['expires_at'])) ?></td>
                                    <td>
                                        <?php if ($is_active): ?>
                                            <span class="badge bg-success">Active</span>
                                        <?php elseif ($is_expired): ?>
                                            <span class="badge bg-danger">Expired</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Used</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <form method="POST" class="d-inline" onsubmit="return confirm('Delete this voucher?')">
                                            <input type="hidden" name="voucher_id" value="<?= $voucher['voucher_id'] ?>">
                                            <button type="submit" name="delete_voucher" class="btn btn-sm btn-outline-danger">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">No vouchers found</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Issue Voucher Modal -->
    <div class="modal fade" id="issueVoucherModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%); border: none;">
                    <div class="text-white">
                        <h5 class="modal-title mb-0"><i class="bi bi-ticket-perforated me-2"></i>Issue New Voucher</h5>
                        <small class="opacity-90">Use commands to select single or multiple users</small>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="issueVoucherForm">
                    <div class="modal-body">
                        <!-- Command-Based User Selection -->
                        <div class="mb-4 p-3 rounded" style="background: #f8f9fa; border: 2px solid #e9ecef;">
                            <label class="form-label fw-semibold text-muted small mb-2">
                                <i class="bi bi-command me-1"></i>SELECT USERS - COMMAND INPUT
                            </label>
                            <div class="input-group mb-2">
                                <span class="input-group-text bg-white" style="border-color: #e9ecef;">
                                    <i class="bi bi-chevron-right text-success"></i>
                                </span>
                                <input type="text" id="userCommand" class="form-control"
                                    placeholder="Type command: * | John | John, Jane | #3 | #1-15"
                                    style="border-color: #e9ecef;">
                                <button type="button" id="searchUsersBtn" class="btn btn-outline-success">
                                    <i class="bi bi-search me-1"></i>Search
                                </button>
                            </div>
                            <div class="d-flex flex-wrap gap-2 align-items-center">
                                <small class="text-muted fw-semibold">Commands:</small>
                                <span class="badge bg-light text-dark border"><code class="text-success">*</code> All</span>
                                <span class="badge bg-light text-dark border"><code class="text-success">John</code> Name</span>
                                <span class="badge bg-light text-dark border"><code class="text-success">Name, Name</code> Multiple</span>
                                <span class="badge bg-light text-dark border"><code class="text-success">#3</code> Single ID</span>
                                <span class="badge bg-light text-dark border"><code class="text-success">#1-15</code> ID Range</span>
                            </div>

                            <!-- Selected Users Display -->
                            <div id="selectedUsersDisplay" class="mt-3" style="display: none;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <strong class="text-success">
                                        <i class="bi bi-check-circle-fill me-1"></i>
                                        Selected: <span id="selectedCount">0</span> user(s)
                                    </strong>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="clearSelection">
                                        <i class="bi bi-x-circle me-1"></i>Clear
                                    </button>
                                </div>
                                <div id="selectedUsersList" class="d-flex flex-wrap gap-2"></div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label"><i class="bi bi-tag-fill me-1"></i>Voucher Type *</label>
                            <select name="external_system" class="form-select" required>
                                <option value="ebook_store">📚 BookStack (E-Book Store)</option>
                                <option value="travel_agency">✈️ Travel Agency</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Type *</label>
                                <select name="discount_type" class="form-select" required>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount (₱)</option>
                                </select>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Amount *</label>
                                <input type="number" name="discount_amount" class="form-control" step="0.01" min="0.01" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Min Order Amount</label>
                                <input type="number" name="min_order_amount" class="form-control" step="0.01" value="0" min="0">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Max Uses *</label>
                                <input type="number" name="max_uses" class="form-control" value="1" min="1" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Expires In (Days) *</label>
                            <input type="number" name="expires_days" class="form-control" value="3" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="bulk_issue_vouchers" class="btn btn-success" id="submitBtn" disabled>
                            <i class="bi bi-ticket-perforated me-2"></i>Issue Vouchers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    </main>
    </div>

    <!-- Bulk Issue Vouchers Modal -->
    <div class="modal fade" id="bulkVoucherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-ticket-perforated me-2"></i>Issue Vouchers to Selected Users</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" id="bulkVoucherForm">
                    <div class="modal-body">
                        <div id="selectedUsersList" class="mb-3">
                            <!-- Will be populated by JavaScript -->
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="bulk_issue_vouchers" class="btn btn-success">
                            <i class="bi bi-ticket-perforated me-2"></i>Issue Vouchers
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Fetch all users from the database for command matching
        const allUsers = <?php
                            mysqli_data_seek($usersResult, 0);
                            $users = [];
                            while ($user = mysqli_fetch_assoc($usersResult)) {
                                $users[] = [
                                    'id' => $user['user_id'],
                                    'name' => $user['user_name']
                                ];
                            }
                            echo json_encode($users);
                            ?>;

        let selectedUsers = [];
        const userCommand = document.getElementById('userCommand');
        const searchUsersBtn = document.getElementById('searchUsersBtn');
        const selectedCountSpan = document.getElementById('selectedCount');
        const selectedUsersDisplay = document.getElementById('selectedUsersDisplay');
        const selectedUsersList = document.getElementById('selectedUsersList');
        const clearSelectionBtn = document.getElementById('clearSelection');
        const submitBtn = document.getElementById('submitBtn');
        const issueVoucherForm = document.getElementById('issueVoucherForm');

        // Parse command and find matching users
        function parseCommand(command) {
            command = command.trim();

            // Command: * (all users)
            if (command === '*') {
                return allUsers;
            }

            // Command: Single ID with # (e.g., #3)
            const singleIdMatch = command.match(/^#(\d+)$/);
            if (singleIdMatch) {
                const id = parseInt(singleIdMatch[1]);
                return allUsers.filter(user => parseInt(user.id) === id);
            }

            // Command: ID range with # (e.g., #1-15)
            const rangeMatch = command.match(/^#(\d+)-(\d+)$/);
            if (rangeMatch) {
                const start = parseInt(rangeMatch[1]);
                const end = parseInt(rangeMatch[2]);
                return allUsers.filter(user => {
                    const id = parseInt(user.id);
                    return id >= start && id <= end;
                });
            }

            // Command: Multiple names (e.g., "John Doe, Bill Gates")
            if (command.includes(',')) {
                const names = command.split(',').map(n => n.trim().toLowerCase());
                return allUsers.filter(user =>
                    names.some(name => user.name.toLowerCase().includes(name))
                );
            }

            // Command: Single name search
            if (command.length > 0) {
                const searchTerm = command.toLowerCase();
                return allUsers.filter(user =>
                    user.name.toLowerCase().includes(searchTerm)
                );
            }

            return [];
        }

        // Update selected users display
        function updateSelectedUsersDisplay() {
            if (selectedUsers.length === 0) {
                selectedUsersDisplay.style.display = 'none';
                submitBtn.disabled = true;
                return;
            }

            // Display selected users
            let html = '';
            selectedUsers.forEach(user => {
                html += `<span class="badge" style="background: linear-gradient(135deg, #2ecc71 0%, #27ae60 100%);
                                font-size: 0.875rem; padding: 0.5rem 0.75rem; font-weight: 500;">
                            <i class="bi bi-person-check-fill me-1"></i>${user.name}
                            <span class="opacity-75 ms-1">#${user.id}</span>
                         </span>`;
            });

            selectedUsersList.innerHTML = html;
            selectedCountSpan.textContent = selectedUsers.length;
            selectedUsersDisplay.style.display = 'block';
            submitBtn.disabled = false;
        }

        // Search button click
        searchUsersBtn.addEventListener('click', function() {
            const command = userCommand.value;
            const matchedUsers = parseCommand(command);

            if (matchedUsers.length === 0) {
                // Show error feedback
                userCommand.classList.add('is-invalid');
                setTimeout(() => userCommand.classList.remove('is-invalid'), 2000);

                alert('❌ No users matched your command.\n\nTry:\n• * (all users)\n• John (name search)\n• 1-10 (ID range)\n• John, Jane (multiple)');
                return;
            }

            selectedUsers = matchedUsers;
            updateSelectedUsersDisplay();
        });

        // Clear selection
        clearSelectionBtn.addEventListener('click', function() {
            selectedUsers = [];
            userCommand.value = '';
            updateSelectedUsersDisplay();
        });

        // Enter key to search
        userCommand.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                searchUsersBtn.click();
            }
        });

        // On form submit, add selected user IDs as hidden inputs
        issueVoucherForm.addEventListener('submit', function(e) {
            // Remove any existing hidden user inputs
            const existingInputs = issueVoucherForm.querySelectorAll('input[name="selected_users[]"]');
            existingInputs.forEach(input => input.remove());

            // Add hidden inputs for selected users
            selectedUsers.forEach(user => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'selected_users[]';
                input.value = user.id;
                issueVoucherForm.appendChild(input);
            });
        });

        // Reset form when modal closes
        document.getElementById('issueVoucherModal').addEventListener('hidden.bs.modal', function() {
            selectedUsers = [];
            userCommand.value = '';
            updateSelectedUsersDisplay();
            issueVoucherForm.reset();
        });
    </script>
</body>

</html>