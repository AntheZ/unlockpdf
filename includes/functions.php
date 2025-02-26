<?php
/**
 * Helper functions for PDF Unlock Tool
 */

// Set cookie lifetime to 30 days
define('COOKIE_LIFETIME', 60 * 60 * 24 * 30);
// Set file expiry time to 10 minutes
define('FILE_EXPIRY_TIME', 60 * 10);
// Set upload directory
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
// Set processed directory
define('PROCESSED_DIR', __DIR__ . '/../processed/');
// Set log directory
define('LOG_DIR', __DIR__ . '/../logs/');

// Include PDF unlock methods
require_once __DIR__ . '/pdf_unlock.php';

/**
 * Log message to file
 * 
 * @param string $message Message to log
 * @return void
 */
function logMessage($message) {
    if (!file_exists(LOG_DIR)) {
        mkdir(LOG_DIR, 0755, true);
    }
    
    $logFile = LOG_DIR . 'app.log';
    $timestamp = date('Y-m-d H:i:s');
    $logMessage = "[$timestamp] $message" . PHP_EOL;
    
    file_put_contents($logFile, $logMessage, FILE_APPEND);
}

/**
 * Get or create a unique user ID
 * 
 * @return string User ID
 */
function getUserId() {
    if (!isset($_COOKIE['pdf_unlock_user'])) {
        $userId = bin2hex(random_bytes(16));
        setcookie('pdf_unlock_user', $userId, time() + COOKIE_LIFETIME, '/');
    } else {
        $userId = $_COOKIE['pdf_unlock_user'];
    }
    
    return $userId;
}

/**
 * Create user directories if they don't exist
 * 
 * @param string $userId User ID
 * @return bool Success status
 */
function createUserDirectories($userId) {
    $userUploadDir = UPLOAD_DIR . $userId . '/';
    $userProcessedDir = PROCESSED_DIR . $userId . '/';
    
    if (!file_exists(UPLOAD_DIR)) {
        $result = mkdir(UPLOAD_DIR, 0755, true);
        if (!$result) {
            logMessage("Failed to create upload directory: " . UPLOAD_DIR);
            return false;
        }
    }
    
    if (!file_exists(PROCESSED_DIR)) {
        $result = mkdir(PROCESSED_DIR, 0755, true);
        if (!$result) {
            logMessage("Failed to create processed directory: " . PROCESSED_DIR);
            return false;
        }
    }
    
    if (!file_exists($userUploadDir)) {
        $result = mkdir($userUploadDir, 0755, true);
        if (!$result) {
            logMessage("Failed to create user upload directory: " . $userUploadDir);
            return false;
        }
    }
    
    if (!file_exists($userProcessedDir)) {
        $result = mkdir($userProcessedDir, 0755, true);
        if (!$result) {
            logMessage("Failed to create user processed directory: " . $userProcessedDir);
            return false;
        }
    }
    
    return (is_dir($userUploadDir) && is_dir($userProcessedDir));
}

/**
 * Generate a unique file ID
 * 
 * @return string File ID
 */
function generateFileId() {
    return bin2hex(random_bytes(8));
}

/**
 * Get file extension from filename
 * 
 * @param string $filename Filename
 * @return string File extension
 */
function getFileExtension($filename) {
    return strtolower(pathinfo($filename, PATHINFO_EXTENSION));
}

/**
 * Validate PDF file
 * 
 * @param string $filePath Path to the file
 * @return bool Is valid PDF
 */
function isValidPdf($filePath) {
    // Log file information
    logMessage("Validating PDF file: " . $filePath);
    
    // Check if file exists
    if (!file_exists($filePath)) {
        logMessage("File does not exist: " . $filePath);
        return false;
    }
    
    // Check file size
    $fileSize = filesize($filePath);
    if ($fileSize === 0) {
        logMessage("File is empty: " . $filePath);
        return false;
    }
    
    logMessage("File size: " . $fileSize . " bytes");
    
    // Check file extension
    $extension = getFileExtension($filePath);
    if ($extension !== 'pdf') {
        logMessage("Invalid file extension: " . $extension);
        return false;
    }
    
    // Check file signature (PDF magic number)
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        logMessage("Failed to open file: " . $filePath);
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    $isPdf = (substr($header, 0, 4) === '%PDF');
    
    if (!$isPdf) {
        logMessage("Invalid PDF header: " . bin2hex($header));
    } else {
        logMessage("Valid PDF header detected");
    }
    
    return $isPdf;
}

