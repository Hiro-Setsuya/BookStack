<?php
session_start();
require_once 'includes/form-input.php';

$title = 'Legal Information';
$extraStyles = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght@100..700&display=swap" rel="stylesheet" />' . "\n";

include 'includes/head.php';
?>

<style>
    /* Change this section in your <style> tag */
    .legal-container {
        max-width: 1200px;
        /* Increased from 900px */
        margin: 4rem auto;
        background: #ffffff;
        border-radius: 15px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
        border: 1px solid #dee2e6;
    }

    .legal-header {
        background: linear-gradient(135deg, #1fd26a 0%, #17a654 100%);
        color: white;
        padding: 3rem 2rem;
        border-radius: 15px 15px 0 0;
        text-align: center;
    }

    .legal-content {
        padding: 3rem;
        line-height: 1.8;
        color: #495057;
    }

    .legal-content h3 {
        color: #212529;
        font-weight: 700;
        margin-top: 2rem;
        margin-bottom: 1rem;
    }

    .nav-pills .nav-link {
        color: #6c757d;
        font-weight: 600;
        padding: 12px 25px;
    }

    .nav-pills .nav-link.active {
        background-color: #1fd26a;
        color: white;
    }

    .back-link {
        text-decoration: none;
        color: #1fd26a;
        display: inline-flex;
        align-items: center;
        gap: 5px;
        margin-bottom: 20px;
        font-weight: 600;
    }

    /* Ensure the page stretches to keep footer at bottom */
    body {
        display: flex;
        flex-direction: column;
        min-height: 100vh;
    }

    .main-content {
        flex: 1 0 auto;
    }
</style>

<body class="bg-light">
    <div class="main-content">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-12">
                    <div class="mt-5">
                        <a href="register.php" class="back-link">
                            <span class="material-symbols-outlined">arrow_back</span>
                            Back to Registration
                        </a>
                    </div>

                </div>
                <div class="legal-container mb-5">
                    <div class="legal-header">
                        <div class="d-flex align-items-center justify-content-center gap-2 mb-3">
                            <img src="assets/img/logo/logo.svg" height="40" width="40" alt="Logo">
                            <span class="fs-3 fw-bold">BookStack</span>
                        </div>
                        <h1 class="display-6 fw-bold">Policies & Agreements</h1>
                    </div>

                    <div class="p-4 border-bottom bg-white sticky-top rounded-0">
                        <ul class="nav nav-pills justify-content-center" id="legalTab" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="terms-tab" data-bs-toggle="pill" data-bs-target="#terms" type="button" role="tab">Terms of Service</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="privacy-tab" data-bs-toggle="pill" data-bs-target="#privacy" type="button" role="tab">Privacy Policy</button>
                            </li>
                        </ul>
                    </div>

                    <div class="tab-content legal-content" id="legalTabContent">
                        <div class="tab-pane fade show active" id="terms" role="tabpanel">
                            <h2>Terms of Service</h2>
                            <p class="lead">Please read these terms carefully before using BookStack.</p>

                            <h3>1. Acceptance of Terms</h3>
                            <p>By creating an account or accessing our services, you agree to be bound by these Terms of Service and all applicable laws and regulations.</p>

                            <h3>2. User Accounts</h3>
                            <p>When you create an account, you must provide accurate and complete information. You are responsible for maintaining the confidentiality of your password and account details.</p>

                            <h3>3. Intellectual Property</h3>
                            <p>All content included on this site, such as text, graphics, logos, and software, is the property of BookStack or its content suppliers and protected by international copyright laws.</p>

                            <h3>4. Termination</h3>
                            <p>We reserve the right to terminate or suspend your account immediately, without prior notice, for conduct that we believe violates these Terms.</p>
                        </div>

                        <div class="tab-pane fade" id="privacy" role="tabpanel">
                            <h2>Privacy Policy</h2>
                            <p class="lead">Your privacy is important to us. This policy explains how we handle your data.</p>

                            <h3>1. Information We Collect</h3>
                            <ul>
                                <li><strong>Account Data:</strong> Username, email address, and hashed password.</li>
                                <li><strong>Usage Data:</strong> Information on how you interact with our textbooks.</li>
                                <li><strong>Technical Data:</strong> IP address, browser type, and device information.</li>
                            </ul>

                            <h3>2. How We Use Your Information</h3>
                            <p>We use your information to provide services, personalize your experience, send welcome emails, and maintain the security of our platform.</p>

                            <h3>3. Data Protection</h3>
                            <p>We implement industry-standard security measures, including password hashing (bcrypt) and SSL encryption, to protect your personal information.</p>

                            <h3>4. Third-Party Services</h3>
                            <p>We do not sell your personal data to third parties. We only share data with essential service providers (like email delivery services) necessary to operate our platform.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>