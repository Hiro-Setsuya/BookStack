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

// Handle search functionality
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$total_results = 0;

// Fetch ebooks from database with optional search
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    $query = "
        SELECT DISTINCT
            e.ebook_id,
            e.title,
            e.author,
            e.price,
            e.cover_image,
            e.file_path,
            c.name as category_name
        FROM ebooks e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE 
            e.title LIKE '$search_term' OR
            e.author LIKE '$search_term' OR
            e.description LIKE '$search_term' OR
            c.name LIKE '$search_term'
        ORDER BY 
            CASE 
                WHEN e.title LIKE '$search_term' THEN 1
                WHEN e.author LIKE '$search_term' THEN 2
                WHEN c.name LIKE '$search_term' THEN 3
                ELSE 4
            END,
            e.title ASC
    ";
    $result = executeQuery($query);
    if ($result) {
        $total_results = mysqli_num_rows($result);
    }
} else {
    $query = "SELECT ebook_id, title, author, price, cover_image, file_path FROM ebooks ORDER BY created_at DESC";
    $result = executeQuery($query);
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>E-Books - BookStack</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>
    <!-- navbar -->
    <?php include 'includes/nav.php'; ?>
    <!-- Content -->
    <div class="container mt-5 pt-5">
        <?php
        $message = !empty($message) ? $message : '';
        $message_type = !empty($message_type) ? $message_type : '';
        $messageType = $message_type;
        include 'includes/notification.php';
        ?>

        <div class="row mb-4">
            <div class="col">
                <?php if (!empty($search_query)): ?>
                    <h2 class="fw-bold">Search Results</h2>
                    <p class="text-muted">
                        Found <strong><?php echo $total_results; ?></strong> result<?php echo $total_results !== 1 ? 's' : ''; ?>
                        for "<strong><?php echo htmlspecialchars($search_query); ?></strong>"
                        <a href="ebooks.php" class="ms-3 text-decoration-none">
                            <i class="bi bi-x-circle me-1"></i>Clear search
                        </a>
                    </p>
                <?php else: ?>
                    <h2 class="fw-bold">Tech E-Books</h2>
                    <p class="text-muted">Browse our collection of programming and computer science books</p>
                <?php endif; ?>
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
                    <?php if (!empty($search_query)): ?>
                        <div class="text-center py-5">
                            <i class="bi bi-search" style="font-size: 4rem; color: #d1d5db;"></i>
                            <h4 class="mt-3">No results found</h4>
                            <p class="text-muted">Try different keywords or <a href="ebooks.php">browse all ebooks</a></p>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info text-center">
                            <i class="bi bi-info-circle me-2"></i>No e-books available at the moment.
                        </div>
                    <?php endif; ?>
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