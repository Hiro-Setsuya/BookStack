<?php

header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");

require_once "../config/db.php";
require_once "response.php";

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// POST - Login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate input
    if (!isset($data['email']) || !isset($data['password'])) {
        jsonResponse([
            "success" => false,
            "message" => "Email and password are required"
        ], 400);
    }

    $email = trim($data['email']);
    $password = $data['password'];

    // Validate email format
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse([
            "success" => false,
            "message" => "Invalid email format"
        ], 400);
    }

    // Check user credentials
    $stmt = $conn->prepare("SELECT user_id, user_name, email, password_hash, role FROM users WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Invalid credentials"
        ], 401);
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        jsonResponse([
            "success" => false,
            "message" => "Invalid credentials"
        ], 401);
    }

    // Generate simple token (in production, use JWT)
    $token = bin2hex(random_bytes(32));

    // Return success with user data
    jsonResponse([
        "user_id" => $user['user_id'],
        "username" => $user['user_name'],
        "email" => $user['email'],
        "role" => $user['role'],
        "token" => $token,
        "message" => "Login successful"
    ], 200);
}

// Method not allowed
jsonResponse([
    "success" => false,
    "message" => "Method not allowed. Use POST for login."
], 405);
