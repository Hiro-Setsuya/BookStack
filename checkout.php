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
        <div class="card shadow-sm rounded-4 p-4 p-md-5">
          <div class="card-title fs-4 fw-bold mb-4">Order Summary</div>
          <div class="mb-4">
            <div class="d-flex gap-3">
              <img src="https://m.media-amazon.com/images/I/519LoIgUAFL.jpg" height="120" class="flex-shrink-0">
              <div class="flex-grow-1 overflow-hidden">
                <div class="fs-5 fw-bold text-truncate">Sample Book 1</div>
                <div class="text-muted text-truncate">Sample Author 1</div>
              </div>
              <div class="fw-semibold text-nowrap">₱100.00</div>
            </div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="fs-6 fw-semibold">Subtotal</div>
            <div class="fs-6">₱100.00</div>
          </div>
          <div class="d-flex justify-content-between align-items-center mb-3 text-green">
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
            <div id="paypal-button-container" class="mt-2 w-100 px-md-5">
              <button class="btn-green w-100 rounded-3 py-2">
                <img src="assets/paypal.svg" class="img-fluid paypal-logo">
              </button>
              <div class="alert alert-info mt-3 border-0" style="background-color: #9e9e9e50;">
                <i class="bi bi-info-circle"></i> <strong>Note:</strong> This is a test payment interface. Use PayPal sandbox credentials.
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>