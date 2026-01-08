<div class="col-lg-3 mb-4 d-none d-lg-block">
    <div class="sidebar-section-label mb-3">Account</div>
    <nav class="nav flex-column mb-4">
        <a class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) == 'profile.php') ? 'active' : '' ?>" href="profile.php">
            <i class="bi bi-person me-2"></i> Profile
        </a>
        <a class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) == 'my-ebooks.php') ? 'active' : '' ?>" href="my-ebooks.php">
            <i class="bi bi-book me-2"></i> My E-Books
        </a>
        <a class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) == 'my-vouchers.php') ? 'active' : '' ?>" href="my-vouchers.php">
            <i class="bi bi-ticket-perforated me-2"></i> My Vouchers
        </a>
    </nav>

    <div class="sidebar-section-label mb-3">Preferences</div>
    <nav class="nav flex-column">
        <a class="sidebar-link <?= (basename($_SERVER['PHP_SELF']) == 'about.php') ? 'active' : '' ?>" href="about.php">
            <i class="bi bi-info-circle me-2"></i> About
        </a>
        <div class="sidebar-link d-flex justify-content-between align-items-center">
            <span><i class="bi bi-moon me-2"></i> Dark Mode</span>
            <div class="form-check form-switch">
                <input class="form-check-input" type="checkbox">
            </div>
        </div>
    </nav>

    <div class="mt-5">
        <a href="client-logout.php" class="sidebar-link text-danger fw-semibold">
            <i class="bi bi-box-arrow-left me-2"></i> Log Out
        </a>
    </div>
</div>