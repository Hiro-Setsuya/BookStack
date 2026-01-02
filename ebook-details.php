<!DOCTYPE html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>E-Book Details - EduBooks</title>

  <!-- Google Fonts: Manrope -->
  <link rel="preconnect" href="https://fonts.googleapis.com" />
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

  <!-- Material Symbols -->
  <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,0..200" rel="stylesheet" />

  <!-- Bootstrap 5 CSS -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
  <link rel="stylesheet" href="style.css">
</head>

<body class="font-sans antialiased">
  <div class="d-flex flex-column min-vh-100">
    <!-- Top Navigation -->
    <?php include 'includes/nav.php' ?>
    <!-- Main Content -->
    <main class="flex-grow-1 py-5">
      <div class="container">
        <!-- Breadcrumbs -->
        <nav aria-label="breadcrumb" class="mb-4">
          <ol class="breadcrumb mb-0">
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Home</a></li>
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Textbooks</a></li>
            <li class="breadcrumb-item"><a href="#" class="text-decoration-none">Computer Science</a></li>
            <li class="breadcrumb-item active fw-semibold" aria-current="page">Introduction to Algorithms, 4th Edition</li>
          </ol>
        </nav>

        <!-- Product Hero -->
        <div class="row g-5 mb-5">
          <!-- Left: Image -->
          <div class="col-lg-4">
            <div class="position-relative border rounded-3 overflow-hidden" style="aspect-ratio: 3/4; background-color: #ffffff;">
              <div class="w-100 h-100 book-cover" style="background-image: url('https://lh3.googleusercontent.com/aida-public/AB6AXuCFFW8znAfEJu1SPsJuZy_bT8MLaVfqxiK-Jej9UTEG8MSjYkurk4m8ss6ZEYGPiFYJiyH-EcUXC-Ews4s68KKP8RtxlgScgnlu8VVFxNCacTo7Wnzh13cU4z0HBiw0RpFILmjdJGxqOyA1_mMnrY4XVUz0EVTIv7saInHd96IXd2AmCpNPqgZR0857h3a5KydlFEFybWr16JemI1PXQpbswOAoSRAuJAe8PGzTAkrAQC0qxwy0Te1M5sJ_xIlEw56jWF5LTqLsw5I');"></div>
              <span class="position-absolute top-3 start-3 badge bg-info text-primary fw-bold">Best Seller</span>
            </div>
            <button class="btn w-100 mt-3 d-flex align-items-center justify-content-center gap-2" style="border: 2px solid #2ecc71; color: #2ecc71; font-weight: 600;">
              <span class="material-symbols-outlined">menu_book</span> Read Free Sample
            </button>
          </div>

          <!-- Right: Details -->
          <div class="col-lg-8">
            <h1 class="display-5 fw-black mb-2" style="color: #0d121c;">Introduction to Algorithms, 4th Edition</h1>
            <p style="color: #2ecc71; font-weight: 600;" class="mb-3">By Thomas H. Cormen, Charles E. Leiserson, Ronald L. Rivest, and Clifford Stein</p>

            <!-- Rating -->
            <div class="d-flex align-items-center gap-2 mb-4">
              <div class="text-warning">
                ★★★★☆
              </div>
              <small class="text-muted">(4.8/5 based on 1,204 reviews)</small>
            </div>

            <!-- Pricing Card -->
            <div class="card p-4 mb-5" style="border-top: 4px solid #2ecc71; box-shadow: 0 2px 8px rgba(46, 204, 113, 0.1);">
              <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-4">
                <div>
                  <div class="d-flex align-items-baseline gap-2">
                    <span class="display-6 fw-black">$45.00</span>
                    <span class="text-decoration-line-through text-muted">$89.00</span>
                  </div>
                  <p class="text-success fw-semibold mb-0">Save 49% • Instant Download</p>
                </div>
              </div>

              <!-- Format Selector -->
              <div class="mb-4">
                <label class="form-label fw-bold">Select Format:</label>
                <div class="row g-2">
                  <div class="col-6 col-sm-3">
                    <div class="format-option active">
                      <span class="position-absolute top-2 end-2 badge text-white" style="font-size: 0.6rem; background-color: #2ecc71;">Best</span>
                      <div class="fw-bold" style="color: #2ecc71;">ePub + PDF</div>
                      <div style="color: #2ecc71;">$45.00</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-3">
                    <div class="format-option">
                      <div class="fw-bold">PDF Only</div>
                      <div class="text-muted">$40.00</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-3">
                    <div class="format-option">
                      <div class="fw-bold">Audiobook</div>
                      <div class="text-muted">$25.00</div>
                    </div>
                  </div>
                  <div class="col-6 col-sm-3">
                    <div class="format-option opacity-50" style="background-color: #f8f9fa;">
                      <div class="fw-bold text-muted">Print</div>
                      <div class="text-muted">Out of Stock</div>
                    </div>
                  </div>
                </div>
              </div>

              <!-- Action Buttons -->
              <div class="d-grid gap-2 d-sm-flex">
                <button class="btn d-flex align-items-center justify-content-center gap-2" style="background-color: #2ecc71; border-color: #2ecc71; color: white; font-weight: 600;">
                  <span class="material-symbols-outlined">shopping_cart</span> Add to Cart
                </button>
                <button class="btn" style="border: 2px solid #2ecc71; color: #2ecc71; font-weight: 600;">Buy Now</button>
              </div>

              <div class="text-center mt-3 small text-muted">
                <div class="d-inline-flex align-items-center gap-2 mx-2">
                  <span class="material-symbols-outlined text-success">check_circle</span> Instant Access
                </div>
                <div class="d-inline-flex align-items-center gap-2 mx-2">
                  <span class="material-symbols-outlined text-success">lock</span> Secure Payment
                </div>
                <div class="d-inline-flex align-items-center gap-2 mx-2">
                  <span class="material-symbols-outlined text-success">verified</span> Official Publisher
                </div>
              </div>
            </div>

            <!-- Description -->
            <p class="text-muted">
              A comprehensive update of the leading algorithms text, with new material on matchings in bipartite graphs, online algorithms, machine learning, and other topics...
            </p>
            <a href="#details" class="text-primary fw-bold text-decoration-underline small">Read full description ↓</a>
          </div>
        </div>

        <!-- Tabs -->
        <div id="details" class="mb-5">
          <ul class="nav nav-tabs mb-4">
            <li class="nav-item">
              <a class="nav-link active fw-bold" href="#">Description</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Table of Contents</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">About the Author</a>
            </li>
            <li class="nav-item">
              <a class="nav-link" href="#">Reviews <span class="badge" style="background-color: #2ecc71;">1.2k</span></a>
            </li>
          </ul>

          <div class="row g-4">
            <div class="col-md-8">
              <p class="text-muted">
                Some books on algorithms are rigorous but incomplete; others cover masses of material but lack rigor...
              </p>
              <ul class="text-muted">
                <li>New chapters on matchings in bipartite graphs...</li>
                <li>New material on topics including solving recurrence equations...</li>
                <li>140 new exercises and 22 new problems.</li>
              </ul>
            </div>
            <div class="col-md-4">
              <div class="card p-4 h-100">
                <h5 class="fw-bold mb-3">Book Details</h5>
                <dl class="row mb-0 text-sm">
                  <dt class="col-6 text-muted">ISBN-13</dt>
                  <dd class="col-6 fw-medium">978-0262046305</dd>
                  <dt class="col-6 text-muted">Publisher</dt>
                  <dd class="col-6 fw-medium">MIT Press</dd>
                  <dt class="col-6 text-muted">Publication Date</dt>
                  <dd class="col-6 fw-medium">April 5, 2022</dd>
                  <dt class="col-6 text-muted">Pages</dt>
                  <dd class="col-6 fw-medium">1312 pages</dd>
                  <dt class="col-6 text-muted">Language</dt>
                  <dd class="col-6 fw-medium">English</dd>
                </dl>
              </div>
            </div>
          </div>
        </div>

        <!-- Related Books -->
        <section class="mb-5">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4">Frequently bought together</h2>
            <a href="#" class="text-primary fw-bold text-decoration-underline">View all</a>
          </div>
          <div class="row g-4">
            <!-- Repeat 4–5 cards as needed -->
            <div class="col-6 col-md-3">
              <div class="card h-100 border">
                <div class="ratio ratio-2x3 mb-2">
                  <div class="book-cover" style="background-image: url('https://lh3.googleusercontent.com/...');"></div>
                </div>
                <h6 class="fw-bold text-truncate">The Pragmatic Programmer</h6>
                <p class="text-muted small">David Thomas</p>
                <div class="d-flex justify-content-between align-items-center">
                  <span class="fw-bold text-primary">$32.00</span>
                  <button class="btn btn-link p-0 text-muted"><span class="material-symbols-outlined">add_circle</span></button>
                </div>
              </div>
            </div>
            <!-- Add more as needed -->
          </div>
        </section>
      </div>
    </main>
  </div>

  <?php include 'includes/footer.php'; ?>

  <!-- Optional: Bootstrap JS (for dropdowns, modals, etc.) -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>