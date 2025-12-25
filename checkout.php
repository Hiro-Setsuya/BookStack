<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="assets/css/style.css">
</head>

<body>
  <!-- navbar -->
  <nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
    <div class="container-fluid">
      <div class="navbar-brand fw-bold text-success">
        BookStack
      </div>
      <div class="ms-auto">
        <button class="btn btn-success d-lg-none d-inline-block me-2">Login/Register</button>
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
        <button class="btn btn-success me-2"><i class="bi bi-cart3"></i></button>
        <button class="btn btn-success">Login/Register</button>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <div class="row">
      <div class="col text-center mx-2">
        <div class="mx-auto d-lg-none d-flex mt-4">
          <input class="form-control me-2" type="text" placeholder="Search">
          <button class="btn btn-success"><i class="bi bi-search"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-3 mt-lg-5">
    <div class="row">
      <div class="col">
        <div class="h2 fw-bold">Checkout</div>
        <div class="text-muted">Complete your purchase to access your ebook instantly.</div>
      </div>
    </div>
  </div>

  <div class="container mt-4 px-3">
    <div class="row">
      <div class="col-lg-8 mb-3">

        <div class="card shadow-sm rounded-4 p-5 mb-4">
          <div class="h5 fw-semibold mb-4">Contact Information</div>

          <div class="mb-3">
            <div class="card-text mb-1">Email Address</div>
            <input class="form-control" type="text" placeholder="sample@gmail.com">
          </div>

          <div class="mb-3">
            <div class="row">
              <div class="col">
                <div class="card-text mb-1">First Name</div>
                <input class="form-control" type="text" placeholder="Rowel Gabriel">
              </div>
              <div class="col">
                <div class="card-text mb-1">Last Name</div>
                <input class="form-control" type="text" placeholder="Mangabat">
              </div>
            </div>
          </div>

          <div class="mb-3">
            <div class="card-text mb-1">Institutio / School ID (optional)</div>
            <input class="form-control" type="text" placeholder="sample@gmail.com">
          </div>

        </div>

        <div class="card shadow-sm rounded-4 p-5">
          <div class="h5 fw-semibold mb-4">Payment Method</div>

          <div class="mb-3">
            <div class="row g-3">
              <div class="col-6">
                <input type="radio" class="btn-check" name="options-base" id="creditcard" autocomplete="off" checked>
                <label class="btn p-3 bg-light shadow-sm w-100 text-start" for="creditcard">
                  <span class="fw-semibold">Credit Card</span>
                  <span class="text-muted d-block">Visa, Mastercard, Amex</span>
                </label>
              </div>

              <div class="col-6">
                <input type="radio" class="btn-check" name="options-base" id="paypal" autocomplete="off">
                <label class="btn p-3 bg-light shadow-sm w-100 text-start" for="paypal">
                  <span class="fw-semibold">PayPal</span>
                  <span class="text-muted d-block">Fast & Secure Checkout</span>
                </label>
              </div>
            </div>
          </div>

          <div class="mb-3">
            <div class="card-text mb-1">Card Number</div>
            <input class="form-control" type="text" placeholder="0000 0000 0000 0000">
          </div>

          <div class="mb-3">
            <div class="row">
              <div class="col">
                <div class="card-text mb-1">Expiration</div>
                <input class="form-control" type="text" placeholder="MM/YY">
              </div>
              <div class="col">
                <div class="card-text mb-1">CVC</div>
                <input class="form-control" type="text" placeholder="123">
              </div>
            </div>
          </div>

        </div>
      </div>

      <div class="col-lg-4 mb-3 sticky-summary">
        <div class="card p-3 position-sticky" style="top: 90px;">
          <div class="card-title fs-4 fw-bold mb-3">Order Summary</div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fs-6 fw-semibold">Subtotal</div>
            <div class="fs-6">₱100.00</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3 text-success">
            <div class="fs-6 fw-semibold">Discount (30%)</div>
            <div class="fs-6">₱30.00</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fs-6 fw-semibold">Tax (Estimated)</div>
            <div class="fs-6">₱0.00</div>
          </div>
          <div class="d-flex justify-content-between align-items-center border-top border-2 mb-3">
            <div class="fs-5 fw-bold">Total</div>
            <div class="fs-5 fw-semibold">₱70.00</div>
          </div>
          <div class="fs-6 d-block ms-2">Promo Code</div>
          <div class="d-flex mb-5">
            <input class="form-control me-2" type="text" placeholder="Enter Code">
            <button class="btn btn-sm btn-success">Apply</button>
          </div>
          <a class="btn btn-success" href="checkout.php">Proceed to Checkout</a>
        </div>
      </div>

    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>