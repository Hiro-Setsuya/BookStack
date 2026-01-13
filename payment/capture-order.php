<?php
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../config/paypal.php';
require_once __DIR__ . '/../notifications/send-email.php';

$orderId = $_GET['orderID'] ?? null;
if (!$orderId) {
    http_response_code(400);
    echo json_encode(["error" => "Missing orderID"]);
    exit;
}

$accessToken = generateAccessToken(PAYPAL_CLIENT_ID, PAYPAL_SECRET);
if (!$accessToken) {
    http_response_code(500);
    echo json_encode(["error" => "Failed to generate access token"]);
    exit;
}

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "https://api-m.sandbox.paypal.com/v2/checkout/orders/$orderId/capture");
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Authorization: Bearer $accessToken"
]);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$captureResponse = curl_exec($ch);
if (!$captureResponse) {
    http_response_code(500);
    echo json_encode(["error" => "CURL CAPTURE ERROR: " . curl_error($ch)]);
    curl_close($ch);
    exit;
}

curl_close($ch);
$responseData = json_decode($captureResponse, true);

if (isset($responseData['status']) && $responseData['status'] === 'COMPLETED') {
    // Save order to database
    $user_id = $_SESSION['user_id'] ?? 0;
    $total_amount = $_SESSION['checkout_total'] ?? 0;

    if ($user_id > 0 && $total_amount > 0) {
        // Insert into orders table
        $insert_order = "INSERT INTO orders (user_id, total_amount, status, payment_id, created_at) 
                         VALUES ($user_id, $total_amount, 'completed', '$orderId', NOW())";

        if (executeQuery($insert_order)) {
            $order_id = mysqli_insert_id($conn);

            // Get items from checkout session/URL
            $item_ids = [];
            if (isset($_SESSION['checkout_items'])) {
                $item_ids = $_SESSION['checkout_items'];
            }

            // Insert order items
            if (!empty($item_ids)) {
                $ids_list = implode(',', array_map('intval', $item_ids));
                $ebooks_query = "SELECT ebook_id, title, author, price FROM ebooks WHERE ebook_id IN ($ids_list)";
                $ebooks_result = executeQuery($ebooks_query);

                $purchased_items = [];
                while ($ebook = mysqli_fetch_assoc($ebooks_result)) {
                    $ebook_id = $ebook['ebook_id'];
                    $price = $ebook['price'];
                    $insert_item = "INSERT INTO order_items (order_id, ebook_id, price, quantity) 
                                   VALUES ($order_id, $ebook_id, $price, 1)";
                    executeQuery($insert_item);

                    // Store item for email notification
                    $purchased_items[] = [
                        'title' => $ebook['title'],
                        'author' => $ebook['author'],
                        'price' => $price
                    ];
                }

                // Clear cart items for this user
                $clear_cart = "DELETE FROM cart_items WHERE user_id = $user_id AND ebook_id IN ($ids_list)";
                executeQuery($clear_cart);

                // Send purchase confirmation email
                $user_query = "SELECT user_name, email FROM users WHERE user_id = $user_id";
                $user_result = executeQuery($user_query);
                if ($user_result && mysqli_num_rows($user_result) > 0) {
                    $user_data = mysqli_fetch_assoc($user_result);
                    sendPurchaseConfirmationEmail(
                        $user_data['email'],
                        $user_data['user_name'],
                        $orderId,
                        $purchased_items,
                        $total_amount
                    );
                }
            }

            // Update voucher usage if one was used
            if (isset($_SESSION['selected_voucher_id']) && !empty($_SESSION['selected_voucher_id'])) {
                $voucher_id = intval($_SESSION['selected_voucher_id']);
                $update_voucher = "UPDATE vouchers SET times_used = times_used + 1 WHERE voucher_id = $voucher_id";
                executeQuery($update_voucher);
            }

            // Clear checkout session
            unset($_SESSION['checkout_total']);
            unset($_SESSION['checkout_items']);
            unset($_SESSION['promo_code']);
            unset($_SESSION['selected_voucher_id']);

            // Check for purchase milestone (buy 3 books, get 20% off)
        }
    }

    echo json_encode([
        "status" => "success",
        "orderID" => $orderId,
        "payer" => $responseData['payer'] ?? []
    ]);
} else {
    http_response_code(500);
    echo json_encode([
        "status" => "failed",
        "details" => $responseData
    ]);
}

exit;
