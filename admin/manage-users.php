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
require_once '../includes/admin-pagination.php';

// Handle CRUD Operations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CREATE: Add New User
    if (isset($_POST['create_user'])) {
        $user_name = mysqli_real_escape_string($conn, trim($_POST['user_name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $password = $_POST['password'];
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email already exists
            $checkQuery = "SELECT user_id FROM users WHERE email = '$email'";
            $checkResult = executeQuery($checkQuery);

            if (mysqli_num_rows($checkResult) > 0) {
                $error = "Email already exists";
            } else {
                // Hash password
                $password_hash = password_hash($password, PASSWORD_DEFAULT);

                $query = "INSERT INTO users (user_name, email, password_hash, role, created_at) VALUES ('$user_name', '$email', '$password_hash', '$role', NOW())";
                if (executeQuery($query)) {
                    $success = "User created successfully";
                } else {
                    $error = "Failed to create user";
                }
            }
        }
    }

    // UPDATE: Edit User
    if (isset($_POST['update_user'])) {
        $user_id = (int)$_POST['user_id'];
        $user_name = mysqli_real_escape_string($conn, trim($_POST['user_name']));
        $email = mysqli_real_escape_string($conn, trim($_POST['email']));
        $role = mysqli_real_escape_string($conn, $_POST['role']);

        // Validate email
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Invalid email format";
        } else {
            // Check if email exists for another user
            $checkQuery = "SELECT user_id FROM users WHERE email = '$email' AND user_id != $user_id";
            $checkResult = executeQuery($checkQuery);

            if (mysqli_num_rows($checkResult) > 0) {
                $error = "Email already exists";
            } else {
                $query = "UPDATE users SET user_name = '$user_name', email = '$email', role = '$role' WHERE user_id = $user_id";
                if (executeQuery($query)) {
                    $success = "User updated successfully";
                } else {
                    $error = "Failed to update user";
                }
            }
        }
    }

    // DELETE: Remove User
    if (isset($_POST['delete_user'])) {
        $user_id = (int)$_POST['user_id'];
        $query = "DELETE FROM users WHERE user_id = $user_id";
        if (executeQuery($query)) {
            $success = "User deleted successfully";
        } else {
            $error = "Failed to delete user";
        }
    }
}

// Pagination setup
$pagination = getPaginationParams($_GET['page'] ?? 1, 10);
$page = $pagination['page'];
$offset = $pagination['offset'];
$items_per_page = $pagination['items_per_page'];

// Fetch users with search and filter
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filterRole = isset($_GET['role']) ? mysqli_real_escape_string($conn, trim($_GET['role'])) : '';

// Count total users for pagination
$countQuery = "SELECT COUNT(*) as total FROM users WHERE 1=1";
if (!empty($searchTerm)) {
    $countQuery .= " AND (user_name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%')";
}
if (!empty($filterRole)) {
    $countQuery .= " AND role = '$filterRole'";
}
$countResult = executeQuery($countQuery);
$total_users_count = mysqli_fetch_assoc($countResult)['total'];
$total_pages = calculateTotalPages($total_users_count, $items_per_page);

$usersQuery = "SELECT * FROM users WHERE 1=1";

if (!empty($searchTerm)) {
    $usersQuery .= " AND (user_name LIKE '%$searchTerm%' OR email LIKE '%$searchTerm%')";
}

if (!empty($filterRole)) {
    $usersQuery .= " AND role = '$filterRole'";
}

$usersQuery .= " ORDER BY created_at DESC LIMIT $items_per_page OFFSET $offset";
$usersResult = executeQuery($usersQuery);
$users = [];
while ($row = mysqli_fetch_assoc($usersResult)) {
    $users[] = $row;
}

// Statistics
$totalUsersQuery = "SELECT COUNT(*) as total FROM users";
$totalUsersResult = executeQuery($totalUsersQuery);
$totalUsers = mysqli_fetch_assoc($totalUsersResult)['total'];

$totalVerifiedQuery = "SELECT COUNT(*) as total FROM users WHERE is_account_verified = 1";
$totalVerifiedResult = executeQuery($totalVerifiedQuery);
$totalVerified = mysqli_fetch_assoc($totalVerifiedResult)['total'];

