<?php
header('Content-Type: application/json');
require_once '../config/db.php';
require_once '../config/api-connection.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        echo json_encode(['exists' => false, 'valid' => false]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['exists' => false, 'valid' => false]);
        exit;
    }

    // Check if email exists in BookStack
    $email_escaped = mysqli_real_escape_string($conn, $email);
    $query = "SELECT user_id FROM users WHERE email = '$email_escaped' LIMIT 1";
    $result = executeQuery($query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo json_encode(['exists' => true, 'valid' => false, 'source' => 'BookStack']);
        exit;
    }

    // Check if email exists in EscaPinas system
    $escapinas_response = @file_get_contents(ESCAPINAS_API_USERS);
    if ($escapinas_response) {
        $escapinas_users = json_decode($escapinas_response, true);
        if ($escapinas_users && is_array($escapinas_users)) {
            foreach ($escapinas_users as $escapinas_user) {
                if (isset($escapinas_user['email']) && strtolower(trim($escapinas_user['email'])) === strtolower($email)) {
                    echo json_encode(['exists' => true, 'valid' => false, 'source' => 'EscaPinas']);
                    exit;
                }
            }
        }
    }

    // Email doesn't exist in either system
    echo json_encode(['exists' => false, 'valid' => true]);
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
