<?php
require_once "../config/db.php";
require_once "response.php";

$data = json_decode(file_get_contents("php://input"), true);

$code = strtoupper(trim($data['code']));
// Handle both 'System_type' (from API) and 'external_system'
$external_system = $data['System_type'] ?? $data['external_system'];
$discount_type = $data['discount_type'];
$discount_amount = $data['discount_amount'];
$min_order_amount = $data['min_order_amount'] ?? 0.00;
$expires_at = $data['expires_at'];

// Determine user_id based on email or user identifier from EscaPinas
$user_id = 1; // Default to system user

// If EscaPinas sends user email or identifier, match it to BookStack user
if (isset($data['user_email']) && !empty($data['user_email'])) {
    $user_email = trim($data['user_email']);
    $user_stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $user_stmt->bind_param("s", $user_email);
    $user_stmt->execute();
    $user_result = $user_stmt->get_result();

    if ($user_result->num_rows > 0) {
        $user_row = $user_result->fetch_assoc();
        $user_id = $user_row['user_id'];
    }
    $user_stmt->close();
} elseif (isset($data['user_id']) && !empty($data['user_id'])) {
    // If BookStack user_id is directly provided
    $user_id = intval($data['user_id']);
}

// Check if voucher already exists
$check_stmt = $conn->prepare("SELECT voucher_id FROM vouchers WHERE code = ?");
$check_stmt->bind_param("s", $code);
$check_stmt->execute();
$result = $check_stmt->get_result();

if ($result->num_rows > 0) {
    // Update existing voucher
    $stmt = $conn->prepare("
        UPDATE vouchers
        SET external_system = ?,
            discount_type = ?,
            discount_amount = ?,
            min_order_amount = ?,
            expires_at = ?
        WHERE code = ?
    ");

    $stmt->bind_param(
        "ssddss",
        $external_system,
        $discount_type,
        $discount_amount,
        $min_order_amount,
        $expires_at,
        $code
    );

    $stmt->execute();

    jsonResponse([
        "success" => true,
        "message" => "Voucher updated successfully",
        "voucher_code" => $code
    ], 200);
} else {
    // Insert new voucher
    $stmt = $conn->prepare("
        INSERT INTO vouchers
        (user_id, external_system, code, discount_type, discount_amount, min_order_amount, max_uses, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

    // Calculate max_uses based on usage_stats if available
    $max_uses = 1;
    if (isset($data['usage_stats']['total_claims'])) {
        $max_uses = max(1, (int)$data['usage_stats']['total_claims']);
    } elseif (isset($data['max_uses'])) {
        $max_uses = $data['max_uses'];
    }

    $stmt->bind_param(
        "isssddis",
        $user_id,
        $external_system,
        $code,
        $discount_type,
        $discount_amount,
        $min_order_amount,
        $max_uses,
        $expires_at
    );

    $stmt->execute();

    jsonResponse([
        "success" => true,
        "message" => "Voucher imported successfully",
        "voucher_code" => $code
    ], 201);
}
