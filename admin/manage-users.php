<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Sync Admin Name from session variable
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Users - BookStack</title>
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
                    <a href="manage-users.php" class="nav-link active"><i class="bi bi-people me-3"></i>Users</a>
                    <a href="manage-orders.php" class="nav-link"><i class="bi bi-cart me-3"></i>Orders</a>
                    <a href="manage-verification.php" class="nav-link"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="manage-reports.php" class="nav-link"><i class="bi bi-bar-chart me-3"></i>Reports</a>

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
                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h5 class="fw-bold mb-0">Manage Users</h5>
                        <p class="text-muted small mb-0">Overview of all system users and account statuses.</p>
                    </div>
                    <button class="btn btn-primary px-4 shadow-sm">
                        <i class="bi bi-person-plus-fill me-2"></i>Add New User
                    </button>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Registered Users</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Total Users Count]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Active Accounts</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Active Users Count]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Pending Verifications</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Pending Count]</h4>
                        </div>
                    </div>
                </div>

                <div class="card data-card p-3 mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control border-start-0" placeholder="Search by name or email...">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select">
                                <option selected>Filter by Role</option>
                                <option>Student</option>
                                <option>Instructor</option>
                            </select>
                        </div>
                        <div class="col-md-3 text-md-end">
                            <button class="btn btn-outline-secondary w-100"><i class="bi bi-filter me-2"></i>Apply Filters</button>
                        </div>
                    </div>
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
                                    <th>Status</th>
                                    <th>Joined Date</th>
                                    <th class="text-end pe-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <div class="placeholder-box py-5">
                                            [Dynamic list of registered users will be displayed here]
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="mt-4 d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Showing [X] to [Y] of [Z] entries</span>
                        <div class="placeholder-box px-3 py-1 small" style="min-width: 150px;">
                            [Pagination]
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>