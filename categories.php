<?php
session_start();
require_once 'config/db.php';

$statusMessage = '';
$statusType = '';

// Fetch categories with ebook count
$query = "SELECT c.category_id, c.name, 
          COUNT(e.ebook_id) as ebook_count 
          FROM categories c 
          LEFT JOIN ebooks e ON c.category_id = e.category_id 
          GROUP BY c.category_id, c.name 
          ORDER BY c.name ASC";
$result = executeQuery($query);

// Set page title and description
$page_title = 'E-Book Categories';
$page_description = 'Explore our collection of ' . mysqli_num_rows($result) . ' categories';

$title = 'E-Book Categories';
$extraStyles = '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" />';
include 'includes/head.php';
?>

<body>
    <!-- navbar -->
    <?php include 'includes/nav.php'; ?>

    <!-- Main Content -->
    <?php include 'includes/client-main.php'; ?>

    <!-- Categories Grid -->
    <div class="row g-3 px-2 px-sm-4">
        <?php
        if ($result && mysqli_num_rows($result) > 0) {
            while ($category = mysqli_fetch_assoc($result)) {
                $category_name = htmlspecialchars($category['name']);
                $ebook_count = $category['ebook_count'];
                $category_id = $category['category_id'];
        ?>
                <!-- Category Card -->
                <div class="col-6 col-sm-12 col-md-4 col-lg-3">
                    <div class="card ebook-card shadow-sm border-0 h-100">
                        <div class="card-body d-flex flex-column ebook-card-body">
                            <div class="category-header mb-3">
                                <h6 class="card-title ebook-title fw-bold text-dark mb-2"><?php echo $category_name; ?></h6>
                                <div class="category-count-badge">
                                    <?php echo $ebook_count; ?> Book<?php echo $ebook_count != 1 ? 's' : ''; ?>
                                </div>
                            </div>
                            <p class="text-muted small mb-4 flex-grow-1">
                                Explore <?php echo $ebook_count; ?> amazing e-book<?php echo $ebook_count != 1 ? 's' : ''; ?>
                            </p>
                            <div class="mt-auto">
                                <a href="ebooks.php?category_id=<?php echo $category_id; ?>"
                                    class="btn btn-green w-100 rounded-pill py-2 fw-semibold">
                                    Browse
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php
            }
        } else {
            ?>
            <!-- No Categories Found -->
            <div class="col-12">
                <div class="alert alert-info text-center py-5" role="alert">
                    <i class="bi bi-info-circle fs-1 d-block mb-3"></i>
                    <h4 class="alert-heading">No Categories Available</h4>
                    <p class="mb-0">There are currently no categories to display. Please check back later!</p>
                </div>
            </div>
        <?php
        }
        ?>
    </div>
    </div>

    <?php include 'includes/footer.php'; ?>
    <?php include 'chatbot/chatbot.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>