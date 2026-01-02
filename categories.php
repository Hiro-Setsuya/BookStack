<?php
session_start();
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BookStack - Computer & Tech E-Books</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- navbar -->
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <main class="flex-grow-1 py-5">
        <div class="container-fuid px-4">
            <!-- Page Header -->
            <div class="mb-6">
                <h1 class="display-4 fw-black mb-2 mt-4">Categories</h1>
                <p class="text-muted fs-5">Explore our collection of e-books organized by category</p>
            </div>

            <!-- Categories Grid -->
            <div class="row g-4">
                <!-- Category Card 1 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm category-card">
                        <div class="card-body p-4">
                            <div class="category-icon mb-3">
                                <span class="material-symbols-outlined">code</span>
                            </div>
                            <h4 class="card-title fw-bold">Computer</h4>
                            <p class="text-muted small mb-4">
                                Programming, algorithms, data structures, and software development
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted fw-semibold">245 Books</small>
                                <a href="#" class="btn btn-green btn-sm px-3 rounded-pill">Browse</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Card 2 -->
                <div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm category-card">
                        <div class="card-body p-4">
                            <div class="category-icon mb-3">
                                <span class="material-symbols-outlined">brush</span>
                            </div>
                            <h4 class="card-title fw-bold">Design</h4>
                            <p class="text-muted small mb-4">
                                Designing textbooks, study guides, and learning resources
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted fw-semibold">189 Books</small>
                                <a href="#" class="btn btn-green btn-sm px-3 rounded-pill">Browse</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Category Card 3 --><div class="col-12 col-md-6 col-lg-4">
                    <div class="card h-100 border-0 shadow-sm category-card">
                        <div class="card-body p-4">
                            <div class="category-icon mb-3">
                                <span class="material-symbols-outlined">terminal</span>
                            </div>
                            <h4 class="card-title fw-bold">Language</h4>
                            <p class="text-muted small mb-4">
                                Languange textbooks, study guides, and learning resources
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted fw-semibold">189 Books</small>
                                <a href="#" class="btn btn-green btn-sm px-3 rounded-pill">Browse</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>