<?php
session_start();
require_once 'config/db.php';

// Get ebook ID from URL
$ebook_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

// Fetch ebook details
$ebook_query = "SELECT e.*, c.name as category_name 
                FROM ebooks e 
                LEFT JOIN categories c ON e.category_id = c.category_id 
                WHERE e.ebook_id = $ebook_id";
$ebook_result = executeQuery($ebook_query);
$ebook = mysqli_fetch_assoc($ebook_result);

if (!$ebook) {
  header('Location: 404.php');
  exit();
}

// Check if user is logged in
$is_logged_in = isset($_SESSION['user_id']);
$user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Check if user has purchased this ebook
$has_purchased = false;
if ($is_logged_in) {
  $purchase_query = "SELECT COUNT(*) as count 
                       FROM order_items oi 
                       INNER JOIN orders o ON oi.order_id = o.order_id 
                       WHERE o.user_id = $user_id 
                       AND oi.ebook_id = $ebook_id 
                       AND o.status = 'completed'";
  $purchase_result = executeQuery($purchase_query);
  $purchase_data = mysqli_fetch_assoc($purchase_result);
  $has_purchased = $purchase_data['count'] > 0;
}

// Fetch all ratings for this ebook
$ratings_query = "SELECT r.*, u.user_name 
                  FROM ratings r 
                  INNER JOIN users u ON r.user_id = u.user_id 
                  WHERE r.ebook_id = $ebook_id 
                  ORDER BY r.created_at DESC";
$ratings_result = executeQuery($ratings_query);

// Calculate average rating
$avg_query = "SELECT AVG(rating) as avg_rating, COUNT(*) as total_ratings 
              FROM ratings 
              WHERE ebook_id = $ebook_id";
$avg_result = executeQuery($avg_query);
$avg_data = mysqli_fetch_assoc($avg_result);
$avg_rating = round($avg_data['avg_rating'], 1);
$total_ratings = $avg_data['total_ratings'];

// Check if user has already rated this ebook
$user_has_rated = false;
if ($is_logged_in) {
  $user_rating_query = "SELECT * FROM ratings WHERE user_id = $user_id AND ebook_id = $ebook_id";
  $user_rating_result = executeQuery($user_rating_query);
  $user_has_rated = mysqli_num_rows($user_rating_result) > 0;
}

// Handle rating submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['submit_rating']) && $is_logged_in && $has_purchased && !$user_has_rated) {
  $rating = intval($_POST['rating']);
  $review = mysqli_real_escape_string($conn, $_POST['review']);

  $insert_query = "INSERT INTO ratings (user_id, ebook_id, rating, review) 
                     VALUES ($user_id, $ebook_id, $rating, '$review')";
  if (executeQuery($insert_query)) {
    header("Location: ebook-details.php?id=$ebook_id&success=1");
    exit();
  }
}

$title = htmlspecialchars($ebook['title']);
$extraStyles = '<link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,0..200" rel="stylesheet" />
<style>
  /* Star Rating Input */
  .rating-input {
    display: flex;
    flex-direction: row-reverse;
    justify-content: flex-end;
    gap: 5px;
    font-size: 2.5rem;
  }

  .rating-input input {
    display: none;
  }

  .rating-input label {
    cursor: pointer;
    color: #ddd;
    transition: color 0.2s;
  }

  .rating-input label:hover,
  .rating-input label:hover~label,
  .rating-input input:checked~label {
    color: #ffc107;
  }
</style>';
include 'includes/head.php';
?>

