<?php
/**
 * Process PDF file upload and unlock
 */

// Start session
session_start();

// Include helper functions
require_once 'includes/functions.php';

// Set content type to JSON
header('Content-Type: application/json');

// Get user ID
$userId = getUserId();

// Create user directories
if (!createUserDirectories($userId)) {
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create user directories'
    ]);
    exit;
}

// Generate file ID
$fileId = generateFileId();

// Process request
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    // Process uploaded file
    $uploadedFile = $_FILES['pdf_file'];
    $originalName = $uploadedFile['name'];
    $tempPath = $uploadedFile['tmp_name'];
    
    // Validate file
    if (!isValidPdf($tempPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid PDF file'
        ]);
        exit;
    }
    
    // Move uploaded file to user's upload directory
    $uploadPath = UPLOAD_DIR . $userId . '/' . $fileId . '.pdf';
    if (!move_uploaded_file($tempPath, $uploadPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to move uploaded file'
        ]);
        exit;
    }
    
    // Process the PDF
    $outputPath = PROCESSED_DIR . $userId . '/' . $fileId . '.pdf';
    if (!unlockPdf($uploadPath, $outputPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unlock PDF'
        ]);
        exit;
    }
    
    // Save metadata
    $metadata = [
        'original_name' => $originalName,
        'upload_time' => time(),
        'source' => 'upload'
    ];
    
    saveFileMetadata($fileId, $userId, $metadata);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'PDF unlocked successfully',
        'file' => [
            'id' => $fileId,
            'name' => $originalName,
            'download_url' => 'download.php?id=' . $fileId,
            'expiry' => time() + FILE_EXPIRY_TIME
        ]
    ]);
    exit;
} elseif (isset($_POST['pdf_url']) && !empty($_POST['pdf_url'])) {
    // Process URL
    $url = $_POST['pdf_url'];
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode([
            'success' => false,
            'message' => 'Invalid URL'
        ]);
        exit;
    }
    
    // Extract filename from URL
    $urlParts = parse_url($url);
    $pathParts = pathinfo($urlParts['path']);
    $originalName = $pathParts['basename'];
    
    if (empty($originalName) || getFileExtension($originalName) !== 'pdf') {
        $originalName = 'document.pdf';
    }
    
    // Download file
    $downloadPath = UPLOAD_DIR . $userId . '/' . $fileId . '.pdf';
    if (!downloadFile($url, $downloadPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to download file from URL'
        ]);
        exit;
    }
    
    // Validate downloaded file
    if (!isValidPdf($downloadPath)) {
        @unlink($downloadPath);
        echo json_encode([
            'success' => false,
            'message' => 'Downloaded file is not a valid PDF'
        ]);
        exit;
    }
    
    // Process the PDF
    $outputPath = PROCESSED_DIR . $userId . '/' . $fileId . '.pdf';
    if (!unlockPdf($downloadPath, $outputPath)) {
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unlock PDF'
        ]);
        exit;
    }
    
    // Save metadata
    $metadata = [
        'original_name' => $originalName,
        'upload_time' => time(),
        'source' => 'url',
        'url' => $url
    ];
    
    saveFileMetadata($fileId, $userId, $metadata);
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'PDF unlocked successfully',
        'file' => [
            'id' => $fileId,
            'name' => $originalName,
            'download_url' => 'download.php?id=' . $fileId,
            'expiry' => time() + FILE_EXPIRY_TIME
        ]
    ]);
    exit;
} else {
    // No file or URL provided
    echo json_encode([
        'success' => false,
        'message' => 'No PDF file or URL provided'
    ]);
    exit;
} 