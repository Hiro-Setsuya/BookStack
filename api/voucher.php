<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "../config/db.php";
require_once "response.php";
require_once "auth-middleware.php"; // Admin authentication required

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle GET requests (retrieve vouchers)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get single voucher by ID
    if (isset($_GET['id'])) {
        $voucher_id = intval($_GET['id']);

        $stmt = $conn->prepare(
            "SELECT v.*, u.user_name, u.email 
             FROM vouchers v
             LEFT JOIN users u ON v.user_id = u.user_id
             WHERE v.voucher_id = ?"
        );
        $stmt->bind_param("i", $voucher_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "Voucher not found"
            ], 404);
        }

        $voucher = $result->fetch_assoc();

        // Calculate remaining uses
        if ($voucher['max_uses'] !== null) {
            $voucher['remaining_uses'] = max(0, $voucher['max_uses'] - $voucher['times_used']);
        } else {
            $voucher['remaining_uses'] = "unlimited";
        }

        // Check if expired
        $voucher['is_expired'] = ($voucher['expires_at'] && strtotime($voucher['expires_at']) < time());
        $voucher['is_available'] = !$voucher['is_redeemed'] && !$voucher['is_expired'];

        $stmt->close();
        jsonResponse($voucher, 200);
    }

    // Get voucher by code
    if (isset($_GET['code'])) {
        $code = strtoupper(trim($_GET['code']));

        $stmt = $conn->prepare(
            "SELECT v.*, u.user_name, u.email 
             FROM vouchers v
             LEFT JOIN users u ON v.user_id = u.user_id
             WHERE v.code = ?"
        );
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "Voucher not found"
            ], 404);
        }

        $voucher = $result->fetch_assoc();

        // Calculate remaining uses
        if ($voucher['max_uses'] !== null) {
            $voucher['remaining_uses'] = max(0, $voucher['max_uses'] - $voucher['times_used']);
        } else {
            $voucher['remaining_uses'] = "unlimited";
        }

        // Check if expired
        $voucher['is_expired'] = ($voucher['expires_at'] && strtotime($voucher['expires_at']) < time());
        $voucher['is_available'] = !$voucher['is_redeemed'] && !$voucher['is_expired'];

        $stmt->close();
        jsonResponse($voucher, 200);
    }

    // Get all vouchers with filtering
    $query = "SELECT v.*, u.user_name, u.email 
              FROM vouchers v
              LEFT JOIN users u ON v.user_id = u.user_id";

    $conditions = [];
    $params = [];
    $types = "";

    // Filter by user_id
    if (isset($_GET['user_id'])) {
        $conditions[] = "v.user_id = ?";
        $params[] = intval($_GET['user_id']);
        $types .= "i";
    }

    // Filter by external_system
    if (isset($_GET['external_system'])) {
        $conditions[] = "v.external_system = ?";
        $params[] = $_GET['external_system'];
        $types .= "s";
    }

    // Filter by redeemed status
    if (isset($_GET['is_redeemed'])) {
        $conditions[] = "v.is_redeemed = ?";
        $params[] = intval($_GET['is_redeemed']);
        $types .= "i";
    }

    // Filter by active/available vouchers
    if (isset($_GET['available']) && $_GET['available'] == 1) {
        $conditions[] = "v.is_redeemed = 0";
        $conditions[] = "(v.expires_at IS NULL OR v.expires_at > NOW())";
    }

    // Search by code
    if (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $conditions[] = "v.code LIKE ?";
        $params[] = $search;
        $types .= "s";
    }

    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY v.issued_at DESC";

    if (count($params) > 0) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }

    $vouchers = [];
    while ($row = $result->fetch_assoc()) {
        // Calculate remaining uses
        if ($row['max_uses'] !== null) {
            $row['remaining_uses'] = max(0, $row['max_uses'] - $row['times_used']);
        } else {
            $row['remaining_uses'] = "unlimited";
        }

        // Check if expired
        $row['is_expired'] = ($row['expires_at'] && strtotime($row['expires_at']) < time());
        $row['is_available'] = !$row['is_redeemed'] && !$row['is_expired'];

        $vouchers[] = $row;
    }

    jsonResponse($vouchers, 200);
}