<body class="font-sans antialiased">
  <div class="d-flex flex-column min-vh-100">
    <!-- Top Navigation -->
    <?php include 'includes/nav.php' ?>
    <!-- Main Content -->
    <main class="flex-grow-1 py-5" style="margin-top: 80px;">
      <div class="container" style="max-width: 1400px;">

        <!-- Product Hero -->
        <div class="row g-4 mb-5 justify-content-center">
          <!-- Left: Image -->
          <div class="col-12 col-sm-8 col-md-5 col-lg-4 col-xl-3">
            <div class="position-relative rounded-3 overflow-hidden shadow" style="aspect-ratio: 2/3; background-color: #f8f9fa; max-width: 350px; margin: 0 auto; border: 1px solid #e0e0e0;">
              <img src="<?php echo htmlspecialchars($ebook['cover_image']); ?>"
                alt="<?php echo htmlspecialchars($ebook['title']); ?>"
                class="w-100 h-100"
                style="object-fit: cover; object-position: center;"
                onerror="this.style.objectFit='contain'; this.style.padding='20px';">
            </div>
          </div>

          <!-- Right: Details -->
          <div class="col-12 col-md-7 col-lg-8 col-xl-9">
            <div style="max-width: 900px;">
              <h1 class="h2 h1-lg fw-black mb-2" style="color: #0d121c;"><?php echo htmlspecialchars($ebook['title']); ?></h1>
              <p style="color: #2ecc71; font-weight: 600;" class="mb-3 fs-5">By <?php echo htmlspecialchars($ebook['author']); ?></p>

              <!-- Rating -->
              <div class="d-flex align-items-center gap-2 mb-4">
                <div class="text-warning fs-5">
                  <?php
                  $full_stars = floor($avg_rating);
                  $half_star = ($avg_rating - $full_stars) >= 0.5 ? 1 : 0;
                  $empty_stars = 5 - $full_stars - $half_star;

                  for ($i = 0; $i < $full_stars; $i++) echo '★';
                  if ($half_star) echo '★';
                  for ($i = 0; $i < $empty_stars; $i++) echo '☆';
                  ?>
                </div>
                <small class="text-muted">(<?php echo $total_ratings; ?> review<?php echo $total_ratings != 1 ? 's' : ''; ?>)</small>
              </div>

              <!-- Pricing Card -->
              <div class="card p-3 p-md-4 mb-4" style="border-top: 4px solid #2ecc71; box-shadow: 0 2px 8px rgba(46, 204, 113, 0.1);">
                <div class="d-flex flex-column flex-sm-row justify-content-between gap-3 mb-3">
                  <div>
                    <div class="d-flex align-items-baseline gap-2">
                      <span class="h2 fw-black mb-0">₱<?php echo number_format($ebook['price'], 2); ?></span>
                    </div>
                    <p class="text-success fw-semibold mb-0">PDF Format • Instant Download</p>
                  </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-grid gap-2 d-sm-flex">
                  <button class="btn btn-lg d-flex align-items-center justify-content-center gap-2 flex-grow-1" style="background-color: #2ecc71; border-color: #2ecc71; color: white; font-weight: 600;">
                    <span class="material-symbols-outlined">shopping_cart</span> Add to Cart
                  </button>
                  <a href="checkout.php?id=<?php echo $ebook_id; ?>&buy_now=1" class="btn btn-lg flex-grow-1" style="border: 2px solid #2ecc71; color: #2ecc71; font-weight: 600; text-decoration: none;">Buy Now</a>
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
            </div>
          </div>
        </div>

        <!-- Description Section -->
        <section class="mb-5">
          <div class="card shadow-sm">
            <div class="card-body p-4">
              <h3 class="h4 mb-3 fw-bold">Description</h3>
              <div style="max-width: 900px;">
                <p class="text-muted" style="line-height: 1.8;">
                  <?php echo nl2br(htmlspecialchars($ebook['description'])); ?>
                </p>
              </div>
            </div>
          </div>
        </section>

        <!-- Ratings & Reviews Section -->
        <section id="ratings-section" class="mb-5">
          <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="h4 fw-bold">Customer Reviews</h2>
          </div>

          <!-- Rating Summary -->
          <div class="card mb-4 shadow-sm">
            <div class="card-body p-4">
              <div class="row align-items-center g-4">
                <div class="col-12 col-md-4 text-center border-md-end">
                  <div class="display-4 fw-bold text-warning"><?php echo $avg_rating > 0 ? $avg_rating : 'N/A'; ?></div>
                  <div class="text-warning fs-4">
                    <?php
                    if ($avg_rating > 0) {
                      for ($i = 0; $i < $full_stars; $i++) echo '★';
                      if ($half_star) echo '★';
                      for ($i = 0; $i < $empty_stars; $i++) echo '☆';
                    }
                    ?>
                  </div>
                  <p class="text-muted mb-0"><?php echo $total_ratings; ?> review<?php echo $total_ratings != 1 ? 's' : ''; ?></p>
                </div>
                <div class="col-12 col-md-8">
                  <?php
                  // Get rating distribution
                  for ($star = 5; $star >= 1; $star--) {
                    $star_query = "SELECT COUNT(*) as count FROM ratings WHERE ebook_id = $ebook_id AND rating = $star";
                    $star_result = executeQuery($star_query);
                    $star_data = mysqli_fetch_assoc($star_result);
                    $star_count = $star_data['count'];
                    $star_percentage = $total_ratings > 0 ? ($star_count / $total_ratings) * 100 : 0;
                  ?>
                    <div class="d-flex align-items-center mb-2">
                      <span class="text-muted" style="width: 50px;"><?php echo $star; ?> star</span>
                      <div class="progress flex-grow-1 mx-3" style="height: 10px;">
                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $star_percentage; ?>%"></div>
                      </div>
                      <span class="text-muted" style="width: 70px;"><?php echo $star_count; ?> (<?php echo round($star_percentage); ?>%)</span>
                    </div>
                  <?php } ?>
                </div>
              </div>
            </div>
          </div>

          <!-- Add Rating Form -->
          <?php if ($is_logged_in): ?>
            <?php if ($has_purchased && !$user_has_rated): ?>
              <div class="card mb-4">
                <div class="card-body">
                  <h5 class="card-title mb-3">Rate This Ebook</h5>
                  <p class="text-muted small mb-3">Share your rating and feedback. Rating is required, but review is optional.</p>
                  <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success" role="alert">
                      <i class="bi bi-check-circle-fill"></i> Thank you for your rating and feedback!
                    </div>
                  <?php endif; ?>
                  <form method="POST" action="" id="ratingForm">
                    <div class="mb-3">
                      <label class="form-label fw-bold">Your Rating <span class="text-danger">*</span></label>
                      <div class="rating-input">
                        <input type="radio" name="rating" value="5" id="star5" required>
                        <label for="star5" title="5 stars">★</label>
                        <input type="radio" name="rating" value="4" id="star4">
                        <label for="star4" title="4 stars">★</label>
                        <input type="radio" name="rating" value="3" id="star3">
                        <label for="star3" title="3 stars">★</label>
                        <input type="radio" name="rating" value="2" id="star2">
                        <label for="star2" title="2 stars">★</label>
                        <input type="radio" name="rating" value="1" id="star1">
                        <label for="star1" title="1 star">★</label>
                      </div>
                    </div>
                    <div class="mb-3">
                      <label for="review" class="form-label fw-bold">Your Review <span class="text-muted fw-normal">(Optional)</span></label>
                      <textarea class="form-control" id="review" name="review" rows="4" placeholder="Share your experience with this ebook... (Optional)"></textarea>
                    </div>
                    <button type="submit" name="submit_rating" class="btn" style="background-color: #2ecc71; color: white;"><i class="bi bi-star-fill"></i> Submit Rating</button>
                  </form>
                </div>
              </div>
            <?php elseif ($user_has_rated): ?>
              <div class="alert alert-info mb-4" role="alert">
                <i class="bi bi-info-circle"></i> You have already reviewed this ebook.
              </div>
            <?php else: ?>
              <div class="alert alert-warning mb-4" role="alert">
                <i class="bi bi-exclamation-triangle"></i> You need to purchase this ebook before you can write a review.
              </div>
            <?php endif; ?>
          <?php else: ?>
            <div class="alert alert-info mb-4" role="alert">
              <i class="bi bi-info-circle"></i> Please <a href="login.php" class="alert-link">login</a> to write a review.
            </div>
          <?php endif; ?>

          <!-- Display Reviews -->
          <div class="reviews-list">
            <h5 class="mb-3">All Reviews</h5>
            <?php if ($total_ratings > 0): ?>
              <?php while ($rating = mysqli_fetch_assoc($ratings_result)): ?>
                <div class="card mb-3">
                  <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                      <div>
                        <h6 class="mb-1 fw-bold"><?php echo htmlspecialchars($rating['user_name']); ?></h6>
                        <div class="text-warning">
                          <?php
                          for ($i = 0; $i < $rating['rating']; $i++) echo '★';
                          for ($i = $rating['rating']; $i < 5; $i++) echo '☆';
                          ?>
                        </div>
                      </div>
                      <small class="text-muted"><?php echo date('M d, Y', strtotime($rating['created_at'])); ?></small>
                    </div>
                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($rating['review'])); ?></p>
                  </div>
                </div>
              <?php endwhile; ?>
            <?php else: ?>
              <div class="alert alert-secondary" role="alert">
                No reviews yet. Be the first to review this ebook!
              </div>
            <?php endif; ?>
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