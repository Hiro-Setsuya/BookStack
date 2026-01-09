<?php
session_start();

// Authentication Guard: Check if the admin is logged in
if (!isset($_SESSION['admin_id'])) {
    header('Location: login.php');
    exit;
}

// Include database connection
require_once '../config/db.php';

// Sync Admin Name from session variable established in login.php
$adminName = $_SESSION['admin_name'] ?? 'Admin';

// Calculate total sales
$total_sales_query = "SELECT COALESCE(SUM(total_amount), 0) as total_sales FROM orders WHERE status = 'completed'";
$total_sales_result = executeQuery($total_sales_query);
$total_sales_data = mysqli_fetch_assoc($total_sales_result);
$total_sales = $total_sales_data['total_sales'];

// Fetch all orders with user information and order items
$orders_query = "
    SELECT 
        o.order_id,
        o.user_id,
        o.total_amount,
        o.status,
        o.payment_id,
        o.created_at,
        o.updated_at,
        u.user_name,
        u.email,
        GROUP_CONCAT(e.title SEPARATOR ', ') as ebook_titles,
        COUNT(oi.order_item_id) as item_count
    FROM orders o
    INNER JOIN users u ON o.user_id = u.user_id
    LEFT JOIN order_items oi ON o.order_id = oi.order_id
    LEFT JOIN ebooks e ON oi.ebook_id = e.ebook_id
    GROUP BY o.order_id
    ORDER BY o.created_at DESC
";
$orders_result = executeQuery($orders_query);

$title = 'Manage Orders';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'orders';
    include '../includes/admin-nav.php'; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h5 class="fw-bold mb-1">Manage Orders</h5>
            <p class="text-muted small mb-0">Monitor transactions and view sales history.</p>
        </div>
        <div class="text-end">
            <p class="text-muted small mb-1">Total Sales (Completed)</p>
            <h4 class="fw-bold text-success mb-0">₱<?php echo number_format($total_sales, 2); ?></h4>
        </div>
    </div>

    <div class="card data-card p-4">
        <div class="table-responsive">
            <table class="table align-middle">
                <thead>
                    <tr class="small text-muted text-uppercase">
                        <th>Order ID</th>
                        <th>Customer</th>
                        <th>Details</th>
                        <th>Date</th>
                        <th>Amount</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if ($orders_result && mysqli_num_rows($orders_result) > 0): ?>
                        <?php while ($order = mysqli_fetch_assoc($orders_result)): ?>
                            <tr>
                                <td class="fw-semibold">#<?php echo $order['order_id']; ?></td>
                                <td>
                                    <div class="fw-semibold"><?php echo htmlspecialchars($order['user_name']); ?></div>
                                    <div class="small text-muted"><?php echo htmlspecialchars($order['email']); ?></div>
                                </td>
                                <td>
                                    <div class="small">
                                        <?php
                                        $titles = htmlspecialchars($order['ebook_titles'] ?? 'No items');
                                        echo strlen($titles) > 50 ? substr($titles, 0, 50) . '...' : $titles;
                                        ?>
                                    </div>
                                    <div class="small text-muted"><?php echo $order['item_count']; ?> item(s)</div>
                                </td>
                                <td class="small"><?php echo date('M d, Y', strtotime($order['created_at'])); ?></td>
                                <td class="fw-semibold">₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                <td>
                                    <span class="status-badge status-<?php echo $order['status']; ?>">
                                        <?php echo ucfirst($order['status']); ?>
                                    </span>
                                </td>
                            </tr>
                        <?php endwhile; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <div class="text-muted">
                                    <i class="bi bi-inbox" style="font-size: 3rem;"></i>
                                    <p class="mt-3">No orders found</p>
                                </div>
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
    </main>
    </div>
    </div>

    <?php include '../includes/admin-footer.php'; ?>