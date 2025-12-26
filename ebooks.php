<?php
session_start();
require_once 'config/db.php';

// Fetch ebooks from database
$query = "SELECT ebook_id, title, author, price, cover_image, file_path FROM ebooks ORDER BY created_at DESC";
$result = executeQuery($query);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Books - BookStack</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- navbar -->
    <nav class="navbar navbar-expand-lg shadow-sm fixed-top px-sm-4 px-1 py-2 bg-light">
        <div class="container-fluid">
            <a href="index.php" class="navbar-brand fw-bold text-success">
                <img src="assets/logo.svg" height="25" alt="Logo">
                <span>BookStack</span>
            </a>
            <div class="ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-success d-lg-none d-inline-block me-2">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-success d-lg-none d-inline-block me-2">Sign In</a>
                <?php endif; ?>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
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
                        <a class="nav-link fw-semibold text-success" href="ebooks.php">
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
                <a href="cart.php" class="btn btn-success me-2"><i class="bi bi-cart3"></i></a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-success dropdown-toggle text-nowrap" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profile</a></li>
                            <li><a class="dropdown-item" href="orders.php"><i class="bi bi-bag me-2"></i>Orders</a></li>
                            <li>
                                <hr class="dropdown-divider">
                            </li>
                            <li><a class="dropdown-item" href="logout.php"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                <?php else: ?>
                    <a href="login.php" class="btn btn-success text-nowrap">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-5 pt-5">
        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">Tech E-Books</h2>
                <p class="text-muted">Browse our collection of programming and computer science books</p>
            </div>
        </div>

        <div class="row g-4">
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                while ($ebook = mysqli_fetch_assoc($result)) {
            ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card ebook-card shadow-sm border-0">
                            <img src="<?php echo htmlspecialchars($ebook['cover_image'] ?? 'assets/img/ebook_cover/default.jpg'); ?>"
                                class="card-img-top ebook-cover"
                                alt="<?php echo htmlspecialchars($ebook['title']); ?>">
                            <div class="card-body d-flex flex-column">
                                <h5 class="card-title ebook-title fw-semibold">
                                    <?php echo htmlspecialchars($ebook['title']); ?>
                                </h5>
                                <p class="card-text ebook-author mb-2">
                                    <i class="bi bi-person me-1"></i><?php echo htmlspecialchars($ebook['author'] ?? 'Unknown'); ?>
                                </p>
                                <div class="mt-auto">
                                    <div class="d-flex justify-content-between align-items-center mb-3">
                                        <span class="h5 mb-0 text-success fw-bold">₱<?php echo number_format($ebook['price'], 2); ?></span>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-outline-success btn-sm">
                                            <i class="bi bi-eye me-1"></i>View Details
                                        </a>
                                        <a href="download.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-primary btn-sm">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                        <button class="btn btn-success btn-sm">
                                            <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php
                }
            } else {
                ?>
                <div class="col-12">
                    <div class="alert alert-info text-center">
                        <i class="bi bi-info-circle me-2"></i>No e-books available at the moment.
                    </div>
                </div>
            <?php
            }
            ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>