<?php
// admin-nav.php - Reusable admin navigation component
// Usage: include '../includes/admin-nav.php';
// Set $currentPage variable before including (e.g., $currentPage = 'dashboard';)

// Get admin name from session
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Define navigation items
$navItems = [
    'dashboard' => ['icon' => 'bi-grid-fill', 'label' => 'Dashboard', 'url' => 'dashboard.php'],
    'ebooks' => ['icon' => 'bi-journal-text', 'label' => 'E-Books', 'url' => 'manage-ebooks.php'],
    'categories' => ['icon' => 'bi-layers', 'label' => 'Categories', 'url' => 'manage-categories.php'],
    'users' => ['icon' => 'bi-people', 'label' => 'Users', 'url' => 'manage-users.php'],
    'orders' => ['icon' => 'bi-cart', 'label' => 'Orders', 'url' => 'manage-orders.php'],
    'vouchers' => ['icon' => 'bi-ticket-perforated', 'label' => 'Vouchers', 'url' => 'manage-vouchers.php'],
    'verifications' => ['icon' => 'bi-shield-check', 'label' => 'Verifications', 'url' => 'manage-verification.php'],
    'messages' => ['icon' => 'bi-envelope', 'label' => 'Messages', 'url' => 'manage-messages.php']
];

// If $currentPage is not set, try to detect it from current file
if (!isset($currentPage)) {
    $currentFile = basename($_SERVER['PHP_SELF']);
    $currentPage = '';
    foreach ($navItems as $key => $item) {
        if ($item['url'] === $currentFile) {
            $currentPage = $key;
            break;
        }
    }
}
?>

<!-- Mobile Header -->
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

        <!-- Sidebar Navigation -->
        <nav class="sidebar d-flex flex-column pb-4" id="sidebar-menu">
            <!-- Brand Logo -->
            <div class="p-4 mb-2 sidebar-brand">
                <div class="navbar-brand fw-bold text-green brand-title">
                    <span>BookStack</span>
                </div>
            </div>

            <!-- Navigation Links -->
            <div class="nav flex-column mb-auto">
                <?php foreach ($navItems as $key => $item): ?>
                    <a href="<?= $item['url'] ?>" class="nav-link <?= ($currentPage === $key) ? 'active' : '' ?>">
                        <i class="bi <?= $item['icon'] ?> me-3"></i><?= $item['label'] ?>
                    </a>
                <?php endforeach; ?>

                <!-- Logout Link -->
                <a href="logout.php" class="nav-link text-danger mt-2">
                    <i class="bi bi-box-arrow-left me-3"></i>Logout
                </a>

                <!-- Admin Profile -->
                <div class="px-3 mt-3">
                    <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
                        <img src="https://ui-avatars.com/api/?name=<?= urlencode($adminName) ?>&background=198754&color=fff"
                            class="rounded-circle me-2"
                            width="35"
                            height="35"
                            alt="<?= htmlspecialchars($adminName) ?>">
                        <div>
                            <p class="mb-0 small fw-bold text-dark"><?= htmlspecialchars($adminName) ?></p>
                            <p class="mb-0 text-muted admin-role">System Administrator</p>
                        </div>
                    </div>
                </div>
            </div>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content w-100">