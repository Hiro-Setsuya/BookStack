<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?redirect=checkout.php');
  exit;
}

// Check if user account is verified
$user_id = $_SESSION['user_id'];
$verify_query = "SELECT is_account_verified FROM users WHERE user_id = $user_id";
$verify_result = executeQuery($verify_query);
$user_data = mysqli_fetch_assoc($verify_result);

if (!$user_data || !$user_data['is_account_verified']) {
  $_SESSION['error_message'] = 'Your account must be verified before you can purchase e-books. Please verify your account first.';
  header('Location: profile.php');
  exit;
}

// Handle promo code submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'apply_promo') {
  $_SESSION['promo_code'] = $_POST['promo_code'] ?? '';
  // Redirect back to checkout with same parameters
  $redirect_url = 'checkout.php?';
  if (isset($_GET['id']) && isset($_GET['buy_now'])) {
    $redirect_url .= 'id=' . intval($_GET['id']) . '&buy_now=1';
  } elseif (isset($_GET['items'])) {
    $redirect_url .= 'items=' . urlencode($_GET['items']);
  }
  header('Location: ' . $redirect_url);
  exit;
}

// Handle both "Buy Now" (single item) and cart checkout (multiple items)
$item_ids = [];

// Check if this is a "Buy Now" direct purchase
if (isset($_GET['id']) && isset($_GET['buy_now'])) {
  $item_ids[] = intval($_GET['id']);
}
// Otherwise, check for cart items
else {
  $items_param = $_GET['items'] ?? '';
  if (!empty($items_param)) {
    $parts = explode(',', $items_param);
    foreach ($parts as $p) {
      $id = intval(trim($p));
      if ($id > 0) $item_ids[] = $id;
    }
  }
}

// Store items in session for payment processing
$_SESSION['checkout_items'] = $item_ids;

if (empty($item_ids)) {
  header('Location: cart.php');
  exit;
}

$ids_list = implode(',', array_map('intval', $item_ids));
$query = "SELECT ebook_id, title, author, price, cover_image FROM ebooks WHERE ebook_id IN ($ids_list)";
$result = executeQuery($query);

$items = [];
$subtotal = 0.0;
if ($result && mysqli_num_rows($result) > 0) {
  while ($row = mysqli_fetch_assoc($result)) {
    $row['quantity'] = 1;
    $row['total'] = $row['price'];
    $subtotal += $row['total'];
    $items[] = $row;
  }
}

$discount_percent = 0;
$discount_amount = 0;
if (isset($_SESSION['promo_code']) && $_SESSION['promo_code'] === 'TECH30') {
  $discount_percent = 30;
  $discount_amount = $subtotal * ($discount_percent / 100);
}

$taxable_amount = $subtotal - $discount_amount;
$tax_rate = 0.12;
$tax = $taxable_amount * $tax_rate;
$total = $subtotal - $discount_amount + $tax;

$_SESSION['checkout_total'] = number_format($total, 2, '.', '');

