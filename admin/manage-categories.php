<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Sync Admin Name from session variable established in login.php
$adminName = $_SESSION['admin_name'] ?? 'Admin';
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories - BookStack</title>
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
                    <a href="manage-categories.php" class="nav-link active"><i class="bi bi-layers me-3"></i>Categories</a>
                    <a href="manage-users.php" class="nav-link"><i class="bi bi-people me-3"></i>Users</a>
                    <a href="manage-orders.php" class="nav-link"><i class="bi bi-cart me-3"></i>Orders</a>
                    <a href="manage-verification.php" class="nav-link"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="reports.php" class="nav-link"><i class="bi bi-bar-chart me-3"></i>Reports</a>

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
                        <h5 class="fw-bold mb-1">Manage Categories</h5>
                        <p class="text-muted small mb-0">Organize e-book genres, edit details, or add new classifications.</p>
                    </div>
                    <button class="btn btn-primary px-4 shadow-sm" style="background-color: #00a3ff; border: none; border-radius: 10px;">
                        <i class="bi bi-plus-lg me-2"></i>Add Category
                    </button>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Categories</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Total Categories Count]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Active Genres</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Active Genres Count]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Hidden Categories</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Hidden Categories Count]</h4>
                        </div>
                    </div>
                </div>

                <div class="card data-card border-0 shadow-sm">
                    <div class="p-3 border-bottom">
                        <div class="d-flex flex-wrap justify-content-between align-items-center gap-3">
                            <div class="input-group" style="max-width: 350px;">
                                <span class="input-group-text bg-light border-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" class="form-control bg-light border-0" placeholder="Search categories...">
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-light border btn-sm text-muted px-3"><i class="bi bi-funnel me-2"></i>Status</button>
                                <button class="btn btn-light border btn-sm text-muted px-3"><i class="bi bi-sort-down me-2"></i>Sort</button>
                            </div>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr style="font-size: 0.75rem; color: #64748b; text-transform: uppercase;">
                                    <th class="ps-4" style="width: 40px;"><input type="checkbox" class="form-check-input"></th>
                                    <th>Category Name</th>
                                    <th>Description</th>
                                    <th>Books</th>
                                    <th>Status</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td class="ps-4"><input type="checkbox" class="form-check-input"></td>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <div class="p-2 bg-light rounded-3 me-3 text-center" style="width: 35px;">
                                                <i class="bi bi-tag text-primary"></i>
                                            </div>
                                            <span class="fw-bold text-dark">[Category Name]</span>
                                        </div>
                                    </td>
                                    <td class="text-muted small">[Brief description of category...]</td>
                                    <td><span class="badge bg-light text-dark rounded-pill px-3">[Count]</span></td>
                                    <td><span class="badge bg-success-subtle text-success px-3 rounded-pill">Active</span></td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm text-muted"><i class="bi bi-three-dots"></i></button>
                                    </td>
                                </tr>
                                <tr>
                                    <td colspan="6" class="text-center py-5">
                                        <div class="placeholder-box py-4 mx-4" style="border: 2px dashed #e5e7eb; border-radius: 8px; color: #9ca3af;">
                                            [Dynamic list of categories will be displayed here]
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="p-3 border-top d-flex justify-content-between align-items-center">
                        <span class="text-muted small">Showing [X]-[Y] of [Total] categories</span>
                        <div class="btn-group">
                            <button class="btn btn-sm btn-outline-secondary px-3">Previous</button>
                            <button class="btn btn-sm btn-outline-secondary px-3">Next</button>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>