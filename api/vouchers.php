<?php

/**
 * Vouchers API Endpoint
 * Handles voucher/discount code management
 * 
 * Endpoints:
 * GET    /api/vouchers.php - Get all vouchers
 * GET    /api/vouchers.php?code={code} - Get voucher by code
 * POST   /api/vouchers.php - Create new voucher
 * POST   /api/vouchers.php/validate - Validate voucher code
 * PUT    /api/vouchers.php?code={code} - Update voucher
 * DELETE /api/vouchers.php?code={code} - Delete voucher
 */

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

// Create vouchers table if not exists
$create_table = "CREATE TABLE IF NOT EXISTS vouchers (
    voucher_id INT AUTO_INCREMENT PRIMARY KEY,
    voucher_code VARCHAR(50) NOT NULL UNIQUE,
    discount_type ENUM('percentage', 'fixed') NOT NULL,
    discount_value DECIMAL(10,2) NOT NULL,
    min_purchase DECIMAL(10,2) DEFAULT 0,
    max_uses INT DEFAULT NULL,
    used_count INT DEFAULT 0,
    expiry_date DATETIME DEFAULT NULL,
    is_active BOOLEAN DEFAULT TRUE,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)";
$conn->query($create_table);

// GET - Retrieve vouchers
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get single voucher by code
    if (isset($_GET['code'])) {
        $code = strtoupper(trim($_GET['code']));

        $stmt = $conn->prepare("SELECT * FROM vouchers WHERE voucher_code = ?");
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
        $stmt->close();

        // Calculate remaining uses
        if ($voucher['max_uses'] !== null) {
            $voucher['remaining_uses'] = max(0, $voucher['max_uses'] - $voucher['used_count']);
        } else {
            $voucher['remaining_uses'] = "unlimited";
        }

        jsonResponse($voucher, 200);
    }

    // Get all vouchers
    $query = "SELECT *, 
              CASE 
                WHEN max_uses IS NULL THEN 'unlimited'
                ELSE GREATEST(0, max_uses - used_count)
              END as remaining_uses
              FROM vouchers 
              ORDER BY created_at DESC";

    $result = $conn->query($query);

    $vouchers = [];
    while ($row = $result->fetch_assoc()) {
        $vouchers[] = $row;
    }

    jsonResponse($vouchers, 200);
}

// POST - Create voucher or validate
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate voucher code endpoint
    if (isset($_GET['action']) && $_GET['action'] === 'validate') {
        if (!isset($data['voucher_code'])) {
            jsonResponse([
                "success" => false,
                "message" => "Voucher code is required"
            ], 400);
        }

        $code = strtoupper(trim($data['voucher_code']));
        $order_amount = isset($data['order_amount']) ? floatval($data['order_amount']) : 0;

        // Get voucher details
        $stmt = $conn->prepare(
            "SELECT * FROM vouchers 
             WHERE voucher_code = ? AND is_active = TRUE"
        );
        $stmt->bind_param("s", $code);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "Invalid or inactive voucher code"
            ], 404);
        }

        $voucher = $result->fetch_assoc();
        $stmt->close();

        // Check expiry date
        if ($voucher['expiry_date'] && strtotime($voucher['expiry_date']) < time()) {
            jsonResponse([
                "success" => false,
                "message" => "Voucher has expired"
            ], 400);
        }

        // Check usage limit
        if ($voucher['max_uses'] !== null && $voucher['used_count'] >= $voucher['max_uses']) {
            jsonResponse([
                "success" => false,
                "message" => "Voucher usage limit reached"
            ], 400);
        }

        // Check minimum purchase
        if ($order_amount < $voucher['min_purchase']) {
            jsonResponse([
                "success" => false,
                "message" => "Minimum purchase of $" . number_format($voucher['min_purchase'], 2) . " required"
            ], 400);
        }

        // Calculate discount
        if ($voucher['discount_type'] === 'percentage') {
            $discount = ($order_amount * $voucher['discount_value']) / 100;
        } else {
            $discount = $voucher['discount_value'];
        }

        $final_amount = max(0, $order_amount - $discount);

        jsonResponse([
            "voucher_code" => $voucher['voucher_code'],
            "discount_type" => $voucher['discount_type'],
            "discount_value" => $voucher['discount_value'],
            "original_amount" => $order_amount,
            "discount_amount" => round($discount, 2),
            "final_amount" => round($final_amount, 2),
            "savings" => round($discount, 2),
            "message" => "Voucher is valid"
        ], 200);
    }

    // Create new voucher
    if (!isset($data['voucher_code']) || !isset($data['discount_type']) || !isset($data['discount_value'])) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher code, discount type, and discount value are required"
        ], 400);
    }

    $code = strtoupper(trim($data['voucher_code']));
    $discount_type = $data['discount_type'];
    $discount_value = floatval($data['discount_value']);
    $min_purchase = isset($data['min_purchase']) ? floatval($data['min_purchase']) : 0;
    $max_uses = isset($data['max_uses']) ? intval($data['max_uses']) : null;
    $expiry_date = isset($data['expiry_date']) ? $data['expiry_date'] : null;
    $is_active = isset($data['is_active']) ? boolval($data['is_active']) : true;

    // Validate discount type
    if (!in_array($discount_type, ['percentage', 'fixed'])) {
        jsonResponse([
            "success" => false,
            "message" => "Discount type must be 'percentage' or 'fixed'"
        ], 400);
    }

    // Validate discount value
    if ($discount_type === 'percentage' && ($discount_value < 0 || $discount_value > 100)) {
        jsonResponse([
            "success" => false,
            "message" => "Percentage discount must be between 0 and 100"
        ], 400);
    }

    if ($discount_value < 0) {
        jsonResponse([
            "success" => false,
            "message" => "Discount value must be positive"
        ], 400);
    }

    // Check if voucher code already exists
    $check = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_code = ?");
    $check->bind_param("s", $code);
    $check->execute();
    if ($check->get_result()->num_rows > 0) {
        $check->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher code already exists"
        ], 409);
    }
    $check->close();

    // Insert voucher
    $stmt = $conn->prepare(
        "INSERT INTO vouchers (voucher_code, discount_type, discount_value, min_purchase, max_uses, expiry_date, is_active) 
         VALUES (?, ?, ?, ?, ?, ?, ?)"
    );
    $stmt->bind_param("ssddisd", $code, $discount_type, $discount_value, $min_purchase, $max_uses, $expiry_date, $is_active);

    if ($stmt->execute()) {
        $voucher_id = $stmt->insert_id;
        $stmt->close();

        jsonResponse([
            "voucher_id" => $voucher_id,
            "voucher_code" => $code,
            "discount_type" => $discount_type,
            "discount_value" => $discount_value,
            "message" => "Voucher created successfully"
        ], 201);
    } else {
        $error = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to create voucher: " . $error
        ], 500);
    }
}

