<?php
/**
 * Download unlocked PDF file
 */

// Start session
session_start();

// Include helper functions
require_once 'includes/functions.php';

// Get user ID
$userId = getUserId();

// Check if file ID is provided
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('HTTP/1.1 400 Bad Request');
    echo 'File ID is required';
    exit;
}

$fileId = $_GET['id'];

// Validate file ID (prevent directory traversal)
if (!preg_match('/^[a-f0-9]+$/', $fileId)) {
    header('HTTP/1.1 400 Bad Request');
    echo 'Invalid file ID';
    exit;
}

// Check if file exists
$filePath = PROCESSED_DIR . $userId . '/' . $fileId . '.pdf';
if (!file_exists($filePath)) {
    header('HTTP/1.1 404 Not Found');
    echo 'File not found';
    exit;
}

// Get original file name
$originalName = getOriginalFileName($fileId, $userId);

// Set headers for download
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $originalName . '"');
header('Content-Length: ' . filesize($filePath));
header('Cache-Control: no-cache, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

// Output file
readfile($filePath);
exit; 