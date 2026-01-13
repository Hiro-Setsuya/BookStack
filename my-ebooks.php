<?php
session_start();
require_once 'config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Fetch user's purchased ebooks
$query = "SELECT DISTINCT e.*, c.name as category_name, o.created_at as purchase_date 
          FROM ebooks e
          INNER JOIN order_items oi ON e.ebook_id = oi.ebook_id
          INNER JOIN orders o ON oi.order_id = o.order_id
          LEFT JOIN categories c ON e.category_id = c.category_id
          WHERE o.user_id = $user_id AND o.status = 'completed'
          ORDER BY o.created_at DESC";
$result = executeQuery($query);

// Fetch user data for sidebar
$user_query = "SELECT user_name, email, created_at FROM users WHERE user_id = $user_id";
$user_result = executeQuery($user_query);
$user = mysqli_fetch_assoc($user_result);

$title = 'My E-Books';
include 'includes/head.php';
?>

<body>
    <?php include 'includes/nav.php'; ?>

    <div class="container account-container py-4">
        <div class="row">
            <?php include 'includes/client-sidebar.php'; ?>

            <div class="col-lg-9">
                <div class="profile-header mb-4 text-center text-lg-start">
                    <h2 class="fw-bold">My E-Books</h2>
                    <p class="text-muted">View and download your purchased e-books.</p>
                </div>

                <?php if (isset($_GET['payment']) && $_GET['payment'] === 'success'): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <strong>Payment Successful!</strong> Your e-book purchase has been completed. You can now download your e-books below.
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if (mysqli_num_rows($result) > 0): ?>
                    <div class="row g-4">
                        <?php while ($ebook = mysqli_fetch_assoc($result)): ?>
                            <div class="col-12">
                                <div class="card profile-card h-100">
                                    <div class="card-body p-4">
                                        <div class="row g-3">
                                            <!-- Ebook Cover -->
                                            <div class="col-auto">
                                                <div class="position-relative rounded overflow-hidden" style="width: 100px; height: 140px; background-color: #f8f9fa; border: 1px solid #e0e0e0;">
                                                    <img src="<?php echo htmlspecialchars($ebook['cover_image']); ?>"
                                                        alt="<?php echo htmlspecialchars($ebook['title']); ?>"
                                                        class="w-100 h-100"
                                                        style="object-fit: cover;"
                                                        onerror="this.style.objectFit='contain'; this.style.padding='10px';">
                                                </div>
                                            </div>

                                            <!-- Ebook Details -->
                                            <div class="col">
                                                <div class="d-flex justify-content-between align-items-start">
                                                    <div class="flex-grow-1">
                                                        <h5 class="fw-bold mb-1">
                                                            <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="text-decoration-none text-dark">
                                                                <?php echo htmlspecialchars($ebook['title']); ?>
                                                            </a>
                                                        </h5>
                                                        <p class="text-muted mb-2">By <?php echo htmlspecialchars($ebook['author']); ?></p>
                                                        <div class="d-flex gap-2 flex-wrap mb-3">
                                                            <span class="badge bg-light text-dark border"><?php echo htmlspecialchars($ebook['category_name']); ?></span>
                                                            <span class="badge bg-success">Purchased</span>
                                                            <span class="badge bg-light text-muted border">
                                                                <i class="bi bi-calendar me-1"></i><?php echo date('M d, Y', strtotime($ebook['purchase_date'])); ?>
                                                            </span>
                                                        </div>
                                                        <p class="text-muted small mb-3" style="line-height: 1.6;">
                                                            <?php
                                                            $description = htmlspecialchars($ebook['description']);
                                                            echo strlen($description) > 200 ? substr($description, 0, 200) . '...' : $description;
                                                            ?>
                                                        </p>
                                                        <div class="d-flex gap-2 flex-wrap">
                                                            <a href="download.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-sm btn-green">
                                                                <i class="bi bi-download me-1"></i> Download PDF
                                                            </a>
                                                            <a href="ebook-details.php?id=<?php echo $ebook['ebook_id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                                <i class="bi bi-eye me-1"></i> View Details
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endwhile; ?>
                    </div>
                <?php else: ?>
                    <div class="card profile-card text-center py-5">
                        <div class="card-body">
                            <div class="mb-4">
                                <i class="bi bi-book" style="font-size: 4rem; color: #dee2e6;"></i>
                            </div>
                            <h4 class="fw-bold mb-3">No E-Books Yet</h4>
                            <p class="text-muted mb-4">You haven't purchased any e-books yet. Start exploring our collection!</p>
                            <a href="ebooks.php" class="btn btn-green d-inline-flex align-items-center justify-content-center">
                                <i class="bi bi-search me-2"></i> Browse E-Books
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>