// PUT - Update voucher
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    if (!isset($_GET['code'])) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher code is required"
        ], 400);
    }

    $code = strtoupper(trim($_GET['code']));
    $data = json_decode(file_get_contents("php://input"), true);

    // Check if voucher exists
    $check = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_code = ?");
    $check->bind_param("s", $code);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher not found"
        ], 404);
    }
    $check->close();

    // Build update query
    $updates = [];
    $params = [];
    $types = "";

    if (isset($data['discount_type'])) {
        $updates[] = "discount_type = ?";
        $params[] = $data['discount_type'];
        $types .= "s";
    }
    if (isset($data['discount_value'])) {
        $updates[] = "discount_value = ?";
        $params[] = floatval($data['discount_value']);
        $types .= "d";
    }
    if (isset($data['min_purchase'])) {
        $updates[] = "min_purchase = ?";
        $params[] = floatval($data['min_purchase']);
        $types .= "d";
    }
    if (isset($data['max_uses'])) {
        $updates[] = "max_uses = ?";
        $params[] = intval($data['max_uses']);
        $types .= "i";
    }
    if (isset($data['expiry_date'])) {
        $updates[] = "expiry_date = ?";
        $params[] = $data['expiry_date'];
        $types .= "s";
    }
    if (isset($data['is_active'])) {
        $updates[] = "is_active = ?";
        $params[] = boolval($data['is_active']);
        $types .= "i";
    }
    if (isset($data['used_count'])) {
        $updates[] = "used_count = ?";
        $params[] = intval($data['used_count']);
        $types .= "i";
    }

    if (count($updates) === 0) {
        jsonResponse([
            "success" => false,
            "message" => "No fields to update"
        ], 400);
    }

    $params[] = $code;
    $types .= "s";

    $query = "UPDATE vouchers SET " . implode(", ", $updates) . " WHERE voucher_code = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(["message" => "Voucher updated successfully"], 200);
    } else {
        $error = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to update voucher: " . $error
        ], 500);
    }
}

// DELETE - Delete voucher
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    if (!isset($_GET['code'])) {
        jsonResponse([
            "success" => false,
            "message" => "Voucher code is required"
        ], 400);
    }

    $code = strtoupper(trim($_GET['code']));

    // Check if voucher exists
    $check = $conn->prepare("SELECT voucher_id FROM vouchers WHERE voucher_code = ?");
    $check->bind_param("s", $code);
    $check->execute();
    if ($check->get_result()->num_rows === 0) {
        $check->close();
        jsonResponse([
            "success" => false,
            "message" => "Voucher not found"
        ], 404);
    }
    $check->close();

    // Delete voucher
    $stmt = $conn->prepare("DELETE FROM vouchers WHERE voucher_code = ?");
    $stmt->bind_param("s", $code);

    if ($stmt->execute()) {
        $stmt->close();
        jsonResponse(["message" => "Voucher deleted successfully"], 200);
    } else {
        $error = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Failed to delete voucher: " . $error
        ], 500);
    }
}

// Method not allowed
jsonResponse([
    "success" => false,
    "message" => "Method not allowed"
], 405);
