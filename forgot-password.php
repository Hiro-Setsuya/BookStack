<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Forgot Password - BookStack</title>

    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">

    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:wght,FILL@100..700,0..1&display=swap" rel="stylesheet" />

    <style>
        :root {
            --primary-color: #4c99e6;
            --primary-hover: #3b8bd6;
            --text-dark: #111417;
            --text-muted: #647587;
            --bg-light: #f6f7f8;
            --border-color: #e5e7eb;
            --bg-footer: #f8f9fa;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-light);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        .navbar {
            background-color: white;
            border-bottom: 1px solid var(--border-color);
        }

        .navbar-brand {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-dark);
            font-weight: 700;
            font-size: 1.125rem;
        }

        .navbar-brand:hover {
            color: var(--text-dark);
        }

        .logo-icon {
            width: 32px;
            height: 32px;
            color: var(--primary-color);
        }

        .nav-link {
            color: var(--text-muted);
            font-weight: 500;
            font-size: 0.875rem;
        }

        .nav-link:hover {
            color: var(--text-dark);
        }

        main {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
        }

        .forgot-password-card {
            max-width: 480px;
            width: 100%;
            background: white;
            border-radius: 1rem;
            border: 1px solid var(--border-color);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .icon-wrapper {
            width: 56px;
            height: 56px;
            border-radius: 50%;
            background-color: rgba(76, 153, 230, 0.1);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            margin: 0 auto 1rem;
        }

        .icon-wrapper .material-symbols-outlined {
            font-size: 28px;
        }

        .card-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .card-description {
            color: var(--text-muted);
            font-size: 0.9375rem;
            max-width: 320px;
            margin: 0 auto;
        }

        .form-label {
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-dark);
            margin-bottom: 0.5rem;
        }

        .input-group-icon {
            position: absolute;
            left: 12px;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
            z-index: 10;
            pointer-events: none;
        }

        .input-group-icon .material-symbols-outlined {
            font-size: 20px;
        }

        .form-control {
            padding-left: 2.5rem;
            height: 48px;
            border-radius: 0.75rem;
            border: 1px solid #dce0e5;
            font-size: 1rem;
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.25);
        }

        .btn-primary {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
            height: 48px;
            font-weight: 700;
            font-size: 1rem;
            border-radius: 0.75rem;
        }

        .btn-primary:hover {
            background-color: var(--primary-hover);
            border-color: var(--primary-hover);
        }

        .btn-primary:focus {
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.3);
        }

        .back-link {
            background: none;
            border: none;
            color: var(--text-muted);
            font-weight: 600;
            font-size: 0.875rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: color 0.2s;
        }

        .back-link:hover {
            color: var(--text-dark);
        }

        .back-link:hover .material-symbols-outlined {
            transform: translateX(-4px);
        }

        .back-link .material-symbols-outlined {
            font-size: 20px;
            transition: transform 0.2s;
        }

        .card-footer-custom {
            background-color: var(--bg-footer);
            border-top: 1px solid var(--border-color);
            padding: 1rem 2rem;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-muted);
            border-radius: 0 0 1rem 1rem;
        }

        .card-footer-custom a {
            color: var(--primary-color);
            text-decoration: none;
        }

        .card-footer-custom a:hover {
            text-decoration: underline;
        }

        .form-check-input:checked {
            background-color: var(--primary-color);
            border-color: var(--primary-color);
        }

        .form-check-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.25rem rgba(76, 153, 230, 0.25);
        }

        .delivery-option {
            padding: 1rem;
            border: 2px solid var(--border-color);
            border-radius: 0.75rem;
            cursor: pointer;
            transition: all 0.2s;
        }

        .delivery-option:hover {
            border-color: var(--primary-color);
            background-color: rgba(76, 153, 230, 0.05);
        }

        .delivery-option.active {
            border-color: var(--primary-color);
            background-color: rgba(76, 153, 230, 0.1);
        }

        .delivery-option .form-check-input {
            margin-top: 0.25rem;
        }

        .delivery-option-label {
            display: flex;
            align-items: start;
            gap: 0.75rem;
            cursor: pointer;
        }

        .delivery-option-icon {
            color: var(--primary-color);
        }

        .delivery-option-text h6 {
            font-size: 0.9375rem;
            font-weight: 600;
            color: var(--text-dark);
            margin-bottom: 0.25rem;
        }

        .delivery-option-text p {
            font-size: 0.8125rem;
            color: var(--text-muted);
            margin-bottom: 0;
        }

        .masked-info {
            font-family: 'Courier New', monospace;
            font-size: 0.875rem;
            color: var(--text-dark);
            font-weight: 600;
            margin-top: 0.25rem;
        }

        @media (min-width: 576px) {
            .card-title {
                font-size: 2rem;
            }
        }
    </style>
