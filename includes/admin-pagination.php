<?php

/**
 * Reusable Admin Pagination Component
 * Displays pagination navigation with page numbers and navigation arrows
 * 
 * @param int $current_page - Current page number
 * @param int $total_pages - Total number of pages
 * @param int $total_items - Total number of items
 * @param array $query_params - Additional query parameters to preserve (e.g., ['search' => 'query', 'status' => 'active'])
 */
function renderAdminPagination($current_page, $total_pages, $total_items, $query_params = [])
{
    if ($total_pages <= 1) {
        // Show item count without pagination
        echo '<div class="mt-4">';
        echo '<span class="text-muted small">Total: ' . htmlspecialchars($total_items) . ' items</span>';
        echo '</div>';
        return;
    }

    // Build query string from additional parameters
    $query_string = '';
    foreach ($query_params as $key => $value) {
        if (!empty($value)) {
            $query_string .= '&' . urlencode($key) . '=' . urlencode($value);
        }
    }

    // Calculate page range to display (show 5 pages: current ± 2)
    $start_page = max(1, $current_page - 2);
    $end_page = min($total_pages, $current_page + 2);
?>
    <div class="mt-4 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <span class="text-muted small">
            Showing page <?php echo htmlspecialchars($current_page); ?> of <?php echo htmlspecialchars($total_pages); ?>
            (Total: <?php echo htmlspecialchars($total_items); ?> items)
        </span>

        <nav aria-label="Page navigation">
            <ul class="pagination pagination-sm mb-0">
                <!-- Previous Page -->
                <li class="page-item <?php echo ($current_page <= 1) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page - 1; ?><?php echo $query_string; ?>" aria-label="Previous">
                        &laquo;
                    </a>
                </li>

                <!-- Page Numbers -->
                <?php for ($i = $start_page; $i <= $end_page; $i++): ?>
                    <li class="page-item <?php echo ($i == $current_page) ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo $query_string; ?>">
                            <?php echo $i; ?>
                        </a>
                    </li>
                <?php endfor; ?>

                <!-- Next Page -->
                <li class="page-item <?php echo ($current_page >= $total_pages) ? 'disabled' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $current_page + 1; ?><?php echo $query_string; ?>" aria-label="Next">
                        &raquo;
                    </a>
                </li>
            </ul>
        </nav>
    </div>
<?php
}

/**
 * Calculate pagination parameters
 * 
 * @param int $page - Current page from query string
 * @param int $items_per_page - Number of items per page (default: 10)
 * @return array - ['page' => current_page, 'offset' => sql_offset, 'items_per_page' => items_per_page]
 */
function getPaginationParams($page = 1, $items_per_page = 10)
{
    $page = max(1, intval($page));
    $offset = ($page - 1) * $items_per_page;

    return [
        'page' => $page,
        'offset' => $offset,
        'items_per_page' => $items_per_page
    ];
}

/**
 * Calculate total pages from total items
 * 
 * @param int $total_items - Total number of items
 * @param int $items_per_page - Number of items per page
 * @return int - Total number of pages
 */
function calculateTotalPages($total_items, $items_per_page = 10)
{
    return ceil($total_items / $items_per_page);
}