?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <!-- navbar -->
  <?php include 'includes/nav.php'; ?>

  <div class="container mt-5">
    <div class="row">
      <div class="col text-center mx-2">
        <div class="mx-auto d-lg-none d-flex mt-4">
          <input class="form-control me-2" type="text" placeholder="Search">
          <button class="btn btn-green"><i class="bi bi-search"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-3 mt-lg-5 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="h2 fw-bold">Checkout</div>
        <div class="text-muted">Complete your purchase to access your ebook instantly.</div>
      </div>
    </div>
  </div>

  <div class="container mt-3 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="card shadow-sm rounded-4 overflow-hidden">
          <div class="card-header fs-4 fw-bold mb-4 py-3 px-3 px-lg-5 px-md-5 text-green">
            Order Summary
          </div>
          <div class="card-body px-4 px-md-5">
            <?php if (empty($items)): ?>
              <div class="alert alert-info">No items found for checkout.</div>
            <?php else: ?>
              <?php foreach ($items as $it): ?>
                <div class="d-flex gap-3 mb-4">
                  <img src="<?php echo htmlspecialchars($it['cover_image'] ?? 'assets/img/ebook_cover/default.jpg'); ?>" height="120" class="flex-shrink-0">
                  <div class="flex-grow-1 overflow-hidden">
                    <div class="fs-5 fw-bold text-truncate"><?php echo htmlspecialchars($it['title']); ?></div>
                    <div class="text-muted text-truncate"><?php echo htmlspecialchars($it['author'] ?? 'Unknown'); ?></div>
                  </div>
                  <div class="fw-semibold text-nowrap">₱<?php echo number_format($it['price'], 2); ?></div>
                </div>
              <?php endforeach; ?>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fs-6 fw-semibold">Subtotal</div>
                <div class="fs-6">₱<?php echo number_format($subtotal, 2); ?></div>
              </div>

              <?php if ($discount_percent > 0): ?>
                <div class="d-flex justify-content-between align-items-center mb-3 text-green">
                  <div class="fs-6 fw-semibold">Discount (<?php echo $discount_percent; ?>%)</div>
                  <div class="fs-6">-₱<?php echo number_format($discount_amount, 2); ?></div>
                </div>
              <?php endif; ?>

              <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="fs-6 fw-semibold">Tax (Estimated)</div>
                <div class="fs-6">₱<?php echo number_format($tax, 2); ?></div>
              </div>

              <div class="d-flex justify-content-between align-items-center border-top border-2 mb-3">
                <div class="fs-5 fw-bold">Total</div>
                <div class="fs-5 fw-semibold">₱<?php echo number_format($total, 2); ?></div>
              </div>
            <?php endif; ?>
          </div>

        </div>
      </div>
    </div>
  </div>

  <!-- Promo Code Section -->
  <div class="container mt-3 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="card shadow-sm rounded-4 overflow-hidden">
          <div class="card-header fs-4 fw-bold mb-4 py-3 px-3 px-lg-5 px-md-5 text-green">
            <i class="bi bi-tag me-2"></i>Promo Code
          </div>
          <div class="card-body px-4 px-md-5">
            <form method="POST" action="">
              <input type="hidden" name="action" value="apply_promo">
              <div class="row g-3 align-items-end">
                <div class="col-md-8">
                  <label class="form-label fw-semibold">Have a promo code?</label>
                  <input class="form-control py-2" type="text" name="promo_code" placeholder="Enter promo code" value="<?php echo htmlspecialchars($_SESSION['promo_code'] ?? ''); ?>" style="border-radius: 12px; border: 1px solid rgba(46, 204, 113, 0.3);">
                </div>
                <div class="col-md-4">
                  <button type="submit" class="btn btn-green w-100 py-2" style="border-radius: 12px; font-weight: 600;">
                    <i class="bi bi-check-circle me-1"></i>Apply
                  </button>
                </div>
              </div>
              <?php if (isset($_SESSION['promo_code']) && $_SESSION['promo_code'] === 'TECH30'): ?>
                <div class="alert alert-success mt-3 mb-0" role="alert">
                  <i class="bi bi-check-circle-fill me-2"></i>Promo code <strong><?php echo htmlspecialchars($_SESSION['promo_code']); ?></strong> applied successfully! You're saving <?php echo $discount_percent; ?>%.
                </div>
              <?php elseif (isset($_SESSION['promo_code']) && !empty($_SESSION['promo_code'])): ?>
                <div class="alert alert-warning mt-3 mb-0" role="alert">
                  <i class="bi bi-exclamation-triangle me-2"></i>Invalid promo code. Please try again.
                </div>
              <?php endif; ?>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-3 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="card shadow-sm rounded-4 overflow-hidden">
          <div class="card-header fs-4 fw-bold mb-4 py-3 px-3 px-lg-5 px-md-5 text-green">
            <i class="bi bi-credit-card me-2"></i>Payment Method
          </div>
          <div class="card-body px-4 px-md-5">
            <p class="text-center">Complete your purchase securely with PayPal:</p>
            <div class="d-flex justify-content-center">
              <div id="paypal-button-container" class="mt-2 w-100" style="max-width: 550px;">
              </div>
            </div>
            <p class="text-muted text-center mt-3 mb-0">
              <small><i class="bi bi-shield-check"></i> Secure payment powered by PayPal</small>
            </p>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-3 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="alert alert-info border-0" style="background-color: #9e9e9e50;">
          <i class="bi bi-info-circle"></i> <strong>Note:</strong> This is a test payment interface. Use PayPal sandbox credentials.
        </div>
      </div>
    </div>
  </div>
  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="https://www.paypal.com/sdk/js?client-id=AfBypM8JUZ8hoyjQWjI6dC1DZbPb12p675WJw-DPgJH-UcrFeped3spRetRIoh1TChzsiLd09WmeuJfy&currency=PHP"></script>
  <script>
    window.checkoutTotal = <?php echo json_encode(number_format($total, 2, '.', '')); ?>;

    if (typeof paypal !== 'undefined') {
      paypal.Buttons({
        createOrder: function() {
          return fetch('payment/create-order.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/x-www-form-urlencoded'
            }
          }).then(function(res) {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
          }).then(function(data) {
            if (!data.id) throw new Error('No order ID returned');
            return data.id;
          }).catch(function(err) {
            console.error('Create order error:', err);
            alert('Failed to create order. Check console.');
          });
        },
        onApprove: function(data) {
          return fetch('payment/capture-order.php?orderID=' + data.orderID)
            .then(function(res) {
              return res.json();
            })
            .then(function(details) {
              if (details.status === 'success') {
                var payerName = (details.payer && details.payer.name && details.payer.name.given_name) ? details.payer.name.given_name : 'customer';
                alert('Payment completed successfully! Redirecting to your e-books...');
                window.location.href = 'my-ebooks.php?payment=success&orderID=' + encodeURIComponent(details.orderID || data.orderID);
              } else {
                console.error('Capture failed', details);
                alert('Payment capture failed. See console for details.');
              }
            }).catch(function(err) {
              console.error('Capture error:', err);
              alert('Payment capture failed.');
            });
        },
        onError: function(err) {
          console.error('PayPal Button Error:', err);
          alert('An error occurred with PayPal. Please try again.');
        },
        onCancel: function(data) {
          alert('Payment cancelled.');
        }
      }).render('#paypal-button-container');
    } else {
      document.getElementById('paypal-button-container').innerHTML = '<p class="text-danger">PayPal SDK failed to load.</p>';
    }
  </script>
</body>

</html>