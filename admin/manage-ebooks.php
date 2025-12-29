<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage EBooks - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
</head>

<body>
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
        <title>Manage EBooks - BookStack</title>
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
                        <a href="manage-ebooks.php" class="nav-link active"><i class="bi bi-journal-text me-3"></i>E-Books</a>
                        <a href="manage-categories.php" class="nav-link"><i class="bi bi-layers me-3"></i>Categories</a>
                        <a href="manage-users.php" class="nav-link"><i class="bi bi-people me-3"></i>Users</a>
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
                    <div class="d-flex justify-content-between align-items-start mb-4">
                        <div>
                            <h3 class="fw-bold mb-1">Manage E-Books</h3>
                            <p class="text-muted small">Manage your digital library inventory, upload new files, and update pricing.</p>
                        </div>
                        <button class="btn btn-outline-primary btn-sm"><i class="bi bi-download me-2"></i>Export CSV</button>
                    </div>

                    <div class="row g-4">
                        <div class="col-12 col-xl-8">
                            <div class="card data-card p-4 mb-4">
                                <div class="row g-3 mb-4">
                                    <div class="col-md-6">
                                        <label class="form-label small fw-bold">Search books</label>
                                        <div class="input-group input-group-sm">
                                            <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                            <input type="text" class="form-control border-start-0" placeholder="Title, author, or ISBN">
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Category</label>
                                        <select class="form-select form-select-sm">
                                            <option>All Categories</option>
                                        </select>
                                    </div>
                                    <div class="col-md-3">
                                        <label class="form-label small fw-bold">Status</label>
                                        <select class="form-select form-select-sm">
                                            <option>All Status</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="table-responsive">
                                    <table class="table align-middle border-top">
                                        <thead>
                                            <tr>
                                                <th style="width: 80px;">COVER</th>
                                                <th>BOOK DETAILS</th>
                                                <th>PRICE</th>
                                                <th>CATEGORY</th>
                                                <th>STATUS</th>
                                                <th class="text-end">ACTIONS</th>
                                            </tr>
                                        </thead>
                                        <tbody class="small">
                                            <tr>
                                                <td colspan="6" class="text-center py-5 text-muted">
                                                    No e-books found in the library.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="d-flex justify-content-between align-items-center mt-3">
                                    <p class="text-muted small mb-0">Showing 0-0 of 0 books</p>
                                    <nav aria-label="Page navigation">
                                        <ul class="pagination pagination-sm mb-0">
                                            <li class="page-item disabled"><a class="page-link" href="#">1</a></li>
                                        </ul>
                                    </nav>
                                </div>
                            </div>
                        </div>

                        <div class="col-12 col-xl-4">
                            <div class="card data-card p-4">
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <h6 class="fw-bold mb-0">Add New E-Book</h6>
                                    <a href="#" class="text-decoration-none small text-success">Clear all</a>
                                </div>

                                <form>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Book Title</label>
                                        <input type="text" class="form-control form-control-sm" placeholder="e.g. Advanced Physics">
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-7">
                                            <label class="form-label small fw-bold">Author</label>
                                            <input type="text" class="form-control form-control-sm" placeholder="Author Name">
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small fw-bold">Price ($)</label>
                                            <input type="number" class="form-control form-control-sm" placeholder="0.00">
                                        </div>
                                    </div>
                                    <div class="row g-2 mb-3">
                                        <div class="col-7">
                                            <label class="form-label small fw-bold">Category</label>
                                            <select class="form-select form-select-sm">
                                                <option>Select...</option>
                                            </select>
                                        </div>
                                        <div class="col-5">
                                            <label class="form-label small fw-bold">ISBN</label>
                                            <input type="text" class="form-control form-control-sm" placeholder="Optional">
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label class="form-label small fw-bold">Cover Image</label>
                                        <div class="placeholder-box p-4 text-center" style="height: auto; border-style: dashed;">
                                            <i class="bi bi-image text-muted fs-4"></i>
                                            <p class="mb-0 small mt-2">Click to upload cover</p>
                                            <p class="text-muted" style="font-size: 0.65rem;">JPG, PNG (Max 2MB)</p>
                                        </div>
                                    </div>
                                    <div class="mb-4">
                                        <label class="form-label small fw-bold">E-Book File (PDF/EPUB)</label>
                                        <div class="p-2 border rounded d-flex align-items-center justify-content-between">
                                            <div class="d-flex align-items-center">
                                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4 me-2"></i>
                                                <div>
                                                    <div class="small fw-bold">advanced_physics_v2.pdf</div>
                                                    <div class="text-success" style="font-size: 0.65rem;"><i class="bi bi-check-circle-fill me-1"></i> Upload complete</div>
                                                </div>
                                            </div>
                                            <button type="button" class="btn-close" style="font-size: 0.5rem;"></button>
                                        </div>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Book</button>
                                        <button type="button" class="btn btn-outline-secondary btn-sm">Save Draft</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </main>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
    </body>

    </html>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>