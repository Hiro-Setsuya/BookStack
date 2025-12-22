<?php
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST");
header("Access-Control-Allow-Headers: Content-Type");

require_once "../config/db.php";
require_once "response.php";

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// Handle GET requests (retrieve users)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Return all users
    $query = "SELECT user_id, user_name, email, phone_number, role, is_phone_verified, created_at, updated_at FROM users ORDER BY created_at DESC";
    $result = $conn->query($query);

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    jsonResponse($users, 200);
}

// Handle POST requests (create user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!isset($data["username"]) || !isset($data["email"]) || !isset($data["password"])) {
        jsonResponse(["error" => "Username, email, and password are required"], 400);
    }

    $username = trim($data["username"]);
    $email = trim($data["email"]);
    $password = $data["password"];

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse(["error" => "All fields are required"], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse(["error" => "Invalid email format"], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse(["error" => "Password must be at least 6 characters long"], 400);
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT user_id FROM users WHERE user_name = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result_username = $check_username->get_result();

    if ($result_username->num_rows > 0) {
        $check_username->close();
        jsonResponse(["error" => "Username already taken"], 409);
    }
    $check_username->close();

    // Check if email already exists
    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result_email = $check_email->get_result();

    if ($result_email->num_rows > 0) {
        $check_email->close();
        jsonResponse(["error" => "Email already registered"], 409);
    }
    $check_email->close();

    // Hash password and insert user
    $password_hash = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $conn->prepare("INSERT INTO users (user_name, email, password_hash) VALUES (?, ?, ?)");
    $stmt->bind_param("sss", $username, $email, $password_hash);

    if ($stmt->execute()) {
        $user_id = $stmt->insert_id;
        $stmt->close();

        jsonResponse([
            "message" => "User registered successfully",
            "user_id" => $user_id,
            "username" => $username,
            "email" => $email
        ], 201);
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        jsonResponse(["error" => "Registration failed: " . $error_msg], 500);
    }
}

// Method not allowed
jsonResponse(["error" => "Method not allowed"], 405);
