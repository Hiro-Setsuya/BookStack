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
    <title>Manage Reports - BookStack</title>
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

        /* Report Specific UI */
        .report-card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
            background: #fff;
            padding: 1.5rem;
        }

        .stat-icon {
            width: 40px;
            height: 40px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .placeholder-box {
            border: 2px dashed #e5e7eb;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #9ca3af;
            font-style: italic;
            text-align: center;
            padding: 20px;
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
                    <a href="manage-reports.php" class="nav-link active"><i class="bi bi-bar-chart me-3"></i>Reports</a>

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
                        <h3 class="fw-bold mb-0">Sales Reports</h3>
                        <p class="text-muted small">Overview of platform performance and book sales.</p>
                    </div>
                    <div class="d-flex gap-2">
                        <button class="btn btn-light border btn-sm"><i class="bi bi-calendar3 me-2"></i>Select Date</button>
                        <button class="btn btn-primary btn-sm px-3"><i class="bi bi-download me-2"></i>Export Report</button>
                    </div>
                </header>

                <div class="mb-4">
                    <div class="btn-group btn-group-sm">
                        <button class="btn btn-light border px-3">All Time</button>
                        <button class="btn btn-light border px-3">This Year</button>
                        <button class="btn btn-light border px-3">Last Quarter</button>
                        <button class="btn btn-primary px-3">This Month</button>
                        <button class="btn btn-light border px-3">Last 7 Days</button>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="report-card d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Total Revenue</p>
                                <h4 class="fw-bold mb-0 text-secondary">[Revenue Amount]</h4>
                            </div>
                            <div class="stat-icon bg-success-subtle text-success"><i class="bi bi-cash-stack"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-card d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Books Sold</p>
                                <h4 class="fw-bold mb-0 text-secondary">[Total Sold]</h4>
                            </div>
                            <div class="stat-icon bg-primary-subtle text-primary"><i class="bi bi-bag-check"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-card d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">New Students</p>
                                <h4 class="fw-bold mb-0 text-secondary">[Student Count]</h4>
                            </div>
                            <div class="stat-icon bg-info-subtle text-info"><i class="bi bi-person-plus"></i></div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="report-card d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted small mb-1">Avg. Order Value</p>
                                <h4 class="fw-bold mb-0 text-secondary">[Average Value]</h4>
                            </div>
                            <div class="stat-icon bg-warning-subtle text-warning"><i class="bi bi-bar-chart"></i></div>
                        </div>
                    </div>
                </div>

                <div class="row g-4 mb-4">
                    <div class="col-lg-8">
                        <div class="report-card">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h6 class="fw-bold mb-0">Revenue Analytics</h6>
                            </div>
                            <div class="placeholder-box" style="height: 300px;">
                                [Revenue Trends Line Chart will be rendered here]
                            </div>
                        </div>
                    </div>
                    <div class="col-lg-4">
                        <div class="report-card">
                            <h6 class="fw-bold mb-4">Sales by Category</h6>
                            <div class="placeholder-box" style="height: 200px;">
                                [Category Distribution Chart]
                            </div>
                            <div class="mt-4 text-center text-muted small">
                                [Category Breakdown Legend]
                            </div>
                        </div>
                    </div>
                </div>

                <div class="report-card">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h6 class="fw-bold mb-0">Top Selling Books</h6>
                    </div>
                    <div class="table-responsive">
                        <table class="table align-middle">
                            <thead class="text-muted small text-uppercase">
                                <tr>
                                    <th>Book Title</th>
                                    <th>Category</th>
                                    <th>Price</th>
                                    <th>Sold</th>
                                    <th>Revenue</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td colspan="5" class="text-center py-5">
                                        <div class="placeholder-box">
                                            [List of top performing e-books will be displayed here]
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