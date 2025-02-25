<?php
/**
 * Script to install dependencies without using global Composer command
 */

// Define constants
define('COMPOSER_URL', 'https://getcomposer.org/installer');
define('COMPOSER_INSTALLER', __DIR__ . '/composer-setup.php');
define('COMPOSER_PHAR', __DIR__ . '/composer.phar');

// Function to output messages
function output($message) {
    echo $message . PHP_EOL;
    flush();
}

// Check PHP version
if (version_compare(PHP_VERSION, '7.2.0', '<')) {
    output('Error: PHP version 7.2.0 or higher is required.');
    output('Current PHP version: ' . PHP_VERSION);
    exit(1);
}

// Check if PHP has required extensions
$required_extensions = ['json', 'phar', 'zip', 'openssl', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (!extension_loaded($ext)) {
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    output('Error: The following PHP extensions are required but missing:');
    output('- ' . implode(PHP_EOL . '- ', $missing_extensions));
    output('Please install these extensions and try again.');
    exit(1);
}

// Create logs directory if it doesn't exist
if (!file_exists(__DIR__ . '/logs')) {
    output('Creating logs directory...');
    if (!mkdir(__DIR__ . '/logs', 0755, true)) {
        output('Error: Failed to create logs directory.');
        exit(1);
    }
}

// Create uploads directory if it doesn't exist
if (!file_exists(__DIR__ . '/uploads')) {
    output('Creating uploads directory...');
    if (!mkdir(__DIR__ . '/uploads', 0755, true)) {
        output('Error: Failed to create uploads directory.');
        exit(1);
    }
}

// Create processed directory if it doesn't exist
if (!file_exists(__DIR__ . '/processed')) {
    output('Creating processed directory...');
    if (!mkdir(__DIR__ . '/processed', 0755, true)) {
        output('Error: Failed to create processed directory.');
        exit(1);
    }
}

// Download Composer installer
output('Downloading Composer installer...');
$installer = @file_get_contents(COMPOSER_URL);
if ($installer === false) {
    output('Error: Failed to download Composer installer.');
    output('Please check your internet connection and try again.');
    exit(1);
}

// Save Composer installer
if (file_put_contents(COMPOSER_INSTALLER, $installer) === false) {
    output('Error: Failed to save Composer installer.');
    exit(1);
}

// Run Composer installer
output('Running Composer installer...');
$command = sprintf(
    '%s %s --quiet',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(COMPOSER_INSTALLER)
);
exec($command, $output_lines, $return_var);

// Check if Composer installer was successful
if ($return_var !== 0 || !file_exists(COMPOSER_PHAR)) {
    output('Error: Failed to install Composer.');
    output('Please try to install Composer manually.');
    exit(1);
}

// Remove Composer installer
@unlink(COMPOSER_INSTALLER);

// Install dependencies
output('Installing dependencies...');
$command = sprintf(
    '%s %s install --no-dev --no-interaction',
    escapeshellarg(PHP_BINARY),
    escapeshellarg(COMPOSER_PHAR)
);
exec($command, $output_lines, $return_var);

// Check if dependencies were installed successfully
if ($return_var !== 0) {
    output('Error: Failed to install dependencies.');
    output('Please try to install dependencies manually.');
    exit(1);
}

// Success message
output('');
output('Dependencies installed successfully!');
output('');
output('You can now use the PDF Unlock Tool.');
output('');
output('If you encounter any issues, please check the logs directory for error messages.');
output('');

exit(0); 