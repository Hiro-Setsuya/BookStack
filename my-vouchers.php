<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's vouchers
$query = "SELECT * FROM vouchers 
          WHERE user_id = $user_id 
          ORDER BY 
            CASE 
              WHEN expires_at > NOW() AND times_used < max_uses THEN 1
              WHEN expires_at <= NOW() THEN 2
              WHEN times_used >= max_uses THEN 3
            END,
            expires_at DESC";
$result = executeQuery($query);

// Fetch user data
$user_query = "SELECT user_name, email, created_at FROM users WHERE user_id = $user_id";
$user_result = executeQuery($user_query);
$user = mysqli_fetch_assoc($user_result);
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>My Vouchers - BookStack</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <style>
        .voucher-card {
            position: relative;
            overflow: hidden;
            border-left: 4px solid var(--primary-color);
        }

        .voucher-card.expired {
            opacity: 0.6;
            border-left-color: #dc3545;
        }

        .voucher-card.fully-used {
            opacity: 0.6;
            border-left-color: #6c757d;
        }

        .voucher-code {
            font-family: 'Courier New', monospace;
            font-weight: bold;
            font-size: 1.25rem;
            letter-spacing: 2px;
            background: linear-gradient(135deg, var(--primary-color) 0%, var(--primary-hover) 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .voucher-badge {
            position: absolute;
            top: 10px;
            right: 10px;
            transform: rotate(15deg);
        }

        .copy-code-btn {
            transition: all 0.2s;
        }

        .copy-code-btn:active {
            transform: scale(0.95);
        }
    </style>
</head>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container account-container py-4">
        <div class="row">
            <?php include 'includes/client-sidebar.php'; ?>

            <div class="col-lg-9">
                <div class="profile-header mb-4 text-center text-lg-start">
                    <h2 class="fw-bold">My Vouchers</h2>
                    <p class="text-muted">View and manage your discount vouchers.</p>
                </div>

                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="row g-4">
                        <?php while ($voucher = mysqli_fetch_assoc($result)):
                            $is_expired = strtotime($voucher['expires_at']) <= time();
                            $is_fully_used = $voucher['times_used'] >= $voucher['max_uses'];
                            $is_active = !$is_expired && !$is_fully_used;
                            $remaining_uses = $voucher['max_uses'] - $voucher['times_used'];
                        ?>
                            <div class="col-12">
                                <div class="card profile-card voucher-card <?php echo $is_expired ? 'expired' : ($is_fully_used ? 'fully-used' : ''); ?>">
                                    <div class="card-body p-4">
                                        <?php if ($is_expired): ?>
                                            <div class="voucher-badge">
                                                <span class="badge bg-danger">Expired</span>
                                            </div>
                                        <?php elseif ($is_fully_used): ?>
                                            <div class="voucher-badge">
                                                <span class="badge bg-secondary">Fully Used</span>
                                            </div>
                                        <?php else: ?>
                                            <div class="voucher-badge">
                                                <span class="badge bg-success">Active</span>
                                            </div>
                                        <?php endif; ?>

                                        <div class="row g-3 align-items-center">
                                            <!-- Voucher Icon -->
                                            <div class="col-auto d-none d-md-block">
                                                <div class="d-flex align-items-center justify-content-center rounded-circle"
                                                    style="width: 80px; height: 80px; background: linear-gradient(135deg, <?php echo $is_active ? 'var(--primary-color)' : '#6c757d'; ?> 0%, <?php echo $is_active ? 'var(--primary-hover)' : '#495057'; ?> 100%);">
                                                    <i class="bi bi-ticket-perforated text-white" style="font-size: 2rem;"></i>
                                                </div>
                                            </div>

                                            <!-- Voucher Details -->
                                            <div class="col">
                                                <div class="mb-2">
                                                    <span class="badge <?php echo $voucher['external_system'] === 'travel_agency' ? 'bg-info' : 'bg-primary'; ?>">
                                                        <i class="bi bi-<?php echo $voucher['external_system'] === 'travel_agency' ? 'airplane' : 'book'; ?> me-1"></i>
                                                        <?php echo $voucher['external_system'] === 'travel_agency' ? 'Travel Agency' : 'BookStack'; ?>
                                                    </span>
                                                </div>

                                                <div class="d-flex align-items-center gap-3 mb-3">
                                                    <div class="voucher-code"><?php echo htmlspecialchars($voucher['code']); ?></div>
                                                    <?php if ($is_active): ?>
                                                        <button class="btn btn-sm btn-outline-secondary copy-code-btn"
                                                            onclick="copyCode('<?php echo htmlspecialchars($voucher['code']); ?>', this)"
                                                            title="Copy code">
                                                            <i class="bi bi-clipboard"></i>
                                                        </button>
                                                    <?php endif; ?>
                                                </div>

                                                <div class="row g-2 mb-3">
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <i class="bi bi-tag-fill me-2"></i>
                                                            <span>
                                                                <?php if ($voucher['discount_type'] === 'percentage'): ?>
                                                                    <strong class="text-success"><?php echo number_format($voucher['discount_amount'], 0); ?>% OFF</strong>
                                                                <?php else: ?>
                                                                    <strong class="text-success">$<?php echo number_format($voucher['discount_amount'], 2); ?> OFF</strong>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                    <?php if ($voucher['min_order_amount'] > 0): ?>
                                                        <div class="col-md-6">
                                                            <div class="d-flex align-items-center text-muted small">
                                                                <i class="bi bi-cart-check me-2"></i>
                                                                <span>Min. order: <strong>$<?php echo number_format($voucher['min_order_amount'], 2); ?></strong></span>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <i class="bi bi-calendar-event me-2"></i>
                                                            <span>Expires: <strong><?php echo date('M d, Y', strtotime($voucher['expires_at'])); ?></strong></span>
                                                        </div>
                                                    </div>
                                                    <div class="col-md-6">
                                                        <div class="d-flex align-items-center text-muted small">
                                                            <i class="bi bi-arrow-repeat me-2"></i>
                                                            <span>
                                                                Used: <strong><?php echo $voucher['times_used']; ?>/<?php echo $voucher['max_uses']; ?></strong>
                                                                <?php if ($remaining_uses > 0 && !$is_expired): ?>
                                                                    <span class="text-success">(<?php echo $remaining_uses; ?> left)</span>
                                                                <?php endif; ?>
                                                            </span>
                                                        </div>
                                                    </div>
                                                </div>

                                                <?php if ($is_active): ?>
                                                    <div class="d-flex gap-2 flex-wrap">
                                                        <a href="ebooks.php?voucher=<?php echo urlencode($voucher['code']); ?>" class="btn btn-sm btn-green">
                                                            <i class="bi bi-cart-plus me-1"></i> Use Now
                                                        </a>
                                                        <button class="btn btn-sm btn-outline-secondary"
                                                            onclick="shareVoucher('<?php echo htmlspecialchars($voucher['code']); ?>')">
                                                            <i class="bi bi-share me-1"></i> Share
                                                        </button>
                                                    </div>
                                                <?php elseif ($is_expired): ?>
                                                    <div class="alert alert-danger mb-0 py-2 small">
                                                        <i class="bi bi-exclamation-triangle me-2"></i>This voucher has expired
                                                    </div>
                                                <?php else: ?>
                                                    <div class="alert alert-secondary mb-0 py-2 small">
                                                        <i class="bi bi-check-circle me-2"></i>All uses of this voucher have been redeemed
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card profile-card text-center py-5">
                        <div class="card-body">
                            <div class="mb-4">
                                <i class="bi bi-ticket-perforated" style="font-size: 4rem; color: #dee2e6;"></i>
                            </div>
                            <h4 class="fw-bold mb-3">No Vouchers Yet</h4>
                            <p class="text-muted mb-4">You don't have any vouchers at the moment. Check back later for special offers!</p>
                            <a href="ebooks.php" class="btn btn-green">
                                <i class="bi bi-search me-2"></i> Browse E-Books
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

    <script>
        function copyCode(code, btn) {
            navigator.clipboard.writeText(code).then(() => {
                const originalHTML = btn.innerHTML;
                btn.innerHTML = '<i class="bi bi-check2"></i>';
                btn.classList.remove('btn-outline-secondary');
                btn.classList.add('btn-success');

                setTimeout(() => {
                    btn.innerHTML = originalHTML;
                    btn.classList.remove('btn-success');
                    btn.classList.add('btn-outline-secondary');
                }, 2000);
            }).catch(err => {
                console.error('Failed to copy:', err);
            });
        }

        function shareVoucher(code) {
            const text = `Check out this voucher code: ${code}`;
            if (navigator.share) {
                navigator.share({
                    title: 'BookStack Voucher',
                    text: text
                }).catch(err => console.log('Error sharing:', err));
            } else {
                // Fallback: copy to clipboard
                navigator.clipboard.writeText(code).then(() => {
                    alert('Voucher code copied to clipboard!');
                });
            }
        }
    </script>
</body>

</html>