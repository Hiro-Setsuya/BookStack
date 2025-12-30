<?php
session_start();
require_once 'config/db.php';

if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?redirect=checkout.php');
  exit;
}

$items_param = $_GET['items'] ?? '';
$item_ids = [];
if (!empty($items_param)) {
  $parts = explode(',', $items_param);
  foreach ($parts as $p) {
    $id = intval(trim($p));
    if ($id > 0) $item_ids[] = $id;
  }
}

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
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <!-- navbar -->
  <nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
    <div class="container-fluid">
      <div class="navbar-brand fw-bold text-green">
        BookStack
      </div>
      <div class="ms-auto">
        <button class="btn btn-green d-lg-none d-inline-block me-2">Login/Register</button>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item px-lg-3">
            <a class="nav-link fw-semibold" href="index.php">
              <i class="bi bi-house-door-fill me-2"></i>Home
            </a>
          </li>
          <li class="nav-item px-lg-3">
            <a class="nav-link fw-semibold" href="ebooks.php">
              <i class="bi bi-book-fill me-2"></i>E-Books
            </a>
          </li>
          <li class="nav-item px-lg-3">
            <a class="nav-link fw-semibold" href="ebook-details.php">
              <i class="bi bi-grid-fill me-2"></i>Categories
            </a>
          </li>
          <li class="nav-item px-lg-3 d-inline-block d-lg-none">
            <a class="nav-link fw-semibold" href="cart.php">
              <i class="bi bi-cart3-fill me-2"></i>Cart
            </a>
          </li>
        </ul>
      </div>
      <div class="ms-auto d-none d-lg-flex">
        <input class="form-control me-2" type="text" placeholder="Search">
        <button class="btn btn-green me-2"><i class="bi bi-cart3"></i></button>
        <button class="btn btn-green">Login/Register</button>
      </div>
    </div>
  </nav>

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

  <div class="container mt-3 px-4">
    <div class="row px-md-5">
      <div class="col px-md-5">
        <div class="card shadow-sm rounded-4 overflow-hidden">
          <div class="card-header fs-4 fw-bold mb-4 py-3 px-3 px-lg-5 px-md-5 text-green">
            <i class="bi bi-credit-card me-2"></i>Payment Method
          </div>
          <div class="card-body px-4 px-md-5">
            <p class="text-center">Complete your purchase securely with PayPal:</p>
            <!-- di pa ito final, inaayos ko pa paypal integ hahaha -->
            <div id="paypal-button-container" class="mt-2 w-100 text-center" style="max-width: 550px;">
            </div>
            <p class="text-muted text-center mt-3 mb-0">
              <small><i class="bi bi-shield-check"></i> Secure payment powered by PayPal</small>
            </p>
            <div class="alert alert-info mt-3 border-0" style="background-color: #9e9e9e50;">
              <i class="bi bi-info-circle"></i> <strong>Note:</strong> This is a test payment interface. Use PayPal sandbox credentials.
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script src="https://www.paypal.com/sdk/js?client-id=AfBypM8JUZ8hoyjQWjI6dC1DZbPb12p675WJw-DPgJH-UcrFeped3spRetRIoh1TChzsiLd09WmeuJfy&currency=PHP"></script>
  <script>
    window.checkoutTotal = <?php echo json_encode(number_format($total, 2, '.', '')); ?>;

    if (typeof paypal !== 'undefined') {
      paypal.Buttons({
        createOrder: function () {
          return fetch('payment/create-order.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' }
          }).then(function (res) {
            if (!res.ok) throw new Error('Network response was not ok');
            return res.json();
          }).then(function (data) {
            if (!data.id) throw new Error('No order ID returned');
            return data.id;
          }).catch(function (err) {
            console.error('Create order error:', err);
            alert('Failed to create order. Check console.');
          });
        },
        onApprove: function (data) {
          return fetch('payment/capture-order.php?orderID=' + data.orderID)
            .then(function (res) { return res.json(); })
            .then(function (details) {
              if (details.status === 'success') {
                var payerName = (details.payer && details.payer.name && details.payer.name.given_name) ? details.payer.name.given_name : 'customer';
                alert('Payment completed successfully by ' + payerName + '!');
                window.location.href = 'orders.php?payment=success&orderID=' + encodeURIComponent(details.orderID || data.orderID);
              } else {
                console.error('Capture failed', details);
                alert('Payment capture failed. See console for details.');
              }
            }).catch(function (err) {
              console.error('Capture error:', err);
              alert('Payment capture failed.');
            });
        },
        onError: function (err) {
          console.error('PayPal Button Error:', err);
          alert('An error occurred with PayPal. Please try again.');
        },
        onCancel: function (data) {
          alert('Payment cancelled.');
        }
      }).render('#paypal-button-container');
    } else {
      document.getElementById('paypal-button-container').innerHTML = '<p class="text-danger">PayPal SDK failed to load.</p>';
    }
  </script>
</body>

</html>