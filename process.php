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
logMessage("Processing request for user: " . $userId);

// Create user directories
if (!createUserDirectories($userId)) {
    logMessage("Failed to create user directories for user: " . $userId);
    echo json_encode([
        'success' => false,
        'message' => 'Failed to create user directories. Please check server permissions.'
    ]);
    exit;
}

// Generate file ID
$fileId = generateFileId();
logMessage("Generated file ID: " . $fileId);

// Process request
if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
    // Process uploaded file
    $uploadedFile = $_FILES['pdf_file'];
    $originalName = $uploadedFile['name'];
    $tempPath = $uploadedFile['tmp_name'];
    
    logMessage("Processing uploaded file: " . $originalName . " (temp: " . $tempPath . ")");
    
    // Check upload errors
    if ($uploadedFile['error'] !== UPLOAD_ERR_OK) {
        $errorMessage = 'Upload error: ';
        switch ($uploadedFile['error']) {
            case UPLOAD_ERR_INI_SIZE:
                $errorMessage .= 'The uploaded file exceeds the upload_max_filesize directive in php.ini';
                break;
            case UPLOAD_ERR_FORM_SIZE:
                $errorMessage .= 'The uploaded file exceeds the MAX_FILE_SIZE directive in the HTML form';
                break;
            case UPLOAD_ERR_PARTIAL:
                $errorMessage .= 'The uploaded file was only partially uploaded';
                break;
            case UPLOAD_ERR_NO_FILE:
                $errorMessage .= 'No file was uploaded';
                break;
            case UPLOAD_ERR_NO_TMP_DIR:
                $errorMessage .= 'Missing a temporary folder';
                break;
            case UPLOAD_ERR_CANT_WRITE:
                $errorMessage .= 'Failed to write file to disk';
                break;
            case UPLOAD_ERR_EXTENSION:
                $errorMessage .= 'A PHP extension stopped the file upload';
                break;
            default:
                $errorMessage .= 'Unknown upload error';
        }
        logMessage($errorMessage);
        echo json_encode([
            'success' => false,
            'message' => $errorMessage
        ]);
        exit;
    }
    
    // Validate file
    if (!isValidPdf($tempPath)) {
        logMessage("Invalid PDF file: " . $originalName);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid PDF file. Please ensure you are uploading a valid PDF document.'
        ]);
        exit;
    }
    
    // Move uploaded file to user's upload directory
    $uploadPath = UPLOAD_DIR . $userId . '/' . $fileId . '.pdf';
    logMessage("Moving uploaded file to: " . $uploadPath);
    
    if (!move_uploaded_file($tempPath, $uploadPath)) {
        $moveError = error_get_last();
        logMessage("Failed to move uploaded file: " . ($moveError ? $moveError['message'] : 'Unknown error'));
        echo json_encode([
            'success' => false,
            'message' => 'Failed to move uploaded file. Please try again.'
        ]);
        exit;
    }
    
    // Process the PDF
    $outputPath = PROCESSED_DIR . $userId . '/' . $fileId . '.pdf';
    logMessage("Processing PDF: " . $uploadPath . " -> " . $outputPath);
    
    if (!unlockPdf($uploadPath, $outputPath)) {
        logMessage("Failed to unlock PDF: " . $uploadPath);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unlock PDF. The file may already be unlocked or uses an unsupported protection method.'
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
    logMessage("PDF processed successfully: " . $originalName);
    
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
    logMessage("Processing PDF from URL: " . $url);
    
    // Validate URL
    if (!filter_var($url, FILTER_VALIDATE_URL)) {
        logMessage("Invalid URL: " . $url);
        echo json_encode([
            'success' => false,
            'message' => 'Invalid URL. Please provide a valid URL to a PDF file.'
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
    
    logMessage("Extracted filename from URL: " . $originalName);
    
    // Download file
    $downloadPath = UPLOAD_DIR . $userId . '/' . $fileId . '.pdf';
    logMessage("Downloading file to: " . $downloadPath);
    
    if (!downloadFile($url, $downloadPath)) {
        logMessage("Failed to download file from URL: " . $url);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to download file from URL. Please check the URL and try again.'
        ]);
        exit;
    }
    
    // Validate downloaded file
    if (!isValidPdf($downloadPath)) {
        @unlink($downloadPath);
        logMessage("Downloaded file is not a valid PDF: " . $url);
        echo json_encode([
            'success' => false,
            'message' => 'Downloaded file is not a valid PDF. Please ensure the URL points to a PDF document.'
        ]);
        exit;
    }
    
    // Process the PDF
    $outputPath = PROCESSED_DIR . $userId . '/' . $fileId . '.pdf';
    logMessage("Processing PDF: " . $downloadPath . " -> " . $outputPath);
    
    if (!unlockPdf($downloadPath, $outputPath)) {
        logMessage("Failed to unlock PDF: " . $downloadPath);
        echo json_encode([
            'success' => false,
            'message' => 'Failed to unlock PDF. The file may already be unlocked or uses an unsupported protection method.'
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
    logMessage("PDF from URL processed successfully: " . $url);
    
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
    logMessage("No PDF file or URL provided");
    echo json_encode([
        'success' => false,
        'message' => 'No PDF file or URL provided. Please upload a file or provide a URL.'
    ]);
    exit;
} 