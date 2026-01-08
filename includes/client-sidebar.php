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

<div class="col-12 mt-3 d-lg-none">
    <div class="p-3 rounded" style="background-color: #f8f9fa;">
        <nav class="nav flex-column gap-1">
            <?php $current_page = basename($_SERVER['PHP_SELF']); ?>

            <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-dark" href="profile.php" style="<?= ($current_page == 'profile.php') ? 'background-color: white;' : '' ?>">
                <i class="bi bi-person me-3 <?= ($current_page == 'profile.php') ? 'text-primary' : 'text-muted' ?>"></i>
                <span>Profile</span>
            </a>

            <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-dark" href="my-ebooks.php" style="<?= ($current_page == 'my-ebooks.php') ? 'background-color: white;' : '' ?>">
                <i class="bi bi-book me-3 <?= ($current_page == 'my-ebooks.php') ? 'text-primary' : 'text-muted' ?>"></i>
                <span>My E-Books</span>
            </a>

            <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-dark" href="my-vouchers.php" style="<?= ($current_page == 'my-vouchers.php') ? 'background-color: white;' : '' ?>">
                <i class="bi bi-ticket-perforated me-3 <?= ($current_page == 'my-vouchers.php') ? 'text-primary' : 'text-muted' ?>"></i>
                <span>My Vouchers</span>
            </a>

            <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-dark" href="about.php" style="<?= ($current_page == 'about.php') ? 'background-color: white;' : '' ?>">
                <i class="bi bi-info-circle me-3 <?= ($current_page == 'about.php') ? 'text-primary' : 'text-muted' ?>"></i>
                <span>About</span>
            </a>

            <hr class="my-2">

            <a class="nav-link d-flex align-items-center px-3 py-2 rounded text-danger" href="client-logout.php">
                <i class="bi bi-box-arrow-left me-3"></i>
                <span>Log Out</span>
            </a>
        </nav>
    </div>
</div>