</head>

<body>
    <nav class="navbar navbar-expand-lg navbar-light">
        <div class="container-fluid px-4 px-lg-5">
            <a class="navbar-brand" href="#">
                <svg class="logo-icon" fill="none" viewBox="0 0 48 48" xmlns="http://www.w3.org/2000/svg">
                    <path d="M24 4C25.7818 14.2173 33.7827 22.2182 44 24C33.7827 25.7818 25.7818 33.7827 24 44C22.2182 33.7827 14.2173 25.7818 4 24C14.2173 22.2182 22.2182 14.2173 24 4Z" fill="currentColor"></path>
                </svg>
                EduBooks
            </a>
            <div class="d-none d-sm-flex">
                <a class="nav-link px-3" href="#">Help</a>
            </div>
        </div>
    </nav>

    <?php
    // Simulating database retrieval - In production, fetch from database based on username/student ID
    $userEmail = "student@university.edu";
    $userPhone = "09123456789";

    // Mask email: show first 2 chars and domain
    $emailParts = explode('@', $userEmail);
    $length = strlen($emailParts[0]);
    $maskedEmail = substr($emailParts[0], 0, 2)
        . str_repeat('*', max($length - 2, 1))
        . '@' . $emailParts[1];
    $maskedPhone = substr($userPhone, 0, 3) . str_repeat('*', strlen($userPhone) - 7) . substr($userPhone, -4);

    ?>

    <main>
        <div class="forgot-password-card">
            <div class="card-body p-4 p-md-5">
                <!-- Icon & Heading -->
                <div class="text-center mb-4">
                    <div class="icon-wrapper">
                        <span class="material-symbols-outlined">lock_reset</span>
                    </div>
                    <h1 class="card-title">Forgot Password?</h1>
                    <p class="card-description">
                        No worries, we'll send you a verification code. Choose where you'd like to receive it.
                    </p>
                </div>

                <!-- Form Section -->
                <form onsubmit="event.preventDefault();">
                    <!-- Delivery Method Selection -->
                    <div class="mb-4">
                        <label class="form-label">Send verification code to:</label>
                        <div class="row g-3">
                            <div class="col-12">
                                <div class="delivery-option active" id="emailOption">
                                    <label class="delivery-option-label">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="deliveryMethod"
                                            id="radioEmail"
                                            value="email"
                                            checked>
                                        <span class="delivery-option-icon">
                                            <span class="material-symbols-outlined">mail</span>
                                        </span>
                                        <div class="delivery-option-text flex-grow-1">
                                            <h6>Email</h6>
                                            <p>Send code to your email</p>
                                            <div class="masked-info"><?php echo $maskedEmail; ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                            <div class="col-12">
                                <div class="delivery-option" id="smsOption">
                                    <label class="delivery-option-label">
                                        <input
                                            class="form-check-input"
                                            type="radio"
                                            name="deliveryMethod"
                                            id="radioSms"
                                            value="sms">
                                        <span class="delivery-option-icon">
                                            <span class="material-symbols-outlined">sms</span>
                                        </span>
                                        <div class="delivery-option-text flex-grow-1">
                                            <h6>SMS</h6>
                                            <p>Send code to your phone</p>
                                            <div class="masked-info"><?php echo $maskedPhone; ?></div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>

                    <button type="submit" class="btn btn-primary w-100 mb-3">
                        Send Verification Code
                    </button>
                </form>

                <div class="text-center pt-2">
                    <button class="back-link">
                        <span class="material-symbols-outlined">arrow_back</span>
                        <span>Back to Login</span>
                    </button>
                </div>
            </div>

            <div class="card-footer-custom">
                <p class="mb-0">
                    Need help? <a href="#">Contact Support</a>
                </p>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Handle delivery method selection
        const emailOption = document.getElementById('emailOption');
        const smsOption = document.getElementById('smsOption');
        const radioEmail = document.getElementById('radioEmail');
        const radioSms = document.getElementById('radioSms');

        // Click handlers for delivery options
        emailOption.addEventListener('click', function() {
            radioEmail.checked = true;
            emailOption.classList.add('active');
            smsOption.classList.remove('active');
        });

        smsOption.addEventListener('click', function() {
            radioSms.checked = true;
            smsOption.classList.add('active');
            emailOption.classList.remove('active');
        });

        // Radio button change handlers
        radioEmail.addEventListener('change', function() {
            if (this.checked) {
                emailOption.classList.add('active');
                smsOption.classList.remove('active');
            }
        });

        radioSms.addEventListener('change', function() {
            if (this.checked) {
                smsOption.classList.add('active');
                emailOption.classList.remove('active');
            }
        });
    </script>
</body>

</html>