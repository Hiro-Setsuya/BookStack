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

// Handle GET requests (retrieve users)
if ($_SERVER['REQUEST_METHOD'] === 'GET') {

    // Get single user by ID
    if (isset($_GET['id'])) {
        $user_id = intval($_GET['id']);

        $stmt = $conn->prepare(
            "SELECT user_id, user_name, email, phone_number, role, is_account_verified, password_hash, created_at, updated_at 
             FROM users WHERE user_id = ?"
        );
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 0) {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "User not found"
            ], 404);
        }

        $user = $result->fetch_assoc();
        $stmt->close();

        jsonResponse($user, 200);
    }

    // Get all users with filtering
    $query = "SELECT user_id, user_name, email, phone_number, role, is_account_verified, password_hash, created_at, updated_at FROM users";

    $conditions = [];
    $params = [];
    $types = "";

    // Filter by role
    if (isset($_GET['role'])) {
        $conditions[] = "role = ?";
        $params[] = $_GET['role'];
        $types .= "s";
    }

    // Search by username or email
    if (isset($_GET['search'])) {
        $search = "%" . $_GET['search'] . "%";
        $conditions[] = "(user_name LIKE ? OR email LIKE ?)";
        $params[] = $search;
        $params[] = $search;
        $types .= "ss";
    }

    if (count($conditions) > 0) {
        $query .= " WHERE " . implode(" AND ", $conditions);
    }

    $query .= " ORDER BY created_at DESC";

    if (count($params) > 0) {
        $stmt = $conn->prepare($query);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $result = $stmt->get_result();
    } else {
        $result = $conn->query($query);
    }

    $users = [];
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }

    jsonResponse($users, 200);
}

// Handle PUT requests (update user)
if ($_SERVER['REQUEST_METHOD'] === 'PUT') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'])) {
        jsonResponse([
            "success" => false,
            "message" => "User ID is required"
        ], 400);
    }

    $user_id = intval($data['user_id']);

    // Build update query dynamically
    $updates = [];
    $params = [];
    $types = "";

    if (isset($data['username'])) {
        $updates[] = "user_name = ?";
        $params[] = trim($data['username']);
        $types .= "s";
    }

    if (isset($data['email'])) {
        $email = trim($data['email']);
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            jsonResponse([
                "success" => false,
                "message" => "Invalid email format"
            ], 400);
        }
        $updates[] = "email = ?";
        $params[] = $email;
        $types .= "s";
    }

    if (isset($data['password'])) {
        $password = $data['password'];
        if (strlen($password) < 6) {
            jsonResponse([
                "success" => false,
                "message" => "Password must be at least 6 characters long"
            ], 400);
        }
        $password_hash = password_hash($password, PASSWORD_DEFAULT);
        $updates[] = "password_hash = ?";
        $params[] = $password_hash;
        $types .= "s";
    }

    if (isset($data['phone_number'])) {
        $updates[] = "phone_number = ?";
        $params[] = trim($data['phone_number']);
        $types .= "s";
    }

    if (isset($data['role'])) {
        $updates[] = "role = ?";
        $params[] = $data['role'];
        $types .= "s";
    }

    if (empty($updates)) {
        jsonResponse([
            "success" => false,
            "message" => "No fields to update"
        ], 400);
    }

    $params[] = $user_id;
    $types .= "i";

    $query = "UPDATE users SET " . implode(", ", $updates) . " WHERE user_id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param($types, ...$params);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            jsonResponse([
                "success" => true,
                "message" => "User updated successfully"
            ], 200);
        } else {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "No changes made or user not found"
            ], 404);
        }
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Update failed: " . $error_msg
        ], 500);
    }
}

// Handle POST requests (create user)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get JSON input
    $data = json_decode(file_get_contents("php://input"), true);

    // Validate required fields
    if (!isset($data["username"]) || !isset($data["email"]) || !isset($data["password"])) {
        jsonResponse([
            "success" => false,
            "message" => "Username, email, and password are required"
        ], 400);
    }

    $username = trim($data["username"]);
    $email = trim($data["email"]);
    $password = $data["password"];

    // Validation
    if (empty($username) || empty($email) || empty($password)) {
        jsonResponse([
            "success" => false,
            "message" => "All fields are required"
        ], 400);
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        jsonResponse([
            "success" => false,
            "message" => "Invalid email format"
        ], 400);
    }

    if (strlen($password) < 6) {
        jsonResponse([
            "success" => false,
            "message" => "Password must be at least 6 characters long"
        ], 400);
    }

    // Check if username already exists
    $check_username = $conn->prepare("SELECT user_id FROM users WHERE user_name = ?");
    $check_username->bind_param("s", $username);
    $check_username->execute();
    $result_username = $check_username->get_result();

    if ($result_username->num_rows > 0) {
        $check_username->close();
        jsonResponse([
            "success" => false,
            "message" => "Username already taken"
        ], 409);
    }
    $check_username->close();

    // Check if email already exists
    $check_email = $conn->prepare("SELECT user_id FROM users WHERE email = ?");
    $check_email->bind_param("s", $email);
    $check_email->execute();
    $result_email = $check_email->get_result();

    if ($result_email->num_rows > 0) {
        $check_email->close();
        jsonResponse([
            "success" => false,
            "message" => "Email already registered"
        ], 409);
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
            "user_id" => $user_id,
            "username" => $username,
            "email" => $email,
            "message" => "User registered successfully"
        ], 201);
    } else {
        $error_msg = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Registration failed: " . $error_msg
        ], 500);
    }
}


// Handle DELETE requests (Delete user)
if ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
    $data = json_decode(file_get_contents("php://input"), true);

    if (!isset($data['user_id'])) {
        jsonResponse([
            "success" => false,
            "message" => "User ID is required"
        ], 400);
    }

    $user_id = intval($data['user_id']);

    $stmt = $conn->prepare("DELETE FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);

    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $stmt->close();
            jsonResponse([
                "success" => true,
                "message" => "User deleted successfully"
            ], 200);
        } else {
            $stmt->close();
            jsonResponse([
                "success" => false,
                "message" => "User not found"
            ], 404);
        }
    } else {
        $error = $stmt->error;
        $stmt->close();
        jsonResponse([
            "success" => false,
            "message" => "Delete failed: " . $error
        ], 500);
    }
}

// Method not allowed
jsonResponse([
    "success" => false,
    "message" => "Method not allowed"
], 405);
