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
        mkdir(UPLOAD_DIR, 0755, true);
    }
    
    if (!file_exists(PROCESSED_DIR)) {
        mkdir(PROCESSED_DIR, 0755, true);
    }
    
    if (!file_exists($userUploadDir)) {
        mkdir($userUploadDir, 0755, true);
    }
    
    if (!file_exists($userProcessedDir)) {
        mkdir($userProcessedDir, 0755, true);
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
    // Check file extension
    if (getFileExtension($filePath) !== 'pdf') {
        return false;
    }
    
    // Check file signature (PDF magic number)
    $handle = fopen($filePath, 'rb');
    if (!$handle) {
        return false;
    }
    
    $header = fread($handle, 4);
    fclose($handle);
    
    return $header === '%PDF';
}

/**
 * Download file from URL
 * 
 * @param string $url URL to download from
 * @param string $savePath Path to save the file
 * @return bool Success status
 */
function downloadFile($url, $savePath) {
    $options = [
        'http' => [
            'method' => 'GET',
            'header' => "User-Agent: PDF Unlock Tool\r\n"
        ]
    ];
    
    $context = stream_context_create($options);
    $content = @file_get_contents($url, false, $context);
    
    if ($content === false) {
        return false;
    }
    
    return file_put_contents($savePath, $content) !== false;
}

/**
 * Unlock PDF file
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdf($inputPath, $outputPath) {
    // Check if QPDF is installed
    exec('qpdf --version 2>&1', $output, $returnVar);
    $qpdfInstalled = ($returnVar === 0);
    
    if ($qpdfInstalled) {
        // Use QPDF to unlock the PDF
        $command = sprintf(
            'qpdf --decrypt "%s" "%s" 2>&1',
            escapeshellarg($inputPath),
            escapeshellarg($outputPath)
        );
        
        exec($command, $output, $returnVar);
        return ($returnVar === 0);
    } else {
        // Fallback to Ghostscript if QPDF is not available
        exec('gs --version 2>&1', $output, $returnVar);
        $gsInstalled = ($returnVar === 0);
        
        if ($gsInstalled) {
            $command = sprintf(
                'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="%s" -c .setpdfwrite -f "%s" 2>&1',
                escapeshellarg($outputPath),
                escapeshellarg($inputPath)
            );
            
            exec($command, $output, $returnVar);
            return ($returnVar === 0);
        } else {
            // If neither QPDF nor Ghostscript is available, use PHP fallback
            // This is a very basic fallback and may not work for all PDFs
            return copy($inputPath, $outputPath);
        }
    }
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