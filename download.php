<?php
session_start();
require_once 'config/db.php';

if (!isset($_GET['id'])) {
    die('Invalid request');
}

$ebook_id = (int)$_GET['id'];

// Fetch ebook
$stmt = $conn->prepare("SELECT title, file_path FROM ebooks WHERE ebook_id = ?");
$stmt->bind_param("i", $ebook_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows === 0) {
    die('E-book not found');
}

$ebook = $result->fetch_assoc();
$file_url = trim($ebook['file_path']);

// Optional: basic URL validation
if (!filter_var($file_url, FILTER_VALIDATE_URL)) {
    die('Invalid file URL.');
}

// Redirect to GitHub Releases download link
header("Location: $file_url");
exit;
