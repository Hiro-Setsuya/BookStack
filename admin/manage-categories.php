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

// Handle Create Category
if (isset($_POST['create_category'])) {
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));

    if (!empty($name)) {
        $query = "INSERT INTO categories (name) VALUES ('$name')";
        if (executeQuery($query)) {
            $message = 'Category created successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error creating category: ' . mysqli_error($conn);
            $messageType = 'danger';
        }
    } else {
        $message = 'Category name is required!';
        $messageType = 'warning';
    }
}

// Handle Update Category
if (isset($_POST['update_category'])) {
    $category_id = (int)$_POST['category_id'];
    $name = mysqli_real_escape_string($conn, trim($_POST['name']));

    if (!empty($name) && $category_id > 0) {
        $query = "UPDATE categories SET name='$name' WHERE category_id=$category_id";
        if (executeQuery($query)) {
            $message = 'Category updated successfully!';
            $messageType = 'success';
        } else {
            $message = 'Error updating category: ' . mysqli_error($conn);
            $messageType = 'danger';
        }
    } else {
        $message = 'Invalid data provided!';
        $messageType = 'warning';
    }
}

// Handle Delete Category
if (isset($_POST['delete_category'])) {
    $category_id = (int)$_POST['category_id'];

    if ($category_id > 0) {
        // Check if category has ebooks
        $checkQuery = "SELECT COUNT(*) as count FROM ebooks WHERE category_id=$category_id";
        $checkResult = executeQuery($checkQuery);
        $checkData = mysqli_fetch_assoc($checkResult);

        if ($checkData['count'] > 0) {
            $message = 'Cannot delete category with existing ebooks. Please reassign ebooks first.';
            $messageType = 'warning';
        } else {
            $query = "DELETE FROM categories WHERE category_id=$category_id";
            if (executeQuery($query)) {
                $message = 'Category deleted successfully!';
                $messageType = 'success';
            } else {
                $message = 'Error deleting category: ' . mysqli_error($conn);
                $messageType = 'danger';
            }
        }
    }
}

// Fetch all categories with ebook count
$categoriesQuery = "SELECT c.category_id, c.name, c.created_at, 
                           COUNT(e.ebook_id) as ebook_count 
                    FROM categories c 
                    LEFT JOIN ebooks e ON c.category_id = e.category_id 
                    GROUP BY c.category_id 
                    ORDER BY c.name ASC";
$categoriesResult = executeQuery($categoriesQuery);
$categories = [];
if ($categoriesResult) {
    while ($row = mysqli_fetch_assoc($categoriesResult)) {
        $categories[] = $row;
    }
}

// Calculate statistics
$totalCategories = count($categories);
$totalEbooks = 0;
$emptyCategories = 0;
foreach ($categories as $cat) {
    $totalEbooks += $cat['ebook_count'];
    if ($cat['ebook_count'] == 0) {
        $emptyCategories++;
    }
}
?>
<!doctype html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Manage Categories - BookStack</title>

    <!-- Google Fonts: Manrope -->
    <link rel="preconnect" href="https://fonts.googleapis.com" />
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin />
    <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@200..800&display=swap" rel="stylesheet" />

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-sRIl4kxILFvY47J16cr9ZwB07vP4J8+LH7qKQnuqkuIAvNWLzeN8tE5YBujZqJLB" crossorigin="anonymous">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">
</head>

