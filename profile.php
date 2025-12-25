<?php session_start(); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Settings - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        body {
            background-color: #f8f9fa;
        }

        .sidebar-link {
            color: #6c757d;
            text-decoration: none;
            padding: 10px 15px;
            display: block;
            border-radius: 8px;
            transition: 0.3s;
        }

        .sidebar-link:hover,
        .sidebar-link.active {
            background-color: #e9ecef;
            color: #198754;
        }

        .profile-card {
            border: none;
            border-radius: 12px;
        }

        .form-control-custom {
            background-color: #f1f3f5;
            border: none;
            padding: 12px;
        }
    </style>
</head>

<body>
    <nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
        <div class="container-fluid">
            <div class="navbar-brand fw-bold text-success">
                <img src="assets/logo.svg" height="25" alt="Logo">
                <span>BookStack</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-lg-4">
                    <li class="nav-item px-lg-3">
                        <a class="nav-link fw-semibold text-success" href="index.php">
                            <i class="bi bi-house-door-fill me-2"></i>Home
                        </a>
                    </li>
                    <li class="nav-item px-lg-3">
                        <a class="nav-link fw-semibold" href="ebooks.php">
                            <i class="bi bi-book-fill me-2"></i>E-Books
                        </a>
                    </li>
                    <li class="nav-item px-lg-3">
                        <a class="nav-link fw-semibold" href="ebook-details.php">
                            <i class="bi bi-grid-fill me-2"></i>Categories
                        </a>
                    </li>
                </ul>
                <div class="ms-auto d-flex align-items-center">
                    <i class="bi bi-bell fs-5 me-3 text-muted"></i>
                    <div class="rounded-circle bg-secondary" style="width: 35px; height: 35px;"></div>
                </div>
            </div>
        </div>
    </nav>

    <div class="container" style="margin-top: 100px;">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="small text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem;">Account</div>
                <nav class="nav flex-column mb-4">
                    <a class="sidebar-link active" href="#"><i class="bi bi-person me-2"></i> General Profile</a>
                    <a class="sidebar-link" href="#"><i class="bi bi-book me-2"></i> My Books</a>
                    <a class="sidebar-link" href="#"><i class="bi bi-credit-card me-2"></i> Billing & Orders</a>
                    <a class="sidebar-link" href="#"><i class="bi bi-shield-lock me-2"></i> Security</a>
                </nav>

                <div class="small text-uppercase text-muted fw-bold mb-3" style="font-size: 0.75rem;">Preferences</div>
                <nav class="nav flex-column">
                    <a class="sidebar-link" href="#"><i class="bi bi-bell me-2"></i> Notifications</a>
                    <a class="sidebar-link" href="#"><i class="bi bi-universal-access me-2"></i> Accessibility</a>
                    <div class="sidebar-link d-flex justify-content-between align-items-center">
                        <span><i class="bi bi-moon me-2"></i> Dark Mode</span>
                        <div class="form-check form-switch">
                            <input class="form-check-input" type="checkbox">
                        </div>
                    </div>
                </nav>

                <div class="mt-5">
                    <a href="logout.php" class="text-danger text-decoration-none fw-semibold"><i class="bi bi-box-arrow-left me-2"></i> Log Out</a>
                </div>
            </div>

            <div class="col-lg-9">
                <h2 class="fw-bold">Account Settings</h2>
                <p class="text-muted">Manage your personal information and student preferences.</p>

                <div class="card profile-card shadow-sm p-4 mb-4 bg-white">
                    <div class="d-flex align-items-center justify-content-between">
                        <div class="d-flex align-items-center">
                            <div class="position-relative">
                                <img src="https://via.placeholder.com/80" class="rounded-circle me-3" alt="User">
                                <span class="position-absolute bottom-0 end-0 bg-primary rounded-circle border border-white p-1" style="transform: translate(-10px, 0);"><i class="bi bi-camera-fill text-white small"></i></span>
                            </div>
                            <div>
                                <h5 class="mb-0 fw-bold">Alex Johnson</h5>
                                <p class="mb-1 text-muted small">alex.j@stanford.edu</p>
                                <span class="badge bg-primary-subtle text-primary border border-primary-subtle">Student Status: Active</span>
                            </div>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm">View Public Profile</button>
                    </div>
                </div>

                <div class="card profile-card shadow-sm p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5 class="fw-bold mb-0">Personal Information</h5>
                        <i class="bi bi-pencil text-muted"></i>
                    </div>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">First Name</label>
                            <input type="text" class="form-control form-control-custom" value="Alex">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Last Name</label>
                            <input type="text" class="form-control form-control-custom" value="Johnson">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light"><i class="bi bi-envelope"></i></span>
                                <input type="email" class="form-control form-control-custom" value="alex.j@stanford.edu">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold text-muted">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light"><i class="bi bi-telephone"></i></span>
                                <input type="text" class="form-control form-control-custom" value="+1 (555) 000-0000">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-5">
                        <a href="#" class="text-danger small fw-semibold text-decoration-none">Deactivate Account</a>
                        <div>
                            <button class="btn btn-light me-2">Cancel</button>
                            <button class="btn btn-primary px-4">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>