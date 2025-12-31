<?php
// Secure: prevent direct access
if (basename($_SERVER['SCRIPT_NAME']) === basename(__FILE__)) {
    http_response_code(403);
    exit('403 Forbidden');
}

// Ensure session and DB are ready
if (!isset($_SESSION)) session_start();
require_once 'config/db.php';

function getCartCount($user_id = null)
{
    global $conn;
    if (!$user_id && isset($_SESSION['user_id'])) {
        $user_id = $_SESSION['user_id'];
    }
    if (!$user_id) return 0;

    $stmt = $conn->prepare("SELECT COUNT(*) as count FROM cart_items WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $count = (int) ($result->fetch_assoc()['count'] ?? 0);
    $stmt->close();
    return $count;
}

$cart_count = getCartCount();
