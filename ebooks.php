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

// Get user's purchased ebooks if logged in
$purchased_ebooks = [];
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $purchased_query = "SELECT DISTINCT oi.ebook_id 
                        FROM order_items oi 
                        INNER JOIN orders o ON oi.order_id = o.order_id 
                        WHERE o.user_id = $user_id AND o.status = 'completed'";
    $purchased_result = executeQuery($purchased_query);
    while ($row = mysqli_fetch_assoc($purchased_result)) {
        $purchased_ebooks[] = $row['ebook_id'];
    }
}

// Handle search and category filtering
$search_query = isset($_GET['q']) ? trim($_GET['q']) : '';
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$total_results = 0;
$category_name = '';

// Pagination settings
$per_page = 12;
$page = isset($_GET['page']) && intval($_GET['page']) > 0 ? intval($_GET['page']) : 1;
$offset = ($page - 1) * $per_page;

// Get category name if filtering by category
if ($category_id > 0) {
    $cat_query = "SELECT name FROM categories WHERE category_id = $category_id";
    $cat_result = executeQuery($cat_query);
    if ($cat_result && mysqli_num_rows($cat_result) > 0) {
        $cat_row = mysqli_fetch_assoc($cat_result);
        $category_name = $cat_row['name'];
    }
}

// Fetch ebooks from database with strict title and author search
if (!empty($search_query)) {
    $search_term = '%' . mysqli_real_escape_string($conn, $search_query) . '%';
    // Count total matching results
    $count_query = "SELECT COUNT(DISTINCT e.ebook_id) as cnt FROM ebooks e LEFT JOIN categories c ON e.category_id = c.category_id WHERE (e.title LIKE '$search_term' OR e.author LIKE '$search_term')" . ($category_id > 0 ? " AND e.category_id = $category_id" : "");
    $count_result = executeQuery($count_query);
    if ($count_result) {
        $row_cnt = mysqli_fetch_assoc($count_result);
        $total_results = intval($row_cnt['cnt']);
    }

    $query = "
        SELECT DISTINCT
            e.ebook_id,
            e.title,
            e.author,
            e.price,
            e.cover_image,
            e.file_path,
            c.name as category_name,
            (SELECT AVG(rating) FROM ratings r WHERE r.ebook_id = e.ebook_id) as avg_rating,
            (SELECT COUNT(*) FROM ratings r WHERE r.ebook_id = e.ebook_id) as total_ratings
        FROM ebooks e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE 
            (e.title LIKE '$search_term' OR
            e.author LIKE '$search_term')
            " . ($category_id > 0 ? "AND e.category_id = $category_id" : "") . "
        ORDER BY 
            CASE 
                WHEN e.title LIKE '$search_term' THEN 1
                WHEN e.author LIKE '$search_term' THEN 2
                ELSE 3
            END,
            e.title ASC
        LIMIT $offset, $per_page
    ";
    $result = executeQuery($query);
} elseif ($category_id > 0) {
    // Filter by category only
    // Count total in category
    $count_query = "SELECT COUNT(*) as cnt FROM ebooks WHERE category_id = $category_id";
    $count_result = executeQuery($count_query);
    if ($count_result) {
        $row_cnt = mysqli_fetch_assoc($count_result);
        $total_results = intval($row_cnt['cnt']);
    }

    $query = "
        SELECT 
            e.ebook_id, 
            e.title, 
            e.author, 
            e.price, 
            e.cover_image, 
            e.file_path,
            c.name as category_name,
            (SELECT AVG(rating) FROM ratings r WHERE r.ebook_id = e.ebook_id) as avg_rating, 
            (SELECT COUNT(*) FROM ratings r WHERE r.ebook_id = e.ebook_id) as total_ratings 
        FROM ebooks e
        LEFT JOIN categories c ON e.category_id = c.category_id
        WHERE e.category_id = $category_id 
        ORDER BY e.created_at DESC
        LIMIT $offset, $per_page
    ";
    $result = executeQuery($query);
} else {
    // Count total ebooks
    $count_query = "SELECT COUNT(*) as cnt FROM ebooks";
    $count_result = executeQuery($count_query);
    if ($count_result) {
        $row_cnt = mysqli_fetch_assoc($count_result);
        $total_results = intval($row_cnt['cnt']);
    }

    $query = "SELECT e.ebook_id, e.title, e.author, e.price, e.cover_image, e.file_path, c.name as category_name, (SELECT AVG(rating) FROM ratings r WHERE r.ebook_id = e.ebook_id) as avg_rating, (SELECT COUNT(*) FROM ratings r WHERE r.ebook_id = e.ebook_id) as total_ratings FROM ebooks e LEFT JOIN categories c ON e.category_id = c.category_id ORDER BY e.created_at DESC LIMIT $offset, $per_page";
    $result = executeQuery($query);
}

