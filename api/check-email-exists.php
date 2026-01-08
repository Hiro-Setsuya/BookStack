<?php
header('Content-Type: application/json');
require_once '../config/db.php';

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

    $email_escaped = mysqli_real_escape_string($conn, $email);
    $query = "SELECT user_id FROM users WHERE email = '$email_escaped' LIMIT 1";
    $result = executeQuery($query);

    if ($result && mysqli_num_rows($result) > 0) {
        echo json_encode(['exists' => true, 'valid' => false]);
    } else {
        echo json_encode(['exists' => false, 'valid' => true]);
    }
} else {
    echo json_encode(['error' => 'Invalid request method']);
}
