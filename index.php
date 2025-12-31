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
  <?php include 'includes/nav.php'; ?>

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
    document.addEventListener('DOMContentLoaded', function() {
      var btn = document.getElementById('userDropdown');
      if (!btn) return;
      var menu = btn.parentElement.querySelector('.dropdown-menu');
      if (!menu) return;

      btn.addEventListener('click', function(e) {
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

      document.addEventListener('click', function(e) {
        if (!btn.contains(e.target) && !menu.contains(e.target)) {
          menu.classList.remove('show');
          btn.setAttribute('aria-expanded', 'false');
        }
      });
    });
  </script>
  <?php include 'includes/footer.php'; ?>
</body>

</html>