<body>

    <div class="d-lg-none bg-white p-3 border-bottom d-flex justify-content-between align-items-center">
        <div class="navbar-brand fw-bold text-green brand-title">
            <span>BookStack</span>
        </div>
        <button class="btn btn-light border" type="button" onclick="document.getElementById('sidebar-menu').classList.toggle('show')">
            <i class="bi bi-list"></i>
        </button>
    </div>

    <div class="container-fluid p-0">
        <div class="d-flex">

            <nav class="sidebar d-flex flex-column pb-4" id="sidebar-menu">
                <div class="p-4 mb-2 sidebar-brand">
                    <div class="navbar-brand fw-bold text-green brand-title">
                        <span>BookStack</span>
                    </div>
                </div>

                <div class="nav flex-column mb-auto">
                    <a href="dashboard.php" class="nav-link"><i class="bi bi-grid-fill me-3"></i>Dashboard</a>
                    <a href="manage-ebooks.php" class="nav-link"><i class="bi bi-journal-text me-3"></i>E-Books</a>
                    <a href="manage-categories.php" class="nav-link active"><i class="bi bi-layers me-3"></i>Categories</a>
                    <a href="manage-users.php" class="nav-link"><i class="bi bi-people me-3"></i>Users</a>
                    <a href="manage-orders.php" class="nav-link"><i class="bi bi-cart me-3"></i>Orders</a>
                    <a href="manage-verification.php" class="nav-link"><i class="bi bi-shield-check me-3"></i>Verifications</a>
                    <a href="manage-messages.php" class="nav-link"><i class="bi bi-envelope me-3"></i>Messages</a>


                    <a href="logout.php" class="nav-link text-danger mt-2"><i class="bi bi-box-arrow-left me-3"></i>Logout</a>

                    <div class="px-3 mt-3">
                        <div class="d-flex align-items-center px-3 py-2 bg-light rounded-3">
                            <img src="https://ui-avatars.com/api/?name=<?php echo urlencode($adminName); ?>&background=198754&color=fff" class="rounded-circle me-2" width="35" height="35">
                            <div>
                                <p class="mb-0 small fw-bold text-dark"><?php echo htmlspecialchars($adminName); ?></p>
                                <p class="mb-0 text-muted" style="font-size: 0.7rem;">System Administrator</p>
                            </div>
                        </div>
                    </div>
                </div>
            </nav>

            <main class="main-content w-100">
                <?php if (!empty($message)): ?>
                    <div class="alert alert-<?php echo $messageType; ?> alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($message); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
                    <div>
                        <h5 class="fw-bold mb-1">Manage Categories</h5>
                        <p class="text-muted small mb-0">Organize e-book genres, edit details, or add new classifications.</p>
                    </div>
                    <button class="btn btn-primary px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#categoryModal" onclick="resetForm()" style="background-color: #00a3ff; border: none; border-radius: 10px;">
                        <i class="bi bi-plus-lg me-2"></i>Add Category
                    </button>
                </div>

                <div class="row g-3 mb-4 text-center">
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total Categories</p>
                            <h4 class="fw-bold mb-0 text-secondary"><?php echo $totalCategories; ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Total E-Books</p>
                            <h4 class="fw-bold mb-0 text-secondary"><?php echo $totalEbooks; ?></h4>
                        </div>
                    </div>
                    <div class="col-12 col-md-4">
                        <div class="card stat-card p-4">
                            <p class="text-muted small mb-1">Empty Categories</p>
                            <h4 class="fw-bold mb-0 text-secondary"><?php echo $emptyCategories; ?></h4>
                        </div>
                    </div>
                </div>

                <div class="card data-card border-0 shadow-sm">
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead>
                                <tr style="font-size: 0.75rem; color: #64748b; text-transform: uppercase;">
                                    <th>Category Name</th>
                                    <th>Books</th>
                                    <th class="text-end pe-4">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($categories)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center py-5">
                                            <div class="placeholder-box py-4 mx-4" style="border: 2px dashed #e5e7eb; border-radius: 8px; color: #9ca3af;">
                                                <i class="bi bi-inbox" style="font-size: 3rem; opacity: 0.3;"></i>
                                                <p class="mt-3 mb-0">No categories found. Click "Add Category" to create one.</p>
                                            </div>
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($categories as $category): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="p-2 bg-light rounded-3 me-3 text-center" style="width: 35px;">
                                                        <i class="bi bi-tag text-primary"></i>
                                                    </div>
                                                    <span class="fw-bold text-dark"><?php echo htmlspecialchars($category['name']); ?></span>
                                                </div>
                                            </td>
                                            <td><span class="badge bg-light text-dark rounded-pill px-3"><?php echo $category['ebook_count']; ?></span></td>
                                            <td class="text-end pe-4">
                                                <button class="btn btn-sm btn-outline-primary me-2" onclick="editCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')" title="Edit">
                                                    <i class="bi bi-pencil"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" onclick="deleteCategory(<?php echo $category['category_id']; ?>, '<?php echo htmlspecialchars(addslashes($category['name'])); ?>')" title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <!-- Category Modal for Create/Edit -->
    <div class="modal fade" id="categoryModal" tabindex="-1" aria-labelledby="categoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="categoryModalLabel">Add Category</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="">
                    <div class="modal-body">
                        <input type="hidden" name="category_id" id="category_id">
                        <div class="mb-3">
                            <label for="name" class="form-label">Category Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="name" name="name" required>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="create_category" id="submitBtn" class="btn btn-primary">Create Category</button>
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
                        <input type="hidden" name="category_id" id="delete_category_id">
                        <p>Are you sure you want to delete the category "<strong id="delete_category_name"></strong>"?</p>
                        <p class="text-muted small">This action cannot be undone.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" name="delete_category" class="btn btn-danger">Delete</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        // Reset form to create mode
        function resetForm() {
            document.getElementById('categoryModalLabel').textContent = 'Add Category';
            document.getElementById('category_id').value = '';
            document.getElementById('name').value = '';
            document.getElementById('submitBtn').name = 'create_category';
            document.getElementById('submitBtn').textContent = 'Create Category';
        }

        // Edit category
        function editCategory(id, name) {
            document.getElementById('categoryModalLabel').textContent = 'Edit Category';
            document.getElementById('category_id').value = id;
            document.getElementById('name').value = name;
            document.getElementById('submitBtn').name = 'update_category';
            document.getElementById('submitBtn').textContent = 'Update Category';

            var modal = new bootstrap.Modal(document.getElementById('categoryModal'));
            modal.show();
        }

        // Delete category
        function deleteCategory(id, name) {
            document.getElementById('delete_category_id').value = id;
            document.getElementById('delete_category_name').textContent = name;

            var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
            modal.show();
        }
    </script>
</body>

</html>