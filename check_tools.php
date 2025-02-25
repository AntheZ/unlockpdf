<?php
/**
 * Script to check for external tools used for PDF unlocking
 */

// Function to output messages
function output($message) {
    echo $message . PHP_EOL;
    flush();
}

// Function to check if a command is available
function commandExists($command) {
    $whereCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $cmd = sprintf('%s %s 2>&1', $whereCommand, escapeshellarg($command));
    exec($cmd, $output, $return_var);
    return $return_var === 0;
}

// Function to check Ghostscript version
function checkGhostscript() {
    $command = 'gswin64c --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    // Try alternative command
    $command = 'gswin32c --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    // Try Linux/Mac command
    $command = 'gs --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        return trim($output[0]);
    }
    
    return false;
}

// Function to check QPDF version
function checkQPDF() {
    $command = 'qpdf --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        // Extract version from output
        foreach ($output as $line) {
            if (strpos($line, 'qpdf version') !== false) {
                return trim(str_replace('qpdf version', '', $line));
            }
        }
        return 'installed (version unknown)';
    }
    
    return false;
}

// Function to check pdftk version
function checkPdftk() {
    $command = 'pdftk --version 2>&1';
    exec($command, $output, $return_var);
    
    if ($return_var === 0 && !empty($output)) {
        // Extract version from output
        foreach ($output as $line) {
            if (strpos($line, 'pdftk') !== false && strpos($line, 'version') !== false) {
                return trim($line);
            }
        }
        return 'installed (version unknown)';
    }
    
    return false;
}

// Check PHP extensions
output('Checking PHP extensions...');
$required_extensions = ['json', 'fileinfo', 'zip', 'openssl', 'mbstring'];
$missing_extensions = [];

foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        output("✓ {$ext} extension is installed");
    } else {
        output("✗ {$ext} extension is missing");
        $missing_extensions[] = $ext;
    }
}

if (!empty($missing_extensions)) {
    output('');
    output('Warning: Some required PHP extensions are missing.');
    output('You may encounter issues with the PDF Unlock Tool.');
}

// Check external tools
output('');
output('Checking external tools...');

// Check Ghostscript
$gs_version = checkGhostscript();
if ($gs_version !== false) {
    output("✓ Ghostscript is installed (version {$gs_version})");
} else {
    output("✗ Ghostscript is not installed or not in PATH");
    output("  Download: https://www.ghostscript.com/releases/gsdnld.html");
}

// Check QPDF
$qpdf_version = checkQPDF();
if ($qpdf_version !== false) {
    output("✓ QPDF is installed (version {$qpdf_version})");
} else {
    output("✗ QPDF is not installed or not in PATH");
    output("  Download: https://github.com/qpdf/qpdf/releases");
}

// Check pdftk
$pdftk_version = checkPdftk();
if ($pdftk_version !== false) {
    output("✓ pdftk is installed ({$pdftk_version})");
} else {
    output("✗ pdftk is not installed or not in PATH");
    output("  Download: https://www.pdflabs.com/tools/pdftk-the-pdf-toolkit/");
}

// Check FPDI (PHP library)
output('');
output('Checking PHP libraries...');
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
    if (class_exists('\\setasign\\Fpdi\\Fpdi')) {
        output("✓ FPDI library is installed");
    } else {
        output("✗ FPDI library is not installed");
        output("  Run: php install_dependencies.php");
    }
} else {
    output("✗ Composer dependencies are not installed");
    output("  Run: php install_dependencies.php");
}

// Summary
output('');
output('Summary:');
$tools_installed = 0;
$tools_total = 3; // Ghostscript, QPDF, pdftk

if ($gs_version !== false) $tools_installed++;
if ($qpdf_version !== false) $tools_installed++;
if ($pdftk_version !== false) $tools_installed++;

output("{$tools_installed} of {$tools_total} external tools are installed.");

if ($tools_installed === 0) {
    output('');
    output('Warning: No external tools are installed!');
    output('The PDF Unlock Tool will use fallback methods, which may not work for all PDFs.');
    output('It is highly recommended to install at least one of the external tools.');
} elseif ($tools_installed < $tools_total) {
    output('');
    output('Recommendation: Install all external tools for best results.');
    output('Different tools work better for different types of PDF protection.');
}

output('');
output('For installation instructions, see INSTALL.md');
output(''); 