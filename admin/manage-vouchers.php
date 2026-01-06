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

$success = '';
$error = '';

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
            $success = "Voucher issued successfully! Code: " . $result['code'];
        } else {
            $error = "Failed to issue voucher";
        }
    }

    // DELETE: Remove Voucher
    if (isset($_POST['delete_voucher'])) {
        $voucher_id = (int)$_POST['voucher_id'];
        $query = "DELETE FROM vouchers WHERE voucher_id = $voucher_id";
        if (executeQuery($query)) {
            $success = "Voucher deleted successfully";
        } else {
            $error = "Failed to delete voucher";
        }
    }
}

// Fetch all vouchers with user information
$vouchersQuery = "SELECT v.*, u.user_name, u.email 
                  FROM vouchers v 
                  LEFT JOIN users u ON v.user_id = u.user_id 
                  ORDER BY v.issued_at DESC";
$vouchersResult = executeQuery($vouchersQuery);

// Fetch all users for dropdown
$usersQuery = "SELECT user_id, user_name, email FROM users WHERE role = 'user' ORDER BY user_name ASC";
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
    <?php include 'includes/admin-nav.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include 'includes/admin-sidebar.php'; ?>

            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4 mt-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pb-2 mb-3 border-bottom">
                    <h1 class="h2"><i class="bi bi-ticket-perforated me-2"></i>Manage Vouchers</h1>
                    <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#issueVoucherModal">
                        <i class="bi bi-plus-circle me-2"></i>Issue New Voucher
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
                                                        <strong>$<?= number_format($voucher['discount_amount'], 2) ?></strong>
                                                    <?php endif; ?>
                                                </td>
                                                <td>$<?= number_format($voucher['min_order_amount'], 2) ?></td>
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
            </main>
        </div>
    </div>

    <!-- Issue Voucher Modal -->
    <div class="modal fade" id="issueVoucherModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bi bi-ticket-perforated me-2"></i>Issue New Voucher</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST">
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">User *</label>
                            <select name="user_id" class="form-select" required>
                                <option value="">Select user...</option>
                                <?php
                                mysqli_data_seek($usersResult, 0);
                                while ($user = mysqli_fetch_assoc($usersResult)):
                                ?>
                                    <option value="<?= $user['user_id'] ?>">
                                        <?= htmlspecialchars($user['user_name']) ?> (<?= htmlspecialchars($user['email']) ?>)
                                    </option>
                                <?php endwhile; ?>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Source *</label>
                            <select name="external_system" class="form-select" required>
                                <option value="ebook_store">BookStack</option>
                                <option value="travel_agency">Travel Agency</option>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Discount Type *</label>
                                <select name="discount_type" class="form-select" required>
                                    <option value="percentage">Percentage (%)</option>
                                    <option value="fixed">Fixed Amount ($)</option>
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
                            <input type="number" name="expires_days" class="form-control" value="30" min="1" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="issue_voucher" class="btn btn-primary">Issue Voucher</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>