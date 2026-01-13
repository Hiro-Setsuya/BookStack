<?php
// Prevent direct access
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Ensure cart count is available
if (!isset($cart_count)) {
    require_once __DIR__ . '/cart-utils.php';
}

// --- Reusable helper function ---
/**
 * Returns active classes if current page matches the given filename.
 *
 * @param string $page Filename to check (e.g., 'index.php')
 * @param string $activeClass CSS class(es) to apply when active (default: 'text-green fw-bold')
 * @return string
 */
function isActive(string $page, string $activeClass = 'text-green fw-bold'): string
{
    return basename($_SERVER['SCRIPT_NAME']) === $page ? $activeClass : '';
}
// -----------------------------
?>

<nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
    <div class="container-fluid">
        <a href="index.php" class="navbar-brand fw-bold text-green">
            <img src="assets/img/logo/logo.svg" height="25" alt="Logo" />
            <span>BookStack</span>
        </a>

        <div class="ms-auto d-flex align-items-center">
            <button class="navbar-toggler me-2" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-green d-lg-none d-inline-flex align-items-center justify-content-center">
                    <i class="bi bi-person-circle"></i>
                    <span class="d-none d-sm-inline ms-2"><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'User'); ?></span>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-green d-lg-none d-inline-flex align-items-center justify-content-center fw-normal">
                    <i class="bi bi-box-arrow-in-right"></i>
                    <span class="d-none d-sm-inline ms-2">Sign In</span>
                </a>
            <?php endif; ?>
        </div>

        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav">
                <li class="nav-item px-lg-3">
                    <a class="nav-link fw-semibold <?= isActive('index.php'); ?>" href="index.php">
                        <i class="bi bi-house-door-fill me-2"></i>Home
                    </a>
                </li>
                <li class="nav-item px-lg-3">
                    <a class="nav-link fw-semibold <?= isActive('ebooks.php'); ?>" href="ebooks.php">
                        <i class="bi bi-book-fill me-2"></i>E-Books
                    </a>
                </li>
                <li class="nav-item px-lg-3">
                    <a class="nav-link fw-semibold <?= isActive('categories.php'); ?>" href="categories.php">
                        <i class="bi bi-grid-fill me-2"></i>Categories
                    </a>
                </li>
                <li class="nav-item px-lg-3 d-inline-block d-lg-none">
                    <a class="nav-link fw-semibold <?= isActive('cart.php'); ?>" href="cart.php">
                        <i class="bi bi-cart-fill me-2"></i>Cart
                    </a>
                </li>
            </ul>

            <!-- Mobile Search Form -->
            <form method="GET" action="ebooks.php" class="d-lg-none px-3 py-2">
                <div class="input-group">
                    <input class="form-control" type="text" name="q" placeholder="Search BookStack" />
                    <button class="btn btn-green" type="submit">
                        <i class="bi bi-search"></i>
                    </button>
                </div>
            </form>
        </div>

        <div class="ms-auto d-none d-lg-flex align-items-center">
            <form method="GET" action="ebooks.php" class="me-2">
                <input class="form-control" type="text" name="q" placeholder="Search BookStack" style="width: 200px;" />
            </form>

            <!-- Cart with badge -->
            <a href="cart.php" class="btn btn-green me-2 position-relative">
                <i class="bi bi-cart3"></i>
                <?php if ($cart_count > 0): ?>
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                        <?php echo $cart_count; ?>
                    </span>
                <?php endif; ?>
            </a>

            <!-- Profile or Sign In button -->
            <?php if (isset($_SESSION['user_id'])): ?>
                <a href="profile.php" class="btn btn-green text-nowrap">
                    <i class="bi bi-person-circle me-2"></i><?php echo htmlspecialchars($_SESSION['user_name'] ?? 'Profile'); ?>
                </a>
            <?php else: ?>
                <a href="login.php" class="btn btn-green text-nowrap fw-normal">
                    <i class="bi bi-box-arrow-in-right me-2"></i>Sign In
                </a>
            <?php endif; ?>
        </div>
    </div>
</nav>