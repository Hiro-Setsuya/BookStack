<?php
// Voucher Utility Functions

/**
 * Generate a unique voucher code
 */
function generateVoucherCode($prefix = 'BS')
{
    return strtoupper($prefix . '-' . substr(md5(uniqid(rand(), true)), 0, 8));
}

/**
 * Create a voucher for a user
 */
function createVoucher($conn, $user_id, $external_system, $discount_type, $discount_amount, $expires_days = 30, $min_order_amount = 0, $max_uses = 1)
{
    $code = generateVoucherCode($external_system === 'travel_agency' ? 'TA' : 'BS');
    $expires_at = date('Y-m-d H:i:s', strtotime("+$expires_days days"));

    $user_id = (int)$user_id;
    $external_system = mysqli_real_escape_string($conn, $external_system);
    $code = mysqli_real_escape_string($conn, $code);
    $discount_type = mysqli_real_escape_string($conn, $discount_type);
    $discount_amount = (float)$discount_amount;
    $min_order_amount = (float)$min_order_amount;
    $max_uses = (int)$max_uses;
    $expires_at = mysqli_real_escape_string($conn, $expires_at);

    $query = "INSERT INTO vouchers (user_id, external_system, code, discount_type, discount_amount, min_order_amount, max_uses, expires_at, issued_at) 
              VALUES ($user_id, '$external_system', '$code', '$discount_type', $discount_amount, $min_order_amount, $max_uses, '$expires_at', NOW())";

    if (executeQuery($query)) {
        return [
            'success' => true,
            'code' => $code,
            'voucher_id' => mysqli_insert_id($conn)
        ];
    }

    return ['success' => false];
}

/**
 * Issue welcome voucher on first sign-in
 */
function issueWelcomeVoucher($conn, $user_id)
{
    // Check if user already has a welcome voucher
    $check = "SELECT voucher_id FROM vouchers WHERE user_id = $user_id AND external_system = 'ebook_store' LIMIT 1";
    $result = executeQuery($check);

    if (mysqli_num_rows($result) == 0) {
        // First voucher - give 10% welcome discount
        return createVoucher($conn, $user_id, 'ebook_store', 'percentage', 10, 5, 0, 1);
    }

    return ['success' => false, 'message' => 'Welcome voucher already issued'];
}

/**
 * Issue verification reward vouchers (both systems)
 */
function issueVerificationVouchers($conn, $user_id)
{
    $results = [];

    // BookStack verification voucher - 15% off
    $result1 = createVoucher($conn, $user_id, 'ebook_store', 'percentage', 15, 5, 0, 1);
    $results[] = $result1;

    // Travel Agency partnership voucher - ₱5 off
    $result2 = createVoucher($conn, $user_id, 'travel_agency', 'fixed', 5, 5, 20, 1);
    $results[] = $result2;

    return $results;
}

/**
 * Check and issue milestone vouchers based on purchase count
 */
function checkPurchaseMilestone($conn, $user_id)
{
    // Count completed orders
    $query = "SELECT COUNT(DISTINCT order_id) as order_count FROM orders WHERE user_id = $user_id AND status = 'completed'";
    $result = executeQuery($query);
    $row = mysqli_fetch_assoc($result);
    $order_count = $row['order_count'];

    // Check if user just hit the 3-purchase milestone
    if ($order_count == 3) {
        // Check if milestone voucher already issued
        $check = "SELECT voucher_id FROM vouchers 
                  WHERE user_id = $user_id 
                  AND external_system = 'ebook_store' 
                  AND discount_type = 'percentage' 
                  AND discount_amount = 20 
                  LIMIT 1";
        $checkResult = executeQuery($check);

        if (mysqli_num_rows($checkResult) == 0) {
            // Issue 20% off milestone reward
            return createVoucher($conn, $user_id, 'ebook_store', 'percentage', 20, 5, 0, 1);
        }
    }

    return ['success' => false, 'message' => 'Milestone not reached or already rewarded'];
}

/**
 * Validate voucher for use
 */
function validateVoucher($conn, $voucher_code, $user_id, $order_amount = 0)
{
    $voucher_code = mysqli_real_escape_string($conn, $voucher_code);
    $user_id = (int)$user_id;

    $query = "SELECT * FROM vouchers 
              WHERE code = '$voucher_code' 
              AND user_id = $user_id 
              AND expires_at > NOW() 
              AND times_used < max_uses";

    $result = executeQuery($query);

    if (mysqli_num_rows($result) > 0) {
        $voucher = mysqli_fetch_assoc($result);

        // Check minimum order amount
        if ($order_amount < $voucher['min_order_amount']) {
            return [
                'valid' => false,
                'message' => 'Order amount does not meet minimum requirement of $' . number_format($voucher['min_order_amount'], 2)
            ];
        }

        return [
            'valid' => true,
            'voucher' => $voucher
        ];
    }

    return [
        'valid' => false,
        'message' => 'Invalid, expired, or already used voucher code'
    ];
}

/**
 * Apply voucher to order (mark as used)
 */
function applyVoucher($conn, $voucher_id)
{
    $voucher_id = (int)$voucher_id;

    $query = "UPDATE vouchers 
              SET times_used = times_used + 1, 
                  redeemed_at = NOW() 
              WHERE voucher_id = $voucher_id";

    return executeQuery($query);
}

/**
 * Calculate discount amount
 */
function calculateDiscount($voucher, $order_amount)
{
    if ($voucher['discount_type'] === 'percentage') {
        return ($order_amount * $voucher['discount_amount']) / 100;
    } else {
        return min($voucher['discount_amount'], $order_amount);
    }
}