$title = 'Manage Users';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'users';
    include '../includes/admin-nav.php'; ?>
    <?php
    $success_message = isset($success) ? $success : '';
    $error_message = isset($error) ? $error : '';
    include '../includes/notification.php';
    ?>

    <div class="mb-4">
        <h5 class="fw-bold mb-0">Manage Users</h5>
        <p class="text-muted small mb-0">Overview of all system users and account statuses.</p>
    </div>

    <div class="row g-3 mb-4 text-center">
        <div class="col-12 col-md-6">
            <div class="card stat-card p-4">
                <p class="text-muted small mb-1">Total Registered Users</p>
                <h4 class="fw-bold mb-0 text-secondary"><?php echo $totalUsers; ?></h4>
            </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="card stat-card p-4">
                <p class="text-muted small mb-1">Verified Accounts</p>
                <h4 class="fw-bold mb-0 text-secondary"><?php echo $totalVerified; ?></h4>
            </div>
        </div>
    </div>

    <div class="card data-card p-3 mb-4">
        <form method="GET" action="">
            <div class="row g-3 align-items-end">
                <div class="col-md-6">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                        <input type="text" name="search" class="form-control border-start-0" placeholder="Search users..." value="<?php echo htmlspecialchars($searchTerm); ?>">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="d-flex gap-2 align-items-center">
                        <select name="role" class="form-select">
                            <option value="">All Roles</option>
                            <option value="user" <?php echo $filterRole === 'user' ? 'selected' : ''; ?>>User</option>
                            <option value="admin" <?php echo $filterRole === 'admin' ? 'selected' : ''; ?>>Admin</option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary" title="Filter"><i class="bi bi-funnel-fill"></i></button>
                        <?php if (!empty($searchTerm) || !empty($filterRole)): ?>
                            <a href="manage-users.php" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>

    <div class="card data-card p-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h6 class="fw-bold mb-0">User Directory</h6>
        </div>
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr>
                        <th>User</th>
                        <th>Role</th>
                        <th>Verification</th>
                        <th>Joined Date</th>
                        <th class="text-end pe-3">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($users) > 0): ?>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($user['user_name']); ?>&background=random" class="rounded-circle me-2" width="40" height="40">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($user['user_name']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($user['email']); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-<?php echo $user['role'] === 'admin' ? 'danger' : 'primary'; ?>">
                                        <?php echo ucfirst(htmlspecialchars($user['role'])); ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (isset($user['is_account_verified']) && $user['is_account_verified'] == 1): ?>
                                        <span class="badge bg-success">Verified</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning text-dark">Not Verified</span>
                                    <?php endif; ?>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($user['created_at'])); ?></td>
                                <td class="text-end">
                                    <button class="btn btn-sm btn-outline-primary mb-1 mb-xl-0" onclick='editUser(<?php echo json_encode($user); ?>)' title="Edit">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <button class="btn btn-sm btn-outline-danger" onclick="deleteUser(<?php echo $user['user_id']; ?>, '<?php echo htmlspecialchars($user['user_name'], ENT_QUOTES); ?>')" title="Delete">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="5" class="text-center py-5 text-muted">
                                <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                <p class="mt-2">No users found</p>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <?php
        renderAdminPagination(
            $page,
            $total_pages,
            $total_users_count,
            [
                'search' => $searchTerm,
                'role' => $filterRole
            ]
        );
        ?>
    </div>
    </main>
    </div>
    </div>

    <!-- Create/Edit User Modal -->
    <div class="modal fade" id="userModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="userModalTitle">Add New User</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="" id="userForm">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="user_id">

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Username <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="user_name" id="user_name" placeholder="Enter username" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Email <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" name="email" id="email" placeholder="user@example.com" required>
                        </div>

                        <div class="mb-3" id="passwordField">
                            <label class="form-label fw-bold small">Password <span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" id="password" placeholder="Min. 6 characters">
                            <small class="text-muted">Minimum 6 characters</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small">Role <span class="text-danger">*</span></label>
                            <select class="form-select" name="role" id="role" required>
                                <option value="user">User</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary" name="create_user" id="submitBtn">Create User</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="user_id" id="delete_user_id">
                        <p>Are you sure you want to delete user <strong id="delete_user_name"></strong>?</p>
                        <p class="text-danger small">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-danger" name="delete_user">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    <script>
        function resetForm() {
            document.getElementById('userForm').reset();
            document.getElementById('user_id').value = '';
            document.getElementById('userModalTitle').textContent = 'Add New User';
            document.getElementById('password').required = true;
            document.getElementById('passwordField').style.display = 'block';
            document.getElementById('submitBtn').name = 'create_user';
            document.getElementById('submitBtn').textContent = 'Create User';
        }

        function editUser(user) {
            document.getElementById('user_id').value = user.user_id;
            document.getElementById('user_name').value = user.user_name;
            document.getElementById('email').value = user.email;
            document.getElementById('role').value = user.role;
            document.getElementById('userModalTitle').textContent = 'Edit User';
            document.getElementById('password').required = false;
            document.getElementById('passwordField').style.display = 'none';
            document.getElementById('submitBtn').name = 'update_user';
            document.getElementById('submitBtn').textContent = 'Update User';

            // Make username and email readonly if role is user
            if (user.role === 'user') {
                document.getElementById('user_name').readOnly = true;
                document.getElementById('email').readOnly = true;
            } else {
                document.getElementById('user_name').readOnly = false;
                document.getElementById('email').readOnly = false;
            }

            const modal = new bootstrap.Modal(document.getElementById('userModal'));
            modal.show();
        }

        function deleteUser(userId, userName) {
            document.getElementById('delete_user_id').value = userId;
            document.getElementById('delete_user_name').textContent = userName;

            const modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>

    <?php include '../includes/admin-footer.php'; ?>
</body>

</html>