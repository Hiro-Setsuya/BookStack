<?php
session_start();
require_once 'config/db.php';

// Handle Add to Cart
$message = '';
$message_type = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_to_cart'])) {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php?redirect=ebooks.php');
        exit;
    }

    $user_id = $_SESSION['user_id'];
    $ebook_id = intval($_POST['ebook_id']);

    // Check if item already exists in cart
    $check_stmt = $conn->prepare("SELECT cart_id FROM cart_items WHERE user_id = ? AND ebook_id = ?");
    $check_stmt->bind_param("ii", $user_id, $ebook_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $message = 'This item is already in your cart!';
        $message_type = 'warning';
    } else {
        // Add to cart
        $insert_stmt = $conn->prepare("INSERT INTO cart_items (user_id, ebook_id) VALUES (?, ?)");
        $insert_stmt->bind_param("ii", $user_id, $ebook_id);

        if ($insert_stmt->execute()) {
            $message = 'Item added to cart successfully!';
            $message_type = 'success';
        } else {
            $message = 'Failed to add item to cart.';
            $message_type = 'danger';
        }
        $insert_stmt->close();
    }
    $check_stmt->close();
}

// Get cart count for navbar badge
$cart_count = 0;
if (isset($_SESSION['user_id'])) {
    $count_stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $count_stmt->bind_param("i", $_SESSION['user_id']);
    $count_stmt->execute();
    $count_result = $count_stmt->get_result();
    $cart_count = $count_result->fetch_assoc()['count'];
    $count_stmt->close();
}

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
            <a href="index.php" class="navbar-brand fw-bold text-green">
                <img src="assets/logo.svg" height="25" alt="Logo">
                <span>BookStack</span>
            </a>
            <div class="ms-auto">
                <?php if (isset($_SESSION['user_id'])): ?>
                    <a href="profile.php" class="btn btn-green d-lg-none d-inline-block me-2">
                        <i class="bi bi-person-circle me-1"></i><?php echo htmlspecialchars($_SESSION['user_name']); ?>
                    </a>
                <?php else: ?>
                    <a href="login.php" class="btn btn-green d-lg-none d-inline-block me-2 fw-normal">Sign In</a>
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
                        <a class="nav-link fw-semibold text-green" href="ebooks.php">
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
                <a href="cart.php" class="btn btn-green me-2 position-relative">
                    <i class="bi bi-cart3"></i>
                    <?php if ($cart_count > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.65rem;">
                            <?php echo $cart_count; ?>
                        </span>
                    <?php endif; ?>
                </a>
                <?php if (isset($_SESSION['user_id'])): ?>
                    <div class="dropdown">
                        <button class="btn btn-green dropdown-toggle text-nowrap" type="button" data-bs-toggle="dropdown">
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
                    <a href="login.php" class="btn btn-green text-nowrap fw-normal">Sign In</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>

    <!-- Content -->
    <div class="container mt-5 pt-5">
        <?php if ($message): ?>
            <div class="alert alert-<?php echo $message_type; ?> alert-dismissible fade show" role="alert">
                <i class="bi bi-<?php echo $message_type === 'success' ? 'check-circle' : ($message_type === 'warning' ? 'exclamation-triangle' : 'x-circle'); ?> me-2"></i>
                <?php echo htmlspecialchars($message); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <div class="row mb-4">
            <div class="col">
                <h2 class="fw-bold">Tech E-Books</h2>
                <p class="text-muted">Browse our collection of programming and computer science books</p>
            </div>
        </div>

        <div class="row g-4 px-4">
            <?php
            if ($result && mysqli_num_rows($result) > 0) {
                while ($ebook = mysqli_fetch_assoc($result)) {
            ?>
                    <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                        <div class="card ebook-card shadow-sm border-0 h-100">
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
                                        <span class="h5 mb-0 text-green fw-bold">₱<?php echo number_format($ebook['price'], 2); ?></span>
                                    </div>
                                    <div class="d-grid gap-2">
                                        <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-outline-green btn-sm">
                                            <i class="bi bi-eye me-1"></i>View Details
                                        </a>
                                        <a href="download.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-green btn-sm">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="ebook_id" value="<?php echo $ebook['ebook_id']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-green btn-sm w-100">
                                                <i class="bi bi-cart-plus me-1"></i>Add to Cart
                                            </button>
                                        </form>
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
    <?php include 'chatbot/chatbot.php'; ?>
</body>

</html>