<?php
/**
 * Test script for PDF unlocking
 * 
 * This script is used to test the PDF unlocking functionality
 * without going through the web interface.
 */

// Include autoloader
if (file_exists(__DIR__ . '/autoload.php')) {
    require_once __DIR__ . '/autoload.php';
}

// Include helper functions
require_once 'includes/functions.php';

// Check if a file path is provided
if ($argc < 2) {
    echo "Usage: php test_unlock.php <path_to_pdf_file>\n";
    exit(1);
}

$inputPath = $argv[1];

// Check if the file exists
if (!file_exists($inputPath)) {
    echo "Error: File not found: $inputPath\n";
    exit(1);
}

// Check if it's a valid PDF
if (!isValidPdf($inputPath)) {
    echo "Error: Not a valid PDF file: $inputPath\n";
    exit(1);
}

// Create output path
$outputPath = __DIR__ . '/test_output.pdf';

// Try to unlock the PDF
echo "Attempting to unlock PDF: $inputPath\n";
echo "Output will be saved to: $outputPath\n";

$success = unlockPdf($inputPath, $outputPath);

if ($success) {
    echo "Success! PDF unlocked and saved to: $outputPath\n";
} else {
    echo "Failed to unlock PDF. Check the logs for more information.\n";
}

// Display log file content
$logFile = __DIR__ . '/logs/app.log';
if (file_exists($logFile)) {
    echo "\nLog file content:\n";
    echo "----------------\n";
    echo file_get_contents($logFile);
}

exit($success ? 0 : 1); 