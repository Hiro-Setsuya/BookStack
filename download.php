<?php
session_start();
require_once 'config/db.php';

// Check if ebook_id is provided
if (!isset($_GET['id'])) {
    die('Invalid request');
}

$ebook_id = (int)$_GET['id'];

// Fetch ebook details
$query = "SELECT title, file_path FROM ebooks WHERE ebook_id = $ebook_id";
$result = executeQuery($query);

if ($result && mysqli_num_rows($result) > 0) {
    $ebook = mysqli_fetch_assoc($result);
    $file_path = $ebook['file_path'];

    // Check if it's a Google Drive link or file ID
    if (strpos($file_path, 'drive.google.com') !== false) {
        // Extract file ID from Google Drive URL
        preg_match('/\/d\/([a-zA-Z0-9_-]+)/', $file_path, $matches);
        $file_id = $matches[1] ?? null;

        if ($file_id) {
            // Redirect to Google Drive download link
            $download_url = "https://drive.google.com/uc?export=download&id=" . $file_id;
            header("Location: " . $download_url);
            exit;
        }
    } elseif (preg_match('/^[a-zA-Z0-9_-]{25,}$/', $file_path)) {
        // If it's just a file ID (no URL)
        $download_url = "https://drive.google.com/uc?export=download&id=" . $file_path;
        header("Location: " . $download_url);
        exit;
    } else {
        // Try local file download
        if (file_exists($file_path)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/octet-stream');
            header('Content-Disposition: attachment; filename="' . basename($file_path) . '"');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            header('Content-Length: ' . filesize($file_path));

            ob_clean();
            flush();
            readfile($file_path);
            exit;
        } else {
            die('File not found. Please contact support.');
        }
    }
} else {
    die('E-book not found');
}
