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
    <style>
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

        .price-link {
            text-decoration: none;
            cursor: pointer;
            transition: opacity 0.2s ease;
        }

        .price-link:hover {
            opacity: 0.8;
        }

        .ebook-card-body {
            padding: 0.5rem;
        }

        @media (min-width: 576px) {
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
    </style>
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

        <div class="row g-3 px-2 px-sm-4">
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
                                    <h6 class="card-title ebook-title fw-bold text-dark mb-1 mb-sm-2 overflow-hidden" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                                        <?php echo htmlspecialchars($ebook['title']); ?>
                                    </h6>
                                </a>
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
                                            <a href="download.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-green btn-sm ebook-btn">
                                                <i class="bi bi-download me-1"></i><span class="d-none d-sm-inline">Download</span><span class="d-inline d-sm-none">Get</span>
                                            </a>
                                        </div>
                                    <?php else: ?>
                                        <div class="mb-2 text-center">
                                            <a href="checkout.php?id=<?php echo $ebook['ebook_id']; ?>&buy_now=1" class="price-link m-1 d-inline-block" onclick="return confirm('Proceed to checkout for <?php echo htmlspecialchars(addslashes($ebook['title'])); ?>?');">
                                                <span class="ebook-price text-green fw-bold">₱<?php echo number_format($ebook['price'], 2); ?></span>
                                            </a>
                                        </div>
                                        <div class="d-grid">
                                            <form method="POST" class="m-0">
                                                <input type="hidden" name="ebook_id" value="<?php echo $ebook['ebook_id']; ?>">
                                                <button type="submit" name="add_to_cart" class="btn btn-green btn-sm w-100 ebook-btn">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
    <?php include 'chatbot/chatbot.php'; ?>
</body>

</html>