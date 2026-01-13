<?php

/**
 * Sync vouchers from EscaPinas API for a specific user
 * @param mysqli $conn Database connection
 * @param int $user_id BookStack user ID
 * @param string $user_email User's email address
 * @return array Result with synced count
 */
function syncAllEscaPinasVouchers($conn, $user_id, $user_email)
{
    $synced_count = 0;
    $errors = [];

    try {
        // Fetch vouchers from EscaPinas API
        $api_url = ESCAPINAS_API_VOUCHERS;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $api_url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false); // For local development

        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $errors[] = "CURL Error: " . curl_error($ch);
            curl_close($ch);
            return ['synced' => 0, 'errors' => $errors];
        }

        curl_close($ch);

        if ($http_code !== 200) {
            $errors[] = "API returned status code: " . $http_code;
            return ['synced' => 0, 'errors' => $errors];
        }

        $vouchers = json_decode($response, true);

        if (!is_array($vouchers)) {
            $errors[] = "Invalid API response format";
            return ['synced' => 0, 'errors' => $errors];
        }

        // Get user's EscaPinas vouchers that belong to them
        foreach ($vouchers as $voucher) {
            // Check if this voucher has usage_stats with claimed users
            // You would need to match user by email or some identifier
            // For now, we sync all travel_agency vouchers

            if (!isset($voucher['code']) || empty($voucher['code'])) {
                continue;
            }

            $code = strtoupper(trim($voucher['code']));
            $external_system = $voucher['System_type'] ?? 'travel_agency';
            $discount_type = $voucher['discount_type'] ?? 'fixed';
            $discount_amount = floatval($voucher['discount_amount'] ?? 0);
            $min_order_amount = floatval($voucher['min_order_amount'] ?? 0);
            $expires_at = $voucher['expires_at'] ?? null;

            // Calculate max_uses from usage_stats
            $max_uses = 1;
            if (isset($voucher['usage_stats']['total_claims'])) {
                $max_uses = max(1, intval($voucher['usage_stats']['total_claims']));
            }

            // Check if voucher already exists for this user
            $check_stmt = $conn->prepare("
                SELECT voucher_id FROM vouchers 
                WHERE code = ? AND user_id = ?
            ");
            $check_stmt->bind_param("si", $code, $user_id);
            $check_stmt->execute();
            $result = $check_stmt->get_result();

            if ($result->num_rows > 0) {
                // Update existing voucher
                $update_stmt = $conn->prepare("
                    UPDATE vouchers
                    SET external_system = ?,
                        discount_type = ?,
                        discount_amount = ?,
                        min_order_amount = ?,
                        max_uses = ?,
                        expires_at = ?
                    WHERE code = ? AND user_id = ?
                ");

                $update_stmt->bind_param(
                    "ssddissi",
                    $external_system,
                    $discount_type,
                    $discount_amount,
                    $min_order_amount,
                    $max_uses,
                    $expires_at,
                    $code,
                    $user_id
                );

                if ($update_stmt->execute()) {
                    $synced_count++;
                }
                $update_stmt->close();
            } else {
                // Insert new voucher
                $insert_stmt = $conn->prepare("
                    INSERT INTO vouchers
                    (user_id, external_system, code, discount_type, discount_amount, min_order_amount, max_uses, expires_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $insert_stmt->bind_param(
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

                if ($insert_stmt->execute()) {
                    $synced_count++;
                }
                $insert_stmt->close();
            }

            $check_stmt->close();
        }
    } catch (Exception $e) {
        $errors[] = "Exception: " . $e->getMessage();
    }

    return [
        'synced' => $synced_count,
        'errors' => $errors
    ];
}

/**
 * Create a voucher for a user
 * @param mysqli $conn Database connection
 * @param int $user_id User ID to assign the voucher to
 * @param string $external_system System type (ebook_store or travel_agency)
 * @param string $discount_type Discount type (percentage or fixed)
 * @param float $discount_amount Discount amount
 * @param int $expires_days Number of days until expiration
 * @param float $min_order_amount Minimum order amount required
 * @param int $max_uses Maximum number of uses
 * @return array Result with success status and voucher code
 */
function createVoucher($conn, $user_id, $external_system, $discount_type, $discount_amount, $expires_days, $min_order_amount = 0.00, $max_uses = 1)
{
    // Generate unique voucher code
    $code = generateVoucherCode();

    // Calculate expiration date
    $expires_at = date('Y-m-d H:i:s', strtotime("+{$expires_days} days"));

    // Insert voucher into database
    $stmt = $conn->prepare("
        INSERT INTO vouchers 
        (user_id, external_system, code, discount_type, discount_amount, min_order_amount, max_uses, expires_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)
    ");

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

    $success = $stmt->execute();
    $stmt->close();

    return [
        'success' => $success,
        'code' => $success ? $code : null
    ];
}

/**
 * Generate a unique voucher code
 * @return string Random uppercase alphanumeric code
 */
function generateVoucherCode()
{
    $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
    $code = '';

    for ($i = 0; $i < 8; $i++) {
        $code .= $characters[rand(0, strlen($characters) - 1)];
    }

    return $code;
}