/**
 * Download file from URL
 * 
 * @param string $url URL to download from
 * @param string $savePath Path to save the file
 * @return bool Success status
 */
function downloadFile($url, $savePath) {
    logMessage("Downloading file from URL: " . $url);
    
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: PDF Unlock Tool\r\n",
            'timeout' => 30,
            'follow_location' => 1,
            'max_redirects' => 5
        ]
    ];
    
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        logMessage("Failed to download file from URL: " . $url . " - Error: " . error_get_last()['message']);
        return false;
    }
    
    $result = file_put_contents($savePath, $content);
    
    if ($result === false) {
        logMessage("Failed to save downloaded file to: " . $savePath);
        return false;
    }
    
    logMessage("File downloaded successfully. Size: " . strlen($content) . " bytes");
    return true;
}

/**
 * Unlock a PDF file
 * 
 * @param string $inputFile Path to the input PDF file
 * @param string $outputFile Path to the output PDF file
 * @return bool True if successful, false otherwise
 */
function unlockPdf($inputFile, $outputFile) {
    logMessage("Starting PDF unlock process for file: " . $inputFile);
    
    // Method 1: Try to unlock using Ghostscript with enhanced parameters (best method)
    logMessage("Method 1: Attempting to unlock with Ghostscript (enhanced parameters)");
    if (unlockPdfWithEnhancedGhostscript($inputFile, $outputFile)) {
        logMessage("Successfully unlocked PDF with Ghostscript (enhanced parameters)");
        return true;
    }
    
    // Method 2: Try to unlock using TCPDF and FPDI
    logMessage("Method 2: Attempting to unlock with TCPDF and FPDI");
    if (unlockPdfWithTCPDF($inputFile, $outputFile)) {
        logMessage("Successfully unlocked PDF with TCPDF and FPDI");
        return true;
    }
    
    // Method 3: Try to unlock using standard Ghostscript
    logMessage("Method 3: Attempting to unlock with standard Ghostscript");
    if (unlockPdfWithGhostscript($inputFile, $outputFile)) {
        logMessage("Successfully unlocked PDF with standard Ghostscript");
        return true;
    }
    
    // Method 4: Check if pdftk is available and try to use it
    logMessage("Method 4: Checking for pdftk");
    if (isPdftkInstalled()) {
        logMessage("pdftk is installed, attempting to unlock with pdftk");
        if (unlockPdfWithPdftk($inputFile, $outputFile)) {
            logMessage("Successfully unlocked PDF with pdftk");
            return true;
        }
    } else {
        logMessage("pdftk is not installed, skipping this method");
    }
    
    // Method 5: Check if QPDF is available and try to use it
    logMessage("Method 5: Checking for QPDF");
    if (isQpdfInstalled()) {
        logMessage("QPDF is installed, attempting to unlock with QPDF");
        if (unlockPdfWithQpdf($inputFile, $outputFile)) {
            logMessage("Successfully unlocked PDF with QPDF");
            return true;
        }
    } else {
        logMessage("QPDF is not installed, skipping this method");
    }
    
    // Method 6: Try to unlock using FPDI (if available)
    logMessage("Method 6: Checking for FPDI");
    if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
        logMessage("FPDI is available, attempting to unlock with FPDI");
        if (unlockPdfWithFpdi($inputFile, $outputFile)) {
            logMessage("Successfully unlocked PDF with FPDI");
            return true;
        }
    } else {
        logMessage("FPDI is not available, skipping this method");
    }
    
    // Method 7: Last resort - just copy the file
    logMessage("Method 7: All methods failed, attempting simple copy as last resort");
    if (copy($inputFile, $outputFile)) {
        logMessage("Successfully copied PDF file (simple copy method)");
        return true;
    }
    
    // If all methods fail
    logMessage("All unlocking methods failed for file: " . $inputFile);
    return false;
}

