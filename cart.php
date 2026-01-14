<?php
session_start();
require_once 'config/db.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
  header('Location: login.php?redirect=cart.php');
  exit;
}

$user_id = $_SESSION['user_id'];

// Handle cart actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  if (isset($_POST['action'])) {
    switch ($_POST['action']) {
      case 'remove':
        $ebook_id = intval($_POST['ebook_id']);
        $stmt = $conn->prepare("DELETE FROM cart_items WHERE user_id = ? AND ebook_id = ?");
        $stmt->bind_param("ii", $user_id, $ebook_id);
        $stmt->execute();
        $stmt->close();
        break;
      case 'apply_promo':
        $_SESSION['promo_code'] = $_POST['promo_code'] ?? '';
        break;
    }
    header('Location: cart.php');
    exit;
  }
}

// Fetch cart items from database with ebook details
$cart_items = [];
$subtotal = 0;

$query = "SELECT c.cart_id, c.ebook_id, c.added_at, e.title, e.author, e.price, e.cover_image 
          FROM cart_items c 
          INNER JOIN ebooks e ON c.ebook_id = e.ebook_id 
          WHERE c.user_id = ? 
          ORDER BY c.added_at DESC";
$stmt = $conn->prepare($query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
  $row['quantity'] = 1; // Since table doesn't have quantity, default to 1
  $row['total'] = $row['price'];
  $subtotal += $row['total'];
  $cart_items[] = $row;
}
$stmt->close();

// Get cart count
$cart_count = count($cart_items);

// Calculate discount and tax
$discount_percent = 0;
$discount_amount = 0;
if (isset($_SESSION['promo_code']) && $_SESSION['promo_code'] === 'TECH30') {
  $discount_percent = 30;
  $discount_amount = $subtotal * 0.30;
}
// Calculate 12% tax on amount after discount
$taxable_amount = $subtotal - $discount_amount;
$tax_rate = 0.12;
$tax = $taxable_amount * $tax_rate;
$total = $subtotal - $discount_amount + $tax;

$title = 'Shopping Cart';
$extraStyles = '<style>
  /* Custom themed checkbox styling */
  .custom-checkbox {
    width: 22px;
    height: 22px;
    cursor: pointer;
    appearance: none;
    -webkit-appearance: none;
    border: 2px solid rgba(46, 204, 113, 0.5);
    border-radius: 6px;
    background-color: white;
    position: relative;
    transition: all 0.3s ease;
  }

  .custom-checkbox:checked {
    background-color: #2ecc71 !important;
    border-color: #2ecc71 !important;
    background-image: url("data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' viewBox=\'0 0 24 24\' fill=\'none\' stroke=\'%23ffffff\' stroke-width=\'3\' stroke-linecap=\'round\' stroke-linejoin=\'round\'%3E%3Cpolyline points=\'20 6 9 17 4 12\'%3E%3C/polyline%3E%3C/svg%3E");
    background-size: 16px;
    background-position: center;
    background-repeat: no-repeat;
  }

  .custom-checkbox:checked::after {
    content: \'\';
  }

  .cart-item-card {
    border: 1px solid rgba(46, 204, 113, 0.1);
    border-radius: 16px;
    transition: all 0.3s ease;
    background: rgba(255, 255, 255, 0.98);
  }

  .cart-item-card:hover {
    border-color: rgba(46, 204, 113, 0.3);
    box-shadow: 0 4px 12px rgba(46, 204, 113, 0.1);
    transform: translateY(-2px);
  }

  .summary-card {
    border-radius: 16px;
    border: 1px solid rgba(46, 204, 113, 0.15);
    background: rgba(255, 255, 255, 0.98);
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
  }

  .book-cover {
    border-radius: 12px;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    transition: transform 0.3s ease;
  }

  .book-cover:hover {
    transform: scale(1.05);
  }

  .select-all-container {
    background: rgba(46, 204, 113, 0.05);
    border-radius: 12px;
    padding: 14px 18px;
    border: 1px solid rgba(46, 204, 113, 0.15);
  }
</style>';
include 'includes/head.php';
?>

