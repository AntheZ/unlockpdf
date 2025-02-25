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
 * Unlock PDF file
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdf($inputPath, $outputPath) {
    logMessage("Attempting to unlock PDF: " . $inputPath);
    
    // Verify input file exists
    if (!file_exists($inputPath)) {
        logMessage("Input file does not exist: " . $inputPath);
        return false;
    }
    
    // Try multiple methods to unlock the PDF
    
    // Method 1: Try pdftk (if available)
    exec('pdftk --version 2>&1', $output, $returnVar);
    $pdftk_installed = ($returnVar === 0);
    
    if ($pdftk_installed) {
        logMessage("pdftk is installed, trying it first");
        if (unlockPdfWithPdftk($inputPath, $outputPath)) {
            return true;
        }
    }
    
    // Method 2: Try QPDF (if available)
    exec('qpdf --version 2>&1', $output, $returnVar);
    $qpdfInstalled = ($returnVar === 0);
    
    if ($qpdfInstalled) {
        logMessage("QPDF is installed, trying it next");
        if (unlockPdfWithQpdf($inputPath, $outputPath)) {
            return true;
        }
    }
    
    // Method 3: Try Enhanced Ghostscript (if available)
    exec('gs --version 2>&1', $output, $returnVar);
    $gsInstalled = ($returnVar === 0);
    
    if ($gsInstalled) {
        logMessage("Ghostscript is installed, trying enhanced parameters");
        if (unlockPdfWithEnhancedGhostscript($inputPath, $outputPath)) {
            return true;
        }
        
        logMessage("Enhanced Ghostscript failed, trying standard parameters");
        if (unlockPdfWithGhostscript($inputPath, $outputPath)) {
            return true;
        }
    }
    
    // Method 4: Try FPDI (if available)
    if (function_exists('unlockPdfWithFpdi')) {
        logMessage("Trying FPDI method");
        if (unlockPdfWithFpdi($inputPath, $outputPath)) {
            return true;
        }
    }
    
    // Method 5: Last resort - simple copy
    logMessage("All methods failed, using simple copy as last resort");
    return simplePdfCopy($inputPath, $outputPath);
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