// Handle POST requests (create voucher)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!isset($data["user_id"]) || !isset($data["code"]) || !isset($data["discount_amount"])) {
        jsonResponse([
            "success" => false,
            "message" => "user_id, code, and discount_amount are required"
        ], 400);
    }

    $user_id = intval($data["user_id"]);
    $external_system = isset($data["external_system"]) ? $data["external_system"] : 'ebook_store';
    $code = strtoupper(trim($data["code"]));
    $discount_type = isset($data["discount_type"]) ? $data["discount_type"] : 'fixed';
    $discount_amount = floatval($data["discount_amount"]);
    $min_order_amount = isset($data["min_order_amount"]) ? floatval($data["min_order_amount"]) : 0.00;
    $max_uses = isset($data["max_uses"]) ? intval($data["max_uses"]) : 1;
    $expires_at = isset($data["expires_at"]) ? $data["expires_at"] : null;

    // Validation
    if (empty($code)) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher code cannot be empty"
        ], 400);
    }

    if ($discount_amount <= 0) {
        jsonResponse([
            "success" => false,
            "message" => "Discount amount must be greater than 0"
        ], 400);
    }

    // Check if voucher code already exists
    $check_stmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ?");
    $check_stmt->bind_param("s", $code);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows > 0) {
        $check_stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher code already exists"
        ], 400);
    }
    $check_stmt->close();

    // Verify user exists
    $user_stmt = $conn->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $user_stmt->bind_param("i", $user_id);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows === 0) {
        $user_stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "User not found"
        ], 404);
    }
    $user_stmt->close();

    // Insert voucher
    $stmt = $conn->prepare(
        "INSERT INTO vouchers (user_id, external_system, code, discount_type, discount_amount, min_order_amount, max_uses, expires_at) 
         VALUES (?, ?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("isssddis", $user_id, $external_system, $code, $discount_type, $discount_amount, $min_order_amount, $max_uses, $expires_at);

    if ($stmt->execute()) {
        $voucher_id = $conn->insert_id;
        $stmt->close();

        // Get the created voucher
        $get_stmt = $conn->prepare(
            "SELECT v.*, u.user_name, u.email 
             FROM vouchers v
             LEFT JOIN users u ON v.user_id = u.user_id
             WHERE v.voucher_id = ?"
        );
        $get_stmt->bind_param("i", $voucher_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $voucher = $result->fetch_assoc();
        $get_stmt->close();

        jsonResponse([
            "success" => true,
            "message" => "Voucher created successfully",
            "voucher" => $voucher
        ], 201);
    } else {
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to create voucher: " . $conn->error
        ], 500);
    }
}

// Handle PUT requests (update voucher)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($_GET['id'])) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher ID is required"
        ], 400);
    }

    $voucher_id = intval($_GET['id']);

    // Check if voucher exists
    $check_stmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_id = ?");
    $check_stmt->bind_param("i", $voucher_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher not found"
        ], 404);
    }
    $check_stmt->close();

    // Build update query dynamically
    $updates = [];
    $params = [];
    $types = "";

    if (isset($data['discount_amount'])) {
        $updates[] = "discount_amount = ?";
        $params[] = floatval($data['discount_amount']);
        $types .= "d";
    }

    if (isset($data['min_order_amount'])) {
        $updates[] = "min_order_amount = ?";
        $params[] = floatval($data['min_order_amount']);
        $types .= "d";
    }

    if (isset($data['max_uses'])) {
        $updates[] = "max_uses = ?";
        $params[] = intval($data['max_uses']);
        $types .= "i";
    }

    if (isset($data['expires_at'])) {
        $updates[] = "expires_at = ?";
        $params[] = $data['expires_at'];
        $types .= "s";
    }

    if (isset($data['is_redeemed'])) {
        $updates[] = "is_redeemed = ?";
        $params[] = intval($data['is_redeemed']);
        $types .= "i";
    }

    if (count($updates) === 0) {
        jsonResponse([
            "success" => false,
            "message" => "No fields to update"
        ], 400);
    }

    $params[] = $voucher_id;
    $types .= "i";

    $query = "UPDATE vouchers SET " . implode(", ", $updates) . " WHERE voucher_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();

        // Get updated voucher
        $get_stmt = $conn->prepare(
            "SELECT v.*, u.user_name, u.email 
             FROM vouchers v
             LEFT JOIN users u ON v.user_id = u.user_id
             WHERE v.voucher_id = ?"
        );
        $get_stmt->bind_param("i", $voucher_id);
        $get_stmt->execute();
        $result = $get_stmt->get_result();
        $voucher = $result->fetch_assoc();
        $get_stmt->close();

        jsonResponse([
            "success" => true,
            "message" => "Voucher updated successfully",
            "voucher" => $voucher
        ], 200);
    } else {
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to update voucher: " . $conn->error
        ], 500);
    }
}

// Handle DELETE requests
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['id'])) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher ID is required"
        ], 400);
    }

    $voucher_id = intval($_GET['id']);

    // Check if voucher exists
    $check_stmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_id = ?");
    $check_stmt->bind_param("i", $voucher_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();

    if ($check_result->num_rows === 0) {
        $check_stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher not found"
        ], 404);
    }
    $check_stmt->close();

    // Delete voucher
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE voucher_id = ?");
    $stmt->bind_param("i", $voucher_id);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse([
            "success" => true,
            "message" => "Voucher deleted successfully"
        ], 200);
    } else {
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to delete voucher: " . $conn->error
        ], 500);
    }
}