<body>
  <?php include 'includes/nav.php'; ?>

  <div class="container mt-5 pt-5 mt-lg-5 px-3">
    <div class="row">

      <div class="col-lg-8 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
          <h2 class="fw-bold mb-0" style="color: #2c3e50;">Your Cart</h2>
          <span class="badge rounded-pill px-3 py-2" style="background: rgba(46, 204, 113, 0.15); color: #2ecc71; font-weight: 600; font-size: 0.9rem;"><?php echo count($cart_items); ?> Item<?php echo count($cart_items) != 1 ? 's' : ''; ?></span>
        </div>

        <?php if (empty($cart_items)): ?>
          <div class="card p-5 text-center" style="border-radius: 16px; border: 1px solid rgba(46, 204, 113, 0.1);">
            <i class="bi bi-cart-x text-muted" style="font-size: 4rem;"></i>
            <h4 class="mt-3 text-muted">Your cart is empty</h4>
            <p class="text-muted">Browse our collection and add some books!</p>
            <a href="ebooks.php" class="btn btn-green mt-3 mx-auto px-4 py-2" style="width: fit-content; border-radius: 20px;">Browse E-Books</a>
          </div>
        <?php else: ?>
          <div class="select-all-container mb-4">
            <label class="form-check-label fw-semibold d-flex align-items-center gap-3 mb-0" style="cursor: pointer;">
              <input type="checkbox" class="custom-checkbox" id="selectAll">
              <span style="color: #2c3e50; font-size: 0.95rem;">Select All Items</span>
            </label>
          </div>
          <?php foreach ($cart_items as $item): ?>
            <div class="cart-item-card p-4 mb-3">
              <div class="row align-items-center">
                <div class="col-auto">
                  <input type="checkbox" class="custom-checkbox item-checkbox" data-price="<?php echo $item['price']; ?>" data-ebook-id="<?php echo $item['ebook_id']; ?>">
                </div>
                <div class="col-lg-2 col-3 mb-3 mb-lg-0">
                  <img src="<?php echo htmlspecialchars($item['cover_image'] ?? 'assets/img/ebook_cover/default.jpg'); ?>" class="img-fluid book-cover" style="max-height: 140px; object-fit: cover; width: 100%;">
                </div>
                <div class="col-lg-5 col-12 mb-3 mb-lg-0">
                  <h5 class="fw-bold mb-2" style="color: #2c3e50;"><?php echo htmlspecialchars($item['title']); ?></h5>
                  <p class="text-muted mb-3 small"><i class="bi bi-person me-1"></i><?php echo htmlspecialchars($item['author'] ?? 'Unknown Author'); ?></p>
                  <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-sm px-3 py-2 remove-btn" data-bs-toggle="modal" data-bs-target="#removeModal" data-ebook-id="<?php echo $item['ebook_id']; ?>" data-ebook-title="<?php echo htmlspecialchars($item['title']); ?>" style="background: rgba(220, 53, 69, 0.1); color: #dc3545; border: none; border-radius: 10px; font-weight: 500; transition: all 0.3s;" onmouseover="this.style.background='rgba(220, 53, 69, 0.2)'" onmouseout="this.style.background='rgba(220, 53, 69, 0.1)'">
                      <i class="bi bi-trash me-1"></i><span class="d-none d-sm-inline">Remove</span>
                    </button>
                  </div>
                </div>
                <div class="col-lg-3 col-12 text-lg-end">
                  <div class="fs-4 fw-bold text-green">₱<?php echo number_format($item['price'], 2); ?></div>
                  <?php if ($item['quantity'] > 1): ?>
                    <div class="small text-muted mt-1">Qty: <?php echo $item['quantity']; ?></div>
                    <div class="small fw-semibold mt-1">Total: ₱<?php echo number_format($item['total'], 2); ?></div>
                  <?php endif; ?>
                </div>
              </div>
            </div>
          <?php endforeach; ?>
        <?php endif; ?>

      </div>

      <div class="col-lg-4 mb-4 sticky-summary">
        <div class="summary-card p-4 position-sticky" style="top: 90px;">
          <h4 class="fw-bold mb-4" style="color: #2c3e50;">Order Summary</h4>

          <div class="d-flex justify-content-between align-items-center mb-3 py-2">
            <span class="text-muted">Selected Items</span>
            <span class="fw-semibold" style="color: #2ecc71;" id="selectedCount">0</span>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3 py-2">
            <span class="text-muted">Subtotal</span>
            <span class="fw-semibold" id="subtotalAmount">₱0.00</span>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-3 py-2" id="discountRow" style="display: none !important; background: rgba(46, 204, 113, 0.1); border-radius: 10px; padding: 12px !important;">
            <span class="fw-semibold" style="color: #2ecc71;">Discount (<span id="discountPercent"><?php echo $discount_percent; ?></span>%)</span>
            <span class="fw-semibold" style="color: #2ecc71;" id="discountAmount">-₱0.00</span>
          </div>

          <div class="d-flex justify-content-between align-items-center mb-4 py-2">
            <span class="text-muted">Tax</span>
            <span class="fw-semibold" id="taxAmount">₱0.00</span>
          </div>

          <div class="d-flex justify-content-between align-items-center py-3 mb-4" style="border-top: 2px solid rgba(46, 204, 113, 0.2);">
            <span class="fs-5 fw-bold" style="color: #2c3e50;">Total</span>
            <span class="fs-4 fw-bold text-green" id="totalAmount">₱0.00</span>
          </div>

          <button class="btn btn-green w-100 py-3" id="checkoutBtn" style="border-radius: 12px; font-weight: 600; font-size: 1rem;" disabled>
            <i class="bi bi-lock me-2"></i>Proceed to Checkout (<span id="checkoutCount">0</span>)
          </button>
        </div>
      </div>

    </div>
  </div>
  <?php include 'includes/footer.php'; ?>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>
  <script>
    // Price calculation based on checked items
    const promoCode = '<?php echo $_SESSION['promo_code'] ?? ''; ?>';
    const discountPercent = <?php echo $discount_percent; ?>;

    function updateOrderSummary() {
      const checkboxes = document.querySelectorAll('.item-checkbox');
      let subtotal = 0;
      let selectedCount = 0;

      checkboxes.forEach(checkbox => {
        if (checkbox.checked) {
          subtotal += parseFloat(checkbox.dataset.price);
          selectedCount++;
        }
      });

      // Calculate discount
      let discount = 0;
      if (discountPercent > 0) {
        discount = subtotal * (discountPercent / 100);
        document.getElementById('discountRow').style.display = 'flex';
      } else {
        document.getElementById('discountRow').style.display = 'none';
      }

      // Calculate 12% tax on amount after discount
      const taxableAmount = subtotal - discount;
      const taxRate = 0.12;
      const tax = taxableAmount * taxRate;

      // Calculate total
      const total = subtotal - discount + tax;

      // Update UI
      document.getElementById('selectedCount').textContent = selectedCount;
      document.getElementById('subtotalAmount').textContent = '₱' + subtotal.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('discountAmount').textContent = '-₱' + discount.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('taxAmount').textContent = '₱' + tax.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('totalAmount').textContent = '₱' + total.toFixed(2).replace(/\B(?=(\d{3})+(?!\d))/g, ',');
      document.getElementById('checkoutCount').textContent = selectedCount;

      // Disable checkout button if no items selected
      const checkoutBtn = document.getElementById('checkoutBtn');
      if (selectedCount === 0) {
        checkoutBtn.disabled = true;
        checkoutBtn.classList.add('disabled');
      } else {
        checkoutBtn.disabled = false;
        checkoutBtn.classList.remove('disabled');
      }
    }

    // Select All functionality
    const selectAllCheckbox = document.getElementById('selectAll');
    if (selectAllCheckbox) {
      selectAllCheckbox.addEventListener('change', function() {
        const checkboxes = document.querySelectorAll('.item-checkbox');
        checkboxes.forEach(checkbox => {
          checkbox.checked = this.checked;
        });
        updateOrderSummary();
      });
    }

    // Individual checkbox change
    document.querySelectorAll('.item-checkbox').forEach(checkbox => {
      checkbox.addEventListener('change', function() {
        // Update select all checkbox
        const allCheckboxes = document.querySelectorAll('.item-checkbox');
        const allChecked = Array.from(allCheckboxes).every(cb => cb.checked);
        if (selectAllCheckbox) {
          selectAllCheckbox.checked = allChecked;
        }
        updateOrderSummary();
      });
    });

    // Checkout button click - redirect with selected items
    document.getElementById('checkoutBtn')?.addEventListener('click', function() {
      const selectedIds = [];
      document.querySelectorAll('.item-checkbox:checked').forEach(checkbox => {
        selectedIds.push(checkbox.dataset.ebookId);
      });

      if (selectedIds.length > 0) {
        window.location.href = 'checkout.php?items=' + selectedIds.join(',');
      }
    });
  </script>

  <!-- Remove Confirmation Modal -->
  <div class="modal fade" id="removeModal" tabindex="-1" aria-labelledby="removeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content" style="border-radius: 16px; border: none;">
        <div class="modal-header border-0 pb-0">
          <h5 class="modal-title fw-bold" id="removeModalLabel" style="color: #2c3e50;">Remove Item</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body py-4">
          <div class="text-center mb-3">
            <i class="bi bi-exclamation-triangle" style="font-size: 3rem; color: #dc3545;"></i>
          </div>
          <p class="text-center mb-0" style="color: #2c3e50; font-size: 1rem;">
            Are you sure you want to remove <strong id="removeItemTitle"></strong> from your cart?
          </p>
        </div>
        <div class="modal-footer border-0 pt-0 gap-2">
          <button type="button" class="btn px-4 py-2" data-bs-dismiss="modal" style="background: rgba(108, 117, 125, 0.1); color: #6c757d; border: none; border-radius: 10px; font-weight: 500;">Cancel</button>
          <form method="POST" id="removeForm" style="display: inline;">
            <input type="hidden" name="action" value="remove">
            <input type="hidden" name="ebook_id" id="removeEbookId" value="">
            <button type="submit" class="btn px-4 py-2" style="background: #dc3545; color: white; border: none; border-radius: 10px; font-weight: 500;">
              <i class="bi bi-trash me-1"></i>Remove
            </button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <script>
    // Handle remove modal
    document.querySelectorAll('.remove-btn').forEach(button => {
      button.addEventListener('click', function() {
        const ebookId = this.dataset.ebookId;
        const ebookTitle = this.dataset.ebookTitle;
        document.getElementById('removeEbookId').value = ebookId;
        document.getElementById('removeItemTitle').textContent = ebookTitle;
      });
    });
  </script>

  <?php include 'chatbot/chatbot.php'; ?>
</body>

</html>