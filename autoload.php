<?php
/**
 * Autoload file for Composer
 */

// Check if Composer's autoloader exists
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    // Include Composer's autoloader
    require_once __DIR__ . '/vendor/autoload.php';
    
    // Log that Composer is available
    if (function_exists('logMessage')) {
        logMessage('Composer autoloader loaded successfully');
    }
} else {
    // Log that Composer is not available
    if (function_exists('logMessage')) {
        logMessage('Composer autoloader not found. FPDI functionality will not be available.');
    }
} 