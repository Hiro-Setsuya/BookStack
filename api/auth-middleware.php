<?php

/**
 * Basic Authentication Middleware
 * Validates admin users from database before API access
 */

require_once __DIR__ . "/../config/db.php";

// Get authorization header
$headers = getallheaders();
$auth = $headers['Authorization'] ?? $headers['authorization'] ?? null;

if ($auth !== null && strpos($auth, 'Basic ') === 0) {
    // Decode Basic Auth credentials
    $encoded = substr($auth, 6);
    $decoded = base64_decode($encoded);
    list($username, $password) = explode(':', $decoded, 2);

    // Validate credentials against database
    $stmt = $conn->prepare("SELECT user_id, user_name, email, password_hash, role FROM users WHERE user_name = ? OR email = ?");
    $stmt->bind_param("ss", $username, $username);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 0) {
        $stmt->close();
        header('WWW-Authenticate: Basic realm="BookStack API"');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials. Please try again.']);
        exit;
    }

    $user = $result->fetch_assoc();
    $stmt->close();

    // Verify password
    if (!password_verify($password, $user['password_hash'])) {
        header('WWW-Authenticate: Basic realm="BookStack API"');
        http_response_code(401);
        echo json_encode(['error' => 'Invalid credentials. Please try again.']);
        exit;
    }

    // Check if user is admin
    if ($user['role'] !== 'admin') {
        header('WWW-Authenticate: Basic realm="BookStack API"');
        http_response_code(401);
        echo json_encode(['error' => 'Access denied. Admin privileges required.']);
        exit;
    }

    // Authentication successful - store user info for use in API
    $authenticated_user = $user;
} else {
    // No authorization header provided
    header('WWW-Authenticate: Basic realm="BookStack API"');
    http_response_code(401);
    echo json_encode(['error' => 'Authentication required']);
    exit;
}