// Compute total pages
$total_pages = $per_page > 0 ? max(1, ceil($total_results / $per_page)) : 1;

// Set page title and description based on search or category
if (!empty($search_query)) {
    $page_title = 'Search Results';
    $page_description = 'Found <strong>' . $total_results . '</strong> result' . ($total_results !== 1 ? 's' : '') .
        ' for "<strong>' . htmlspecialchars($search_query) . '</strong>"' .
        ' <a href="ebooks.php" class="ms-3 text-decoration-none"><i class="bi bi-x-circle me-1"></i>Clear search</a>';
} elseif ($category_id > 0 && !empty($category_name)) {
    $page_title = htmlspecialchars($category_name) . ' E-Books';
    $page_description = 'Browse <strong>' . $total_results . '</strong> book' . ($total_results !== 1 ? 's' : '') .
        ' in the <strong>' . htmlspecialchars($category_name) . '</strong> category' .
        ' <a href="ebooks.php" class="ms-3 text-decoration-none"><i class="bi bi-x-circle me-1"></i>View all</a>';
} else {
    $page_title = 'Tech E-Books';
    $page_description = 'Browse our collection of programming and computer science books';
}

$title = 'E-Books';
$extraStyles = '<style>
    /* Responsive ebook cover sizes */
    .ebook-cover-container {
        min-height: 180px;
        height: 180px;
    }

    /* Responsive typography */
    .ebook-title {
        font-size: 0.8rem;
        line-height: 1.2;
        min-height: 2.4rem;
    }

    .ebook-author {
        font-size: 0.7rem;
    }

    .ebook-price {
        font-size: 0.95rem;
    }

    .ebook-btn {
        font-size: 0.75rem;
        padding: 0.35rem 0.5rem;
        transition: all 0.3s ease;
    }

    .ebook-btn:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
    }

    .ebook-btn:active {
        transform: translateY(0);
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .price-btn {
        font-size: 0.75rem;
        padding: 0.35rem 0.5rem;
    }

    .price-btn:active {
        box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .price-btn:hover {
        background-color: #f8f9fa;
        border-color: #ced4da;
    }

    .price-link {
        text-decoration: none;
        cursor: pointer;
        transition: background-color 0.18s ease, border-color 0.18s ease, box-shadow 0.18s ease, opacity 0.18s ease, transform 0.18s ease;
        display: inline-block;
        padding: 0.08rem 0.25rem;
        border-radius: 0.25rem;
    }

    .ebook-card-body {
        padding: 0.5rem;
    }

    .rating-stars {
        color: #ffc107;
        font-size: 0.7rem;
    }

    @media (min-width: 576px) {
        .rating-stars {
            font-size: 0.8rem;
        }

        .ebook-cover-container {
            min-height: 240px;
            height: 240px;
        }

        .ebook-title {
            font-size: 0.95rem;
            line-height: 1.3;
            min-height: 2.6rem;
        }

        .ebook-author {
            font-size: 0.8rem;
        }

        .ebook-price {
            font-size: 1.05rem;
        }

        .ebook-btn {
            font-size: 0.85rem;
            padding: 0.4rem 0.6rem;
        }

        .ebook-card-body {
            padding: 0.75rem;
        }
    }

    @media (min-width: 768px) {
        .rating-stars {
            font-size: 0.875rem;
        }

        .ebook-cover-container {
            min-height: 280px;
            height: 280px;
        }

        .ebook-title {
            font-size: 1rem;
            line-height: 1.3;
            min-height: 2.6rem;
        }

        .ebook-author {
            font-size: 0.875rem;
        }

        .ebook-price {
            font-size: 1.15rem;
        }

        .ebook-btn {
            font-size: 0.9rem;
            padding: 0.45rem 0.7rem;
        }

        .ebook-card-body {
            padding: 1rem;
        }
    }

    @media (min-width: 992px) {
        .ebook-cover-container {
            min-height: 320px;
            height: 320px;
        }

        .ebook-title {
            font-size: 1.1rem;
            line-height: 1.4;
            min-height: 3rem;
        }

        .ebook-author {
            font-size: 0.9375rem;
        }

        .ebook-price {
            font-size: 1.25rem;
        }

        .ebook-btn {
            font-size: 0.9375rem;
            padding: 0.5rem 0.75rem;
        }

        .ebook-card-body {
            padding: 1.25rem;
        }
    }

    .pagination .page-link {
        color: #28a745;
        border-color: #28a745;
    }

    .pagination .page-link:hover {
        background-color: #28a745;
        color: #fff;
    }

    .pagination .page-item.active .page-link {
        background-color: #28a745;
        border-color: #28a745;
        color: #fff;
    }

    .pagination .page-item.disabled .page-link {
        color: #6c757d;
    }
</style>';
include 'includes/head.php';
?>

<body>
    <!-- navbar -->
    <?php include 'includes/nav.php'; ?>

    <?php
    $message = !empty($message) ? $message : '';
    $message_type = !empty($message_type) ? $message_type : '';
    $messageType = $message_type;
    if (!empty($message)) {
        echo '<div class="container position-absolute start-50 translate-middle-x" style="top: 80px; z-index: 1050;">';
        include 'includes/notification.php';
        echo '</div>';
    }
    ?>

    <!-- Main Content -->
    <?php include 'includes/client-main.php'; ?>

    <?php
    // Build base URL for pagination links while preserving search/category params
    $base_url = 'ebooks.php?';
    if (!empty($search_query)) {
        $base_url .= 'q=' . urlencode($search_query) . '&';
    }
    if ($category_id > 0) {
        $base_url .= 'category_id=' . $category_id . '&';
    }
    ?>

    <div class="row g-3 px-2 px-sm-4 mb-4">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($ebook = mysqli_fetch_assoc($result)) {
                $is_purchased = in_array($ebook['ebook_id'], $purchased_ebooks);
        ?>
                <div class="col-6 col-sm-6 col-md-4 col-lg-3">
                    <div class="card ebook-card shadow-sm border-0 h-100">
                        <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="text-decoration-none bg-light d-flex align-items-center justify-content-center ebook-cover-container" style="overflow: hidden;">
                            <img src="<?php echo htmlspecialchars($ebook['cover_image'] ?? 'assets/img/ebook_cover/default.jpg'); ?>"
                                class="ebook-cover w-100 h-100"
                                alt="<?php echo htmlspecialchars($ebook['title']); ?>"
                                style="object-fit: contain; object-position: center;">
                        </a>
                        <div class="card-body d-flex flex-column ebook-card-body">
                            <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="text-decoration-none">
                                <h6 class="card-title ebook-title fw-bold text-dark mb-0 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                    <?php echo htmlspecialchars($ebook['title']); ?>
                                </h6>
                            </a>
                            <div class="rating-stars mb-1">
                                <?php
                                $avg_rating = isset($ebook['avg_rating']) && $ebook['avg_rating'] !== null ? round(floatval($ebook['avg_rating']), 1) : 0;
                                $total_ratings = isset($ebook['total_ratings']) ? intval($ebook['total_ratings']) : 0;
                                $full_stars = floor($avg_rating);
                                $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
                                $empty_stars = 5 - $full_stars - $half_star;

                                for ($i = 0; $i < $full_stars; $i++) {
                                    echo '<i class="bi bi-star-fill"></i>';
                                }
                                if ($half_star) {
                                    echo '<i class="bi bi-star-half"></i>';
                                }
                                for ($i = 0; $i < $empty_stars; $i++) {
                                    echo '<i class="bi bi-star"></i>';
                                }

                                if ($total_ratings > 0) {
                                    echo '<span class="text-muted ms-1" style="font-size: 0.7rem;">(' . number_format($avg_rating, 1) . ')</span>';
                                } else {
                                    echo '<span class="text-muted ms-1" style="font-size: 0.7rem;">(0)</span>';
                                }
                                ?>
                            </div>
                            <p class="card-text ebook-author mb-1 mb-sm-2 text-muted">
                                <i class="bi bi-person me-1"></i><span class="d-none d-sm-inline"><?php echo htmlspecialchars($ebook['author'] ?? 'Unknown'); ?></span><span class="d-inline d-sm-none"><?php echo htmlspecialchars(strlen($ebook['author']) > 15 ? substr($ebook['author'], 0, 15) . '...' : $ebook['author']); ?></span>
                            </p>
                            <div class="mt-auto">
                                <?php if ($is_purchased): ?>
                                    <div class="mb-2">
                                        <span class="badge bg-success w-100 py-2">
                                            <i class="bi bi-check-circle me-1"></i>Purchased
                                        </span>
                                    </div>
                                    <div class="d-grid">
                                        <a href="download.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-green btn-sm ebook-btn d-flex align-items-center justify-content-center">
                                            <i class="bi bi-download me-1"></i>Download
                                        </a>
                                    </div>
                                <?php else: ?>
                                    <div class="mb-2 d-grid">
                                        <button type="button" class="btn w-100 price-btn" style="background: #fff; border: 1px solid #dee2e6; height: 38px; font-size: 0.9rem; color: inherit;" aria-label="Buy <?php echo htmlspecialchars($ebook['title']); ?>" onclick="if(confirm('Proceed to checkout for <?php echo htmlspecialchars(addslashes($ebook['title'])); ?>?')){ window.location.href='checkout.php?id=<?php echo $ebook['ebook_id']; ?>&buy_now=1'; }">
                                            <span class="ebook-price text-green fw-bold">₱<?php echo number_format($ebook['price'], 2); ?></span>
                                        </button>
                                    </div>
                                    <div class="d-grid">
                                        <form method="POST" class="m-0">
                                            <input type="hidden" name="ebook_id" value="<?php echo $ebook['ebook_id']; ?>">
                                            <button type="submit" name="add_to_cart" class="btn btn-green w-100" style="border: 1px solid rgba(0,0,0,0.1); height: 38px; font-size: 0.9rem;">
                                                <i class="bi bi-cart-plus me-1"></i><span class="d-none d-sm-inline">Add to Cart</span><span class="d-inline d-sm-none">Add</span>
                                            </button>
                                        </form>
                                    </div>
                                <?php endif; ?>
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

    <div class="container mt-3">
        <nav aria-label="Page navigation example">
            <ul class="pagination justify-content-center">
                <li class="page-item <?php if ($page <= 1) echo 'disabled'; ?>">
                    <a class="page-link" href="<?php echo $base_url . 'page=' . max(1, $page - 1); ?>" aria-label="Previous">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                </li>
                <?php for ($p = 1; $p <= $total_pages; $p++): ?>
                    <li class="page-item <?php if ($p == $page) echo 'active'; ?>"><a class="page-link" href="<?php echo $base_url . 'page=' . $p; ?>"><?php echo $p; ?></a></li>
                <?php endfor; ?>
                <li class="page-item <?php if ($page >= $total_pages) echo 'disabled'; ?>">
                    <a class="page-link" href="<?php echo $base_url . 'page=' . min($total_pages, $page + 1); ?>" aria-label="Next">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                </li>
            </ul>
        </nav>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'chatbot/chatbot.php'; ?>
    <?php include 'includes/footer.php'; ?>
</body>

</html>