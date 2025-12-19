<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap Demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
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
            <a class="nav-link fw-semibold text-success" href="index.php">
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

  <div class="container mt-lg-5 py-4 px-4">
    <div class="row">
      <div class="col-lg-7">
        <div class="display-3 fw-bold">Buy and Download</div>
        <div class="display-3 fw-bold text-success">E-Books Instantly</div>
        <div class="fs-5 mt-3">
          Explore a wide collection of books across multiple categories. With BookStack, you can easily discover new titles, read your favorites, and organize your personal library all in one place.”
        </div>
        <div class="mt-4 mb-4">
          <button class="btn btn-success me-2"><i class="bi bi-cart3 me-2"></i>Browse Shop</button>
          <button class="btn btn-outline-success"><i class="bi bi-info-circle me-2"></i>How it Works</button>
        </div>
      </div>
      <div class="col-lg-5">
        <img src="https://tse4.mm.bing.net/th/id/OIP.4vlJD00w2dCppW1ZYkrb8gHaFI?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3"
          class="img-fluid rounded-3 mb-2">
        <div class="fs-5 fw-semibold">Featued Collection</div>
        <div class="fs-5">Best Sellers of the Month.</div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
</body>

</html>