<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Sync Admin Name from session variable established in login.php
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Include database connection
require_once '../config/db.php';

// Initialize message variables
$message = '';
$messageType = '';

// Handle Create E-Book
if (isset($_POST['create_ebook'])) {
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $author = mysqli_real_escape_string($conn, trim($_POST['author']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $price = (float)$_POST['price'];
    $file_path = mysqli_real_escape_string($conn, trim($_POST['file_path']));
    $cover_image = mysqli_real_escape_string($conn, trim($_POST['cover_image'] ?? ''));

    if (!empty($title) && !empty($file_path) && $price >= 0) {
        $categoryPart = $category_id ? $category_id : 'NULL';
        $query = "INSERT INTO ebooks (title, author, description, category_id, price, file_path, cover_image) 
                  VALUES ('$title', '$author', '$description', $categoryPart, $price, '$file_path', '$cover_image')";
        if (executeQuery($query)) {
            $message = 'E-Book created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error creating e-book: ' . mysqli_error($conn);
            $messageType = 'danger';
        }
    } else {
        $message = 'Please fill in all required fields!';
        $messageType = 'warning';
    }
}

// Handle Update E-Book
if (isset($_POST['update_ebook'])) {
    $ebook_id = (int)$_POST['ebook_id'];
    $title = mysqli_real_escape_string($conn, trim($_POST['title']));
    $author = mysqli_real_escape_string($conn, trim($_POST['author']));
    $description = mysqli_real_escape_string($conn, trim($_POST['description'] ?? ''));
    $category_id = !empty($_POST['category_id']) ? (int)$_POST['category_id'] : NULL;
    $price = (float)$_POST['price'];
    $file_path = mysqli_real_escape_string($conn, trim($_POST['file_path']));
    $cover_image = mysqli_real_escape_string($conn, trim($_POST['cover_image'] ?? ''));

    if (!empty($title) && !empty($file_path) && $ebook_id > 0) {
        $categoryPart = $category_id ? "category_id=$category_id" : "category_id=NULL";
        $query = "UPDATE ebooks SET title='$title', author='$author', description='$description', 
                  $categoryPart, price=$price, file_path='$file_path', cover_image='$cover_image' 
                  WHERE ebook_id=$ebook_id";
        if (executeQuery($query)) {
            $message = 'E-Book updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating e-book: ' . mysqli_error($conn);
            $messageType = 'danger';
        }
    } else {
        $message = 'Invalid data provided!';
        $messageType = 'warning';
    }
}

// Handle Delete E-Book
if (isset($_POST['delete_ebook'])) {
    $ebook_id = (int)$_POST['ebook_id'];

    if ($ebook_id > 0) {
        $query = "DELETE FROM ebooks WHERE ebook_id=$ebook_id";
        if (executeQuery($query)) {
            $message = 'E-Book deleted successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error deleting e-book: ' . mysqli_error($conn);
            $messageType = 'danger';
        }
    }
}

// Fetch all categories for dropdown
$categoriesQuery = "SELECT category_id, name FROM categories ORDER BY name ASC";
$categoriesResult = executeQuery($categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $row;
    }
}

// Get search and filter parameters
$searchTerm = isset($_GET['search']) ? mysqli_real_escape_string($conn, trim($_GET['search'])) : '';
$filterCategory = isset($_GET['category']) ? (int)$_GET['category'] : 0;

// Fetch all ebooks with category names and apply filters
$ebooksQuery = "SELECT e.ebook_id, e.title, e.author, e.description, e.price, e.file_path, e.cover_image, 
                       e.created_at, c.name as category_name, c.category_id
                FROM ebooks e
                LEFT JOIN categories c ON e.category_id = c.category_id
                WHERE 1=1";

// Add search filter
if (!empty($searchTerm)) {
    $ebooksQuery .= " AND (e.title LIKE '%$searchTerm%' OR e.author LIKE '%$searchTerm%')";
}

// Add category filter
if ($filterCategory > 0) {
    $ebooksQuery .= " AND e.category_id = $filterCategory";
}

$ebooksQuery .= " ORDER BY e.created_at DESC";
$ebooksResult = executeQuery($ebooksQuery);
$ebooks = [];
if ($ebooksResult) {
    while ($row = mysqli_fetch_assoc($ebooksResult)) {
        $ebooks[] = $row;
    }
}

// Calculate statistics
$totalEbooks = count($ebooks);
$totalRevenue = 0;
$avgPrice = 0;
foreach ($ebooks as $ebook) {
    $totalRevenue += $ebook['price'];
}
if ($totalEbooks > 0) {
    $avgPrice = $totalRevenue / $totalEbooks;
}

$title = 'Manage EBooks';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'ebooks';
    include '../includes/admin-nav.php'; ?>
    <?php
    $message = !empty($message) ? $message : '';
    $messageType = !empty($messageType) ? $messageType : '';
    include '../includes/notification.php';
    ?>

    <div class="d-flex justify-content-between align-items-start mb-4">
        <div>
            <h5 class="fw-bold mb-1">Manage E-Books</h5>
            <p class="text-muted small mb-0">Manage your digital library inventory, upload ebooks, and update pricing.</p>
        </div>
    </div>

    <div class="row g-4">
        <div class="col-12 col-xl-8">
            <div class="card data-card p-4 mb-4">
                <form method="GET" action="" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Search books</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-search text-muted"></i></span>
                                <input type="text" name="search" class="form-control border-start-0" placeholder="Title or author" value="<?php echo htmlspecialchars($searchTerm); ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Category</label>
                            <select name="category" class="form-select form-select-sm">
                                <option value="0" <?php echo $filterCategory == 0 ? 'selected' : ''; ?>>All Categories</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>" <?php echo $filterCategory == $cat['category_id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small fw-bold">&nbsp;</label>
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary" title="Filter"><i class="bi bi-funnel-fill"></i></button>
                                <?php if (!empty($searchTerm) || $filterCategory > 0): ?>
                                    <a href="manage-ebooks.php" class="btn btn-sm btn-outline-secondary" title="Clear filters"><i class="bi bi-x-lg"></i></a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table align-middle border-top">
                        <thead>
                            <tr>
                                <th style="width: 80px;">COVER</th>
                                <th>BOOK DETAILS</th>
                                <th>PRICE</th>
                                <th>CATEGORY</th>
                                <th class="text-end">ACTIONS</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ebooks)): ?>
                                <tr>
                                    <td colspan="5" class="text-center py-5 text-muted">
                                        <i class="bi bi-book" style="font-size: 3rem; opacity: 0.3;"></i>
                                        <p class="mt-3">No e-books found. Click "Add E-Book" to create one.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($ebooks as $ebook): ?>
                                    <tr>
                                        <td>
                                            <?php if (!empty($ebook['cover_image'])): ?>
                                                <?php
                                                $coverSrc = $ebook['cover_image'];
                                                // Check if it's a URL (http/https) or data URL (base64), if not prepend ../
                                                if (!preg_match('/^(https?:\/\/|data:)/i', $coverSrc)) {
                                                    $coverSrc = '../' . $coverSrc;
                                                }
                                                ?>
                                                <img src="<?php echo htmlspecialchars($coverSrc); ?>" alt="Cover" style="width: 60px; height: 80px; object-fit: cover; border-radius: 4px;">
                                            <?php else: ?>
                                                <div style="width: 60px; height: 80px; background: #f0f0f0; border-radius: 4px; display: flex; align-items: center; justify-content: center;">
                                                    <i class="bi bi-book text-muted"></i>
                                                </div>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <div class="fw-bold text-dark"><?php echo htmlspecialchars($ebook['title']); ?></div>
                                            <div class="text-muted small"><?php echo htmlspecialchars($ebook['author']); ?></div>
                                        </td>
                                        <td>₱<?php echo number_format($ebook['price'], 2); ?></td>
                                        <td>
                                            <?php if ($ebook['category_name']): ?>
                                                <?php echo htmlspecialchars($ebook['category_name']); ?>
                                            <?php else: ?>
                                                <span class="text-muted">Uncategorized</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <button class="btn btn-sm btn-outline-primary mb-1" onclick='editEbook(<?php echo json_encode($ebook); ?>)' title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </button>
                                            <button class="btn btn-sm btn-outline-danger" onclick="deleteEbook(<?php echo $ebook['ebook_id']; ?>, '<?php echo htmlspecialchars(addslashes($ebook['title'])); ?>')" title="Delete">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <div class="d-flex justify-content-between align-items-center mt-3">
                    <p class="text-muted small mb-0">Showing <?php echo $totalEbooks > 0 ? '1-' . $totalEbooks : '0'; ?> of <?php echo $totalEbooks; ?> books</p>
                </div>
            </div>
        </div>

        <div class="col-12 col-xl-4">
            <div class="card data-card p-4">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h6 class="fw-bold mb-0">Add New E-Book</h6>
                    <a href="#" class="text-decoration-none small text-success" onclick="clearSidebarForm(); return false;">Clear all</a>
                </div>

                <form id="sidebarForm" method="POST" action="">
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Title</label>
                        <input type="text" class="form-control form-control-sm" name="title" placeholder="Enter book title" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Author <span class="text-muted fw-normal">(Optional)</span></label>
                        <input type="text" class="form-control form-control-sm" name="author" placeholder="Enter author name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Description <span class="text-muted fw-normal">(Optional)</span></label>
                        <textarea class="form-control form-control-sm" name="description" rows="2" placeholder="Brief description"></textarea>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-7">
                            <label class="form-label small fw-bold">Category</label>
                            <select class="form-select form-select-sm" name="category_id">
                                <option value="">Select category</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-5">
                            <label class="form-label small fw-bold">Price (₱)</label>
                            <input type="number" class="form-control form-control-sm" name="price" placeholder="0.00" step="0.01" min="0" required>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cover Image</label>
                        <input type="text" class="form-control form-control-sm" name="cover_image" placeholder="Image URL or path">
                        <small class="text-muted" style="font-size: 0.7rem;">URL or relative path</small>
                    </div>
                    <div class="mb-4">
                        <label class="form-label small fw-bold">Google Drive File ID</label>
                        <input type="text" class="form-control form-control-sm" name="file_path" placeholder="Enter file ID" required>
                        <small class="text-muted" style="font-size: 0.7rem;">From Google Drive share link</small>
                    </div>
                    <div class="d-grid">
                        <button type="submit" name="create_ebook" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add E-Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </main>
    </div>
    </div>

    <!-- E-Book Modal for Create/Edit -->
    <div class="modal fade" id="ebookModal" tabindex="-1" aria-labelledby="ebookModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="ebookModalLabel">Add E-Book</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="ebook_id" id="ebook_id">
                        <div class="row">
                            <div class="col-md-8">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Title <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="title" name="title" placeholder="Enter the book title" required>
                                </div>
                                <div class="mb-3">
                                    <label for="author" class="form-label">Author <span class="text-muted fw-normal">(Optional)</span></label>
                                    <input type="text" class="form-control" id="author" name="author" placeholder="Enter author name">
                                </div>
                                <div class="mb-3">
                                    <label for="description" class="form-label">Description <span class="text-muted fw-normal">(Optional)</span></label>
                                    <textarea class="form-control" id="description" name="description" rows="3" placeholder="Brief description of the e-book"></textarea>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="mb-3">
                                    <label for="price" class="form-label">Price (₱) <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control" id="price" name="price" step="0.01" min="0" placeholder="0.00" required>
                                </div>
                                <div class="mb-3">
                                    <label for="category_id" class="form-label">Category <span class="text-muted fw-normal">(Optional)</span></label>
                                    <select class="form-select" id="category_id" name="category_id">
                                        <option value="">Select category</option>
                                        <?php foreach ($categories as $cat): ?>
                                            <option value="<?php echo $cat['category_id']; ?>"><?php echo htmlspecialchars($cat['name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="file_path" class="form-label">Google Drive File ID <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="file_path" name="file_path" placeholder="Enter file ID" required>
                                    <small class="text-muted">Get from Google Drive share link</small>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="cover_image" class="form-label">Cover Image <span class="text-muted fw-normal">(Optional)</span></label>
                                    <input type="text" class="form-control" id="cover_image" name="cover_image" placeholder="Image URL or path">
                                    <small class="text-muted">Full URL or relative path</small>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_ebook" id="submitBtn" class="btn btn-primary">Create E-Book</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="ebook_id" id="delete_ebook_id">
                        <p>Are you sure you want to delete the e-book "<strong id="delete_ebook_title"></strong>"?</p>
                        <p class="text-muted small">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_ebook" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        // Clear sidebar form fields
        function clearSidebarForm() {
            document.getElementById('sidebarForm').reset();
        }

        // Reset form to create mode
        function resetForm() {
            document.getElementById('ebookModalLabel').textContent = 'Add E-Book';
            document.getElementById('ebook_id').value = '';
            document.getElementById('title').value = '';
            document.getElementById('author').value = '';
            document.getElementById('description').value = '';
            document.getElementById('price').value = '';
            document.getElementById('category_id').value = '';
            document.getElementById('file_path').value = '';
            document.getElementById('cover_image').value = '';
            document.getElementById('submitBtn').name = 'create_ebook';
            document.getElementById('submitBtn').textContent = 'Create E-Book';
        }

        // Edit ebook
        function editEbook(ebook) {
            document.getElementById('ebookModalLabel').textContent = 'Edit E-Book';
            document.getElementById('ebook_id').value = ebook.ebook_id;
            document.getElementById('title').value = ebook.title;
            document.getElementById('author').value = ebook.author;
            document.getElementById('description').value = ebook.description || '';
            document.getElementById('price').value = ebook.price;
            document.getElementById('category_id').value = ebook.category_id || '';
            document.getElementById('file_path').value = ebook.file_path;
            document.getElementById('cover_image').value = ebook.cover_image || '';
            document.getElementById('submitBtn').name = 'update_ebook';
            document.getElementById('submitBtn').textContent = 'Update E-Book';

            var modal = new bootstrap.Modal(document.getElementById('ebookModal'));
            modal.show();
        }

        // Delete ebook
        function deleteEbook(id, title) {
            document.getElementById('delete_ebook_id').value = id;
            document.getElementById('delete_ebook_title').textContent = title;

            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>

    <?php include '../includes/admin-footer.php'; ?>