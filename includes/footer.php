<?php
// Prevent direct access
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('403 Forbidden');
}
?>

<footer class="footer bg-green text-white mt-auto">
    <div class="container-fluid py-5 px-4 px-md-5">
        <div class="row g-4">
            <!-- Brand & Description -->
            <div class="col-lg-4 col-md-6">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <img src="assets/logo.svg" height="32" alt="BookStack" />
                    <span class="fw-bold fs-4">BookStack</span>
                </div>
                <p class="text-white-50 mb-3 pe-lg-4">
                    Your trusted platform for digital books and learning resources. Discover, purchase, and access knowledge anytime, anywhere.
                </p>
            </div>

            <!-- Quick Links -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold mb-3 text-uppercase">Explore</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="footer-link text-white-50 text-decoration-none">Home</a></li>
                    <li class="mb-2"><a href="ebooks.php" class="footer-link text-white-50 text-decoration-none">E-Books</a></li>
                    <li class="mb-2"><a href="cart.php" class="footer-link text-white-50 text-decoration-none">Cart</a></li>
                    <li class="mb-2"><a href="orders.php" class="footer-link text-white-50 text-decoration-none">My Orders</a></li>
                </ul>
            </div>

            <!-- Account -->
            <div class="col-lg-2 col-md-6 col-6">
                <h6 class="fw-bold mb-3 text-uppercase">Account</h6>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="profile.php" class="footer-link text-white-50 text-decoration-none">Profile</a></li>
                    <li class="mb-2"><a href="my-ebooks.php" class="footer-link text-white-50 text-decoration-none">My Library</a></li>
                    <li class="mb-2"><a href="login.php" class="footer-link text-white-50 text-decoration-none">Login</a></li>
                    <li class="mb-2"><a href="register.php" class="footer-link text-white-50 text-decoration-none">Register</a></li>
                </ul>
            </div>

            <!-- Contact Info -->
            <div class="col-lg-4 col-md-6">
                <h6 class="fw-bold mb-3 text-uppercase">Contact Us</h6>
                <ul class="list-unstyled">
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-envelope-fill me-2 mt-1"></i>
                        <a href="mailto:nullbyte235@gmail.com" class="footer-link text-white-50 text-decoration-none">
                            nullbyte235@gmail.com
                        </a>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-geo-alt-fill me-2 mt-1"></i>
                        <span class="text-white-50">Philippines</span>
                    </li>
                    <li class="mb-3 d-flex align-items-start">
                        <i class="bi bi-clock-fill me-2 mt-1"></i>
                        <span class="text-white-50">24/7 Digital Access</span>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <!-- Bottom Bar -->
    <div class="border-top border-white border-opacity-25">
        <div class="container-fluid px-4 px-md-5 py-3">
            <div class="row align-items-center">
                <div class="col-12 text-center text-md-start mb-2">
                    <small class="text-white-50">
                        © <?php echo date('Y'); ?> BookStack. All rights reserved.
                    </small>
                </div>
            </div>
        </div>
    </div>
</footer>

<style>
    .footer {
        background-color: #198754 !important;
        width: 100%;
    }

    .text-white-50 {
        color: rgba(255, 255, 255, 0.75) !important;
    }

    .footer-link:hover {
        color: #fff !important;
        transition: color 0.2s ease;
    }

    .footer h6 {
        font-size: 0.875rem;
        letter-spacing: 0.5px;
    }

    .footer ul li {
        font-size: 0.9rem;
    }

    .border-opacity-25 {
        --bs-border-opacity: 0.25;
    }
</style>