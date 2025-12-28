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
    <title>Admin Dashboard - BookStack</title>
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

        .stat-card,
        .data-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: #fff;
        }

        .btn-green {
            background-color: var(--brand-green) !important;
            color: white !important;
            border-radius: 8px;
        }

        .table thead th {
            background-color: #f8f9fa;
            color: #64748b;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            border: none;
        }

        .placeholder-box {
            border: 2px dashed #e5e7eb;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-style: italic;
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
                    <a href="dashboard.php" class="nav-link active"><i class="bi bi-grid-fill me-3"></i>Dashboard</a>
                    <a href="manage-ebooks.php" class="nav-link"><i class="bi bi-journal-text me-3"></i>E-Books</a>
                    <a href="manage-categories.php" class="nav-link"><i class="bi bi-layers me-3"></i>Categories</a>
                    <a href="manage-users.php" class="nav-link"><i class="bi bi-people me-3"></i>Users</a>
                    <a href="manage-orders.php" class="nav-link"><i class="bi bi-cart me-3"></i>Orders</a>
                    <a href="manage-verification.php" class="nav-link"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="reports.php" class="nav-link"><i class="bi bi-bar-chart me-3"></i>Reports</a>

                    <hr class="mx-4 my-2 text-muted opacity-25">

                    <a href="#" class="nav-link"><i class="bi bi-gear me-3"></i>Settings</a>
                    <a href="logout.php" class="nav-link text-danger"><i class="bi bi-box-arrow-left me-3"></i>Logout</a>

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
                    <h5 class="fw-bold mb-0">Dashboard Overview</h5>
                    <div class="d-flex align-items-center gap-3">
                        <div class="input-group bg-white rounded-3 shadow-sm d-none d-sm-flex" style="width: 250px;">
                            <span class="input-group-text bg-transparent border-0"><i class="bi bi-search text-muted"></i></span>
                            <input type="text" class="form-control border-0 bg-transparent ps-0" placeholder="Search anything...">
                        </div>
                        <button class="btn btn-green px-3 fw-semibold">+ Add E-Book</button>
                    </div>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Users</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Total Users Count]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Sales</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Total Sales Amount]</h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total E-Books</p>
                            <h4 class="fw-bold mb-0 text-secondary">[Total Books Count]</h4>
                        </div>
                    </div>
                </div>

                <div class="card data-card p-4 mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <h6 class="fw-bold mb-1">Sales Overview</h6>
                            <p class="text-muted small mb-0">Revenue analytics visualization</p>
                        </div>
                    </div>
                    <div class="placeholder-box" style="height: 250px;">
                        [Sales Revenue Chart will be rendered here]
                    </div>
                </div>

                <div class="card data-card p-4">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0">Recent Orders</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Student</th>
                                    <th>E-Book Title</th>
                                    <th>Date</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="6" class="text-center py-5 text-muted">
                                        <div class="placeholder-box py-3">
                                            [Dynamic list of recent transactions will be displayed here]
                                        </div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>