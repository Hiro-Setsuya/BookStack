<?php
session_start();
?>
<!doctype html>
<html lang="en">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>BookStack - Computer & Tech E-Books</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body>
  <!-- navbar -->
  <nav id="navbar" class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
    <div class="container-fluid">
      <div class="navbar-brand fw-bold text-green">
        <img src="assets/logo.svg" height="25" alt="Logo">
        <span>BookStack</span>
      </div>
      <div class="ms-auto">
        <?php if (isset($_SESSION['user_id'])): ?>
          <a href="profile.php" class="btn btn-green d-lg-none d-inline-block me-2">
            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
          </a>
        <?php else: ?>
          <a href="login.php" class="btn btn-green d-lg-none d-inline-block me-2">Sign In</a>
        <?php endif; ?>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
          aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>
      </div>
      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item px-lg-3">
            <a class="nav-link fw-semibold text-green" href="index.php">
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
      <div class="ms-auto d-none d-lg-flex align-items-center">
        <input class="form-control me-2" type="text" placeholder="Search tech books..." style="width: 200px;">
        <a href="cart.php" class="btn btn-green me-2"><i class="bi bi-cart3"></i></a>
        <?php if (isset($_SESSION['user_id'])): ?>
          <div class="dropdown">
            <button id="userDropdown" class="btn btn-green dropdown-toggle text-nowrap" type="button" data-bs-toggle="dropdown" aria-expanded="false">
              <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
              <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
              <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag me-2"></i>Orders</a></li>
              <li>
                <hr class="dropdown-divider">
              </li>
              <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
          </div>
        <?php else: ?>
          <a href="login.php" class="btn btn-green text-nowrap">Sign In</a>
        <?php endif; ?>
      </div>
    </div>
  </nav>

  <div class="container mt-5">
    <div class="row">
      <div class="col text-center mx-2">
        <div class="mx-auto d-lg-none d-flex mt-4">
          <input class="form-control me-2" type="text" placeholder="Search tech books...">
          <button class="btn btn-green"><i class="bi bi-search"></i></button>
        </div>
      </div>
    </div>
  </div>

  <div class="container mt-lg-5 py-4 px-4">
    <div class="row">
      <div class="col-lg-7">
        <div class="display-3 fw-bold">Learn Tech Skills with</div>
        <div class="display-3 fw-bold text-green">Expert E-Books</div>
        <div class="fs-5 mt-3 text-muted">
          Access thousands of computer science, programming, and tech e-books. From web development to AI, find resources to advance your career.
        </div>
        <div class="mt-4 mb-4">
          <a href="ebooks.php" class="btn btn-green me-2"><i class="bi bi-laptop me-2"></i>Browse Tech Books</a>
          <a href="#how-it-works" class="btn btn-outline-green"><i class="bi bi-info-circle me-2"></i>How It Works</a>
        </div>
      </div>
      <div class="col-lg-5">
        <img src="https://tse4.mm.bing.net/th/id/OIP.4vlJD00w2dCppW1ZYkrb8gHaFI?cb=ucfimg2&ucfimg=1&rs=1&pid=ImgDetMain&o=7&rm=3"
          class="img-fluid rounded-3 mb-2 shadow">
        <div class="fs-5 fw-semibold">Popular Topics</div>
        <div class="text-muted">Web Dev • Python • Data Science • AI</div>
      </div>
    </div>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
  <?php include 'chatbot/chatbot.php'; ?>
  <script>
    // Lightweight dropdown toggle fallback (index-only)
    document.addEventListener('DOMContentLoaded', function () {
      var btn = document.getElementById('userDropdown');
      if (!btn) return;
      var menu = btn.parentElement.querySelector('.dropdown-menu');
      if (!menu) return;

      btn.addEventListener('click', function (e) {
        e.preventDefault();
        var shown = menu.classList.contains('show');
        if (shown) {
          menu.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        } else {
          menu.classList.add('show');
          btn.setAttribute('aria-expanded', 'true');
        }
      });

      document.addEventListener('click', function (e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
          menu.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    });
  </script>
</body>

</html>