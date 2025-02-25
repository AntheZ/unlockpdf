<?php
/**
 * Check for user's existing files
 */

// Start session
session_start();

// Include helper functions
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get user ID
$userId = getUserId();

// Get user's files
$files = getUserFiles($userId);

// Return response
echo json_encode([
    'success' => true,
    'files' => $files
]);
exit; 