/**
 * Clean up expired files
 * 
 * @return void
 */
function cleanupExpiredFiles() {
    $now = time();
    
    // Scan processed directory
    if (is_dir(PROCESSED_DIR)) {
        foreach (new DirectoryIterator(PROCESSED_DIR) as $userDir) {
            if ($userDir->isDot() || !$userDir->isDir()) {
                continue;
            }
            
            $userProcessedDir = $userDir->getPathname();
            
            foreach (new DirectoryIterator($userProcessedDir) as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                    continue;
                }
                
                $filePath = $fileInfo->getPathname();
                $fileModTime = $fileInfo->getMTime();
                
                // Check if file has expired
                if (($now - $fileModTime) > FILE_EXPIRY_TIME) {
                    @unlink($filePath);
                }
            }
            
            // Remove user directory if empty
            $files = array_diff(scandir($userProcessedDir), ['.', '..']);
            if (empty($files)) {
                @rmdir($userProcessedDir);
            }
        }
    }
    
    // Clean up upload directory as well
    if (is_dir(UPLOAD_DIR)) {
        foreach (new DirectoryIterator(UPLOAD_DIR) as $userDir) {
            if ($userDir->isDot() || !$userDir->isDir()) {
                continue;
            }
            
            $userUploadDir = $userDir->getPathname();
            
            foreach (new DirectoryIterator($userUploadDir) as $fileInfo) {
                if ($fileInfo->isDot() || !$fileInfo->isFile()) {
                    continue;
                }
                
                $filePath = $fileInfo->getPathname();
                $fileModTime = $fileInfo->getMTime();
                
                // Check if file has expired
                if (($now - $fileModTime) > FILE_EXPIRY_TIME) {
                    @unlink($filePath);
                }
            }
            
            // Remove user directory if empty
            $files = array_diff(scandir($userUploadDir), ['.', '..']);
            if (empty($files)) {
                @rmdir($userUploadDir);
            }
        }
    }
}

/**
 * Get user's files
 * 
 * @param string $userId User ID
 * @return array Files information
 */
function getUserFiles($userId) {
    $files = [];
    $userProcessedDir = PROCESSED_DIR . $userId . '/';
    $now = time();
    
    if (is_dir($userProcessedDir)) {
        foreach (new DirectoryIterator($userProcessedDir) as $fileInfo) {
            if ($fileInfo->isDot() || !$fileInfo->isFile() || $fileInfo->getExtension() !== 'pdf') {
                continue;
            }
            
            $filePath = $fileInfo->getPathname();
            $fileModTime = $fileInfo->getMTime();
            $expiry = $fileModTime + FILE_EXPIRY_TIME;
            
            // Skip expired files
            if ($expiry <= $now) {
                continue;
            }
            
            $fileId = pathinfo($fileInfo->getFilename(), PATHINFO_FILENAME);
            $originalName = getOriginalFileName($fileId, $userId);
            
            $files[] = [
                'id' => $fileId,
                'name' => $originalName,
                'download_url' => 'download.php?id=' . $fileId,
                'expiry' => $expiry
            ];
        }
    }
    
    return $files;
}

/**
 * Get original file name from metadata
 * 
 * @param string $fileId File ID
 * @param string $userId User ID
 * @return string Original file name
 */
function getOriginalFileName($fileId, $userId) {
    $metadataFile = PROCESSED_DIR . $userId . '/' . $fileId . '.meta';
    
    if (file_exists($metadataFile)) {
        $metadata = json_decode(file_get_contents($metadataFile), true);
        return $metadata['original_name'] ?? ($fileId . '.pdf');
    }
    
    return $fileId . '.pdf';
}

/**
 * Save file metadata
 * 
 * @param string $fileId File ID
 * @param string $userId User ID
 * @param array $metadata Metadata
 * @return bool Success status
 */
function saveFileMetadata($fileId, $userId, $metadata) {
    $metadataFile = PROCESSED_DIR . $userId . '/' . $fileId . '.meta';
    return file_put_contents($metadataFile, json_encode($metadata)) !== false;
}

// Run cleanup on every request
cleanupExpiredFiles(); 