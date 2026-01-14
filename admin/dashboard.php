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

// Helper function to get single value from query
function getSingleValue($query)
{
    $conn = $GLOBALS['conn'];
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) > 0) {
        $row = mysqli_fetch_assoc($result);
        return array_values($row)[0]; // Get first column value
    }
    return 0;
}

// Helper function to get multiple rows
function getRows($query)
{
    $conn = $GLOBALS['conn'];
    $result = mysqli_query($conn, $query);
    $rows = [];
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
    }
    return $rows;
}

// Fetch statistics from database using your executeQuery function
try {
    // Total Users
    $total_users = getSingleValue("SELECT COUNT(*) as total FROM users WHERE role = 'user'");

    // Total E-books
    $total_ebooks = getSingleValue("SELECT COUNT(*) as total FROM ebooks");

    // Total Sales (completed orders)
    $total_sales = getSingleValue("SELECT COALESCE(SUM(total_amount), 0) as total FROM orders WHERE status = 'completed'");

    // Recent Orders (last 5)
    $recent_orders = getRows("
        SELECT o.order_id, o.total_amount, o.status, o.created_at, 
               u.user_name
        FROM orders o
        JOIN users u ON o.user_id = u.user_id
        ORDER BY o.created_at DESC
        LIMIT 5
    ");

    // Recent Users (last 5)
    $recent_users = getRows("
        SELECT user_name, email, created_at, is_account_verified
        FROM users 
        WHERE role = 'user'
        ORDER BY created_at DESC
        LIMIT 5
    ");

    // Sales by Month (last 3 months only - shorter chart)
    $monthly_sales = getRows("
        SELECT 
            DATE_FORMAT(created_at, '%b') as month,
            COALESCE(SUM(total_amount), 0) as monthly_sales
        FROM orders 
        WHERE status = 'completed' 
        AND created_at >= DATE_SUB(NOW(), INTERVAL 3 MONTH)
        GROUP BY DATE_FORMAT(created_at, '%Y-%m')
        ORDER BY created_at DESC
        LIMIT 3
    ");

    // // If no data, provide sample data for demonstration
    // if (empty($monthly_sales)) {
    //     $monthly_sales = [
    //         ['month' => 'Jan', 'monthly_sales' => 25450.00],
    //         ['month' => 'Dec', 'monthly_sales' => 18920.50],
    //         ['month' => 'Nov', 'monthly_sales' => 32100.75]
    //     ];
    // }
} catch (Exception $e) {
    // Handle database error gracefully
    $total_users = 0;
    $total_ebooks = 0;
    $total_sales = 0;
    $recent_orders = [];
    $recent_users = [];
    $monthly_sales = [];
}

$title = 'Admin Dashboard';
$extraStyles = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';
include '../includes/head.php';
?>

<body>

    <?php $currentPage = 'dashboard';
    include '../includes/admin-nav.php'; ?>
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <h5 class="fw-bold mb-0">Dashboard Overview</h5>
        <div class="text-muted">
            Last updated: <?php echo date('M j, Y \a\t g:i A'); ?>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Total Users</p>
                        <h3 class="fw-bold mb-0 text-secondary"><?php echo number_format($total_users); ?></h3>
                    </div>
                    <div class="p-3 rounded-circle bg-light">
                        <i class="bi bi-people text-green" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Total Sales</p>
                        <h3 class="fw-bold mb-0 text-secondary">₱<?php echo number_format($total_sales, 2); ?></h3>
                    </div>
                    <div class="p-3 rounded-circle bg-light">
                        <i class="bi bi-cash-coin text-success" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="card stat-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <p class="text-muted small mb-1">Total E-Books</p>
                        <h3 class="fw-bold mb-0 text-secondary"><?php echo number_format($total_ebooks); ?></h3>
                    </div>
                    <div class="p-3 rounded-circle bg-light">
                        <i class="bi bi-journal text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Compact Sales Chart -->
    <div class="card data-card p-4 mb-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h6 class="fw-bold mb-1">Sales Overview</h6>
            </div>
        </div>
        <div class="chart-container">
            <canvas id="salesChart"></canvas>
        </div>
    </div>

    <!-- Recent Orders -->
    <div class="row">
        <div class="col-md-6 mb-4">
            <div class="card data-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Recent Orders</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Order ID</th>
                                <th>Student</th>
                                <th>Amount</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_orders)): ?>
                                <?php foreach ($recent_orders as $order): ?>
                                    <tr>
                                        <td>#<?php echo $order['order_id']; ?></td>
                                        <td><?php echo htmlspecialchars($order['user_name']); ?></td>
                                        <td>₱<?php echo number_format($order['total_amount'], 2); ?></td>
                                        <td>
                                            <span class="status-badge status-<?php echo $order['status']; ?>">
                                                <?php echo ucfirst($order['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="4" class="text-center py-3 text-muted">
                                        No recent orders
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Recent Users -->
        <div class="col-md-6 mb-4">
            <div class="card data-card p-4 h-100">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h6 class="fw-bold mb-0">Recent Users</h6>
                </div>
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Verified</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($recent_users)): ?>
                                <?php foreach ($recent_users as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['user_name']); ?></td>
                                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['is_account_verified'] ? 'verified-yes' : 'verified-no'; ?>">
                                                <?php echo $user['is_account_verified'] ? 'Yes' : 'No'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="3" class="text-center py-3 text-muted">
                                        No recent users
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
    </main>
    </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js" integrity="sha384-FKyoEForCGlyvwx9Hj09JcYn3nv7wiPVlz7YYwJrWVcXK/BmnVDxM+D2scQbITxI" crossorigin="anonymous"></script>

    <script>
        // Sales Chart (shorter version)
        const salesCtx = document.getElementById('salesChart').getContext('2d');

        // Prepare data for chart (reverse to show oldest first)
        const monthlySales = <?php echo json_encode(array_reverse($monthly_sales)); ?>;

        const months = monthlySales.map(item => item.month);
        const sales = monthlySales.map(item => parseFloat(item.monthly_sales));

        new Chart(salesCtx, {
            type: 'bar',
            data: {
                labels: months,
                datasets: [{
                    label: 'Sales (₱)',
                    data: sales,
                    backgroundColor: '#198754',
                    borderColor: '#198754',
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: function(value) {
                                return value.toLocaleString('en-PH', {
                                    minimumFractionDigits: 0,
                                    maximumFractionDigits: 0
                                });
                            },
                            maxTicksLimit: 5
                        }
                    },
                    x: {
                        ticks: {
                            maxRotation: 0,
                            minRotation: 0
                        }
                    }
                },
                indexAxis: 'y' // Horizontal bars for better space usage
            }
        });

        // Mobile sidebar toggle
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar-menu');
            const toggleBtn = document.querySelector('.d-lg-none .btn');

            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(e) {
                if (window.innerWidth < 992 && sidebar.classList.contains('show')) {
                    if (!sidebar.contains(e.target) && !toggleBtn.contains(e.target)) {
                        sidebar.classList.remove('show');
                    }
                }
            });
        });
    </script>

    <?php include '../includes/admin-footer.php'; ?>
</body>

</html>