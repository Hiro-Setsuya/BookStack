<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user data for sidebar
$user_query = "SELECT user_name, email, created_at FROM users WHERE user_id = $user_id";
$user_result = executeQuery($user_query);
$user = mysqli_fetch_assoc($user_result);

$title = 'About';
include 'includes/head.php';
?>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container account-container py-4">
        <div class="row">
            <?php include 'includes/client-sidebar.php'; ?>

            <div class="col-lg-9">
                <div class="profile-header mb-4 text-center text-lg-start">
                    <h2 class="fw-bold">About BookStack</h2>
                    <p class="text-muted">Learn more about our platform and mission.</p>
                </div>

                <!-- About Section -->
                <div class="card profile-card p-4 mb-4">
                    <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-book-fill text-success"></i> What is BookStack?
                    </h4>
                    <p class="text-muted mb-3" style="line-height: 1.8;">
                        BookStack is a comprehensive digital bookstore platform that provides users with instant access to a wide collection of e-books.
                        Our platform makes it easy to discover, purchase, and download e-books in PDF format, giving you the freedom to read anywhere, anytime.
                    </p>
                    <p class="text-muted" style="line-height: 1.8;">
                        Whether you're looking for educational materials, professional development resources, or leisure reading,
                        BookStack offers a seamless experience from browsing to downloading your favorite titles.
                    </p>
                </div>

                <!-- Features Section -->
                <div class="card profile-card p-4 mb-4">
                    <h4 class="fw-bold mb-4">Key Features</h4>
                    <div class="row g-4">
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-lightning-charge-fill text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Instant Download</h6>
                                    <p class="text-muted small mb-0">Get immediate access to your purchased e-books with instant PDF downloads.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-shield-check text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Secure Payments</h6>
                                    <p class="text-muted small mb-0">Safe and secure payment processing through PayPal integration.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-collection-fill text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Wide Selection</h6>
                                    <p class="text-muted small mb-0">Browse through diverse categories and discover new titles regularly.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-star-fill text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">User Reviews</h6>
                                    <p class="text-muted small mb-0">Read reviews from verified purchasers to make informed decisions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-person-check-fill text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Account Verification</h6>
                                    <p class="text-muted small mb-0">Secure your account with our verification system for trusted transactions.</p>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="d-flex gap-3">
                                <div class="flex-shrink-0">
                                    <div class="rounded-circle bg-success bg-opacity-10 p-3">
                                        <i class="bi bi-cart-check-fill text-success fs-4"></i>
                                    </div>
                                </div>
                                <div>
                                    <h6 class="fw-bold mb-2">Easy Shopping</h6>
                                    <p class="text-muted small mb-0">Add items to cart or buy instantly with our streamlined checkout process.</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Technology Stack -->
                <div class="card profile-card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Technology Stack</h4>
                    <p class="text-muted mb-3" style="line-height: 1.8;">
                        BookStack is built with modern web technologies to ensure a fast, secure, and reliable experience:
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="badge bg-success text-white border px-3 py-2">PHP</span>
                        <span class="badge bg-success text-white border px-3 py-2">MySQL</span>
                        <span class="badge bg-success text-white border px-3 py-2">Bootstrap 5</span>
                        <span class="badge bg-success text-white border px-3 py-2">JavaScript</span>
                        <span class="badge bg-success text-white border px-3 py-2">PayPal API</span>
                        <span class="badge bg-success text-white border px-3 py-2">PHPMailer</span>
                        <span class="badge bg-success text-white border px-3 py-2">Ollama</span>
                    </div>
                </div>

                <!-- Members Section -->
                <div class="card profile-card p-4 mb-4">
                    <h4 class="fw-bold mb-3 d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-success"></i> Meet the Members
                    </h4>
                    <div class="row g-4">
                        <div class="col-md-6 col-lg-4">
                            <div class="d-flex align-items-center gap-3">
                                <img src="assets/img/members/hiro.jpg" alt="Adrian" class="rounded-circle" style="width: 60px; height: 60px; object-fit: cover;">
                                <div>
                                    <h6 class="fw-bold mb-1">Adrian Vincent H. Javillo</h6>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Collab Section -->
                <div class=" card profile-card p-4 mb-4 mt-4">
                    <h4 class="fw-bold mb-4 d-flex align-items-center gap-2">
                        <i class="bi bi-people-fill text-success"></i> Collaboration Partners
                    </h4>
                    <div class="row align-items-center">
                        <div class="col-md-3 text-center mb-3 mb-md-0">
                            <img src="assets/img/logo/emblem v1.png" alt="EscaPinas Logo" class="img-fluid" style="max-height: 120px; width: auto;">
                        </div>
                        <div class="col-md-9">
                            <h5 class="fw-bold mb-2">EscaPinas</h5>
                            <p class="text-muted mb-0" style="line-height: 1.6;">
                                In collaboration with <strong>EscaPinas</strong>, we are expanding our digital reach to provide high-quality localized content and enhanced distribution services across the region.
                            </p>
                            <div class="mt-3">
                                <span class="badge rounded-pill bg-success px-3">Official Partner</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Contact Information -->
                <div class="card profile-card p-4 mb-4">
                    <h4 class="fw-bold mb-3">Get in Touch</h4>
                    <p class="text-muted mb-4" style="line-height: 1.8;">
                        Have questions or feedback? We'd love to hear from you!
                    </p>
                    <div class="d-flex flex-column gap-3">
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-envelope-fill text-success fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Email</small>
                                <span class="fw-semibold">nullbyte235@gmail.com</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-geo-alt-fill text-success fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Location</small>
                                <span class="fw-semibold">Philippines</span>
                            </div>
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            <i class="bi bi-globe text-success fs-5"></i>
                            <div>
                                <small class="text-muted d-block">Website</small>
                                <span class="fw-semibold">www.bookstack.com</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Version Info -->
                <div class="card profile-card p-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="fw-bold mb-1">BookStack Version</h6>
                            <p class="text-muted small mb-0">Version 1.0.0 - Released January 2026</p>
                        </div>
                        <div class="text-end">
                            <span class="badge bg-success px-3 py-2">Stable</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>