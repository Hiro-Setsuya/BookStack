<?php session_start(); ?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Account Settings - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
        <div class="container-fluid">
            <div class="navbar-brand fw-bold text-green text-gprof">
                <img src="assets/logo.svg" height="25" alt="Logo">
                <span>BookStack</span>
            </div>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item px-lg-3">
                        <a class="nav-link fw-semibold text-green" href="index.php">
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
            </div>
            <div class="ms-auto d-none d-lg-flex align-items-center gap-3">
                <i class="bi bi-bell fs-5 text-muted"></i>
                <div class="rounded-circle" style="width: 35px; height: 35px; background: linear-gradient(135deg, #2ecc71 0%, #27a961 100%); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 0.9rem;">AJ</div>
            </div>
        </div>
    </nav>

    <div class="container account-container">
        <div class="row">
            <div class="col-lg-3 mb-4">
                <div class="sidebar-section-label mb-3">Account</div>
                <nav class="nav flex-column mb-4">
                    <a class="sidebar-link active" href="#"><i class="bi bi-person me-2"></i> General Profile</a>
                    <a class="sidebar-link" href="orders.php"><i class="bi bi-book me-2"></i> My Books</a>
                    <a class="sidebar-link" href="orders.php"><i class="bi bi-credit-card me-2"></i> Billing & Orders</a>
                    <a class="sidebar-link" href="change-password.php"><i class="bi bi-shield-lock me-2"></i> Security</a>
                </nav>

                <div class="sidebar-section-label mb-3">Preferences</div>
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
                    <a href="logout.php" class="sidebar-link text-danger fw-semibold"><i class="bi bi-box-arrow-left me-2"></i> Log Out</a>
                </div>
            </div>

            <div class="col-lg-9">
                <div class="profile-header mb-4">
                    <h2>Account Settings</h2>
                    <p>Manage your personal information and preferences.</p>
                </div>

                <div class="card profile-card p-4 mb-4">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <div class="position-relative">
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: linear-gradient(135deg, #2ecc71 0%, #27a961 100%); color: white; font-size: 2rem; font-weight: 700;">AJ</div>
                                <button class="position-absolute bottom-0 end-0 btn btn-sm btn-primary rounded-circle p-2" style="transform: translate(-8px, -8px); width: 40px; height: 40px; display: flex; align-items: center; justify-content: center;" title="Change photo">
                                    <i class="bi bi-camera-fill"></i>
                                </button>
                            </div>
                            <div>
                                <h5 class="mb-1 fw-bold">Alex Johnson</h5>
                                <p class="mb-2 text-muted small">alex.j@bookstack.com</p>
                                <span class="badge bg-success">Active Member</span>
                            </div>
                        </div>
                        <button class="btn btn-outline-secondary btn-sm">View Public Profile</button>
                    </div>
                </div>

                <div class="card profile-card p-4 bg-white">
                    <div class="d-flex justify-content-between align-items-center mb-4 border-bottom pb-3">
                        <h5 class="fw-bold mb-0">Personal Information</h5>
                        <button class="btn btn-link btn-sm text-primary p-0" title="Edit">
                            <i class="bi bi-pencil-square"></i>
                        </button>
                    </div>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">First Name</label>
                            <input type="text" class="form-control form-control-custom" value="Alex" placeholder="First Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Last Name</label>
                            <input type="text" class="form-control form-control-custom" value="Johnson" placeholder="Last Name">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light"><i class="bi bi-envelope text-muted"></i></span>
                                <input type="email" class="form-control form-control-custom border-start-0" value="alex.j@bookstack.com" placeholder="Email">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Phone Number</label>
                            <div class="input-group">
                                <span class="input-group-text border-0 bg-light"><i class="bi bi-telephone text-muted"></i></span>
                                <input type="text" class="form-control form-control-custom border-start-0" value="+1 (555) 000-0000" placeholder="Phone">
                            </div>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center mt-5 pt-4 border-top">
                        <a href="#" class="text-danger small fw-semibold text-decoration-none">Deactivate Account</a>
                        <div class="gap-2 d-flex">
                            <button class="btn btn-outline-secondary">Cancel</button>
                            <button class="btn btn-green px-4">Save Changes</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>