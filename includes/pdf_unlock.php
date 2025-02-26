<?php
/**
 * PDF Unlock Tool - Alternative PDF unlocking method using FPDI with TCPDF
 * 
 * This file provides an alternative method for unlocking PDFs using the FPDI library.
 * It requires the FPDI and TCPDF libraries to be installed via Composer.
 */

// Check if TCPDF and FPDI are available
if (!class_exists('\\setasign\\Fpdi\\Tcpdf\\Fpdi') && class_exists('\\TCPDF') && class_exists('\\setasign\\Fpdi\\Fpdi')) {
    /**
     * Unlock PDF using FPDI with TCPDF adapter
     * 
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @return bool Success status
     */
    function unlockPdfWithFpdi($inputPath, $outputPath) {
        try {
            logMessage("Attempting to unlock PDF with FPDI+TCPDF: " . $inputPath);
            
            // Create new PDF document using TCPDF adapter
            $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
            
            // Get the number of pages
            $pageCount = $pdf->setSourceFile($inputPath);
            logMessage("PDF has " . $pageCount . " pages");
            
            // Process each page
            for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                // Import page
                $templateId = $pdf->importPage($pageNo);
                
                // Get the size of the imported page
                $size = $pdf->getTemplateSize($templateId);
                
                // Add a page with the same size
                $pdf->AddPage(
                    $size['width'] > $size['height'] ? 'L' : 'P', 
                    [$size['width'], $size['height']]
                );
                
                // Use the imported page
                $pdf->useTemplate($templateId);
            }
            
            // Save the new PDF
            $pdf->Output($outputPath, 'F');
            
            logMessage("PDF successfully unlocked with FPDI+TCPDF");
            return true;
        } catch (\Exception $e) {
            logMessage("FPDI+TCPDF error: " . $e->getMessage());
            return false;
        }
    }
} else {
    /**
     * Fallback PDF unlocking method
     * 
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @return bool Success status
     */
    function unlockPdfWithFpdi($inputPath, $outputPath) {
        logMessage("FPDI with TCPDF not available. Skipping this method.");
        return false;
    }
}

/**
 * Unlock PDF using qpdf command line tool
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdfWithQpdf($inputPath, $outputPath) {
    logMessage("Attempting to unlock PDF with qpdf: " . $inputPath);
    
    $command = sprintf(
        'qpdf --decrypt "%s" "%s" 2>&1',
        escapeshellarg($inputPath),
        escapeshellarg($outputPath)
    );
    
    logMessage("Executing command: " . $command);
    exec($command, $output, $returnVar);
    
    $success = ($returnVar === 0);
    if (!$success) {
        logMessage("qpdf failed with return code: " . $returnVar . ", Output: " . implode("\n", $output));
    } else {
        logMessage("qpdf successfully unlocked the PDF");
    }
    
    return $success;
}

/**
 * Unlock PDF using Ghostscript
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdfWithGhostscript($inputPath, $outputPath) {
    logMessage("Attempting to unlock PDF with Ghostscript: " . $inputPath);
    
    // Use 'gs' command for Linux
    $command = sprintf(
        'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="%s" -c .setpdfwrite -f "%s" 2>&1',
        escapeshellarg($outputPath),
        escapeshellarg($inputPath)
    );
    
    logMessage("Executing command: " . $command);
    exec($command, $output, $returnVar);
    
    $success = ($returnVar === 0);
    if (!$success) {
        logMessage("Ghostscript failed with return code: " . $returnVar . ", Output: " . implode("\n", $output));
    } else {
        logMessage("Ghostscript successfully unlocked the PDF");
    }
    
    return $success;
}

/**
 * Unlock PDF using Ghostscript with enhanced parameters
 * 
 * @param string $inputFile Path to the input PDF file
 * @param string $outputFile Path to the output PDF file
 * @return bool True if successful, false otherwise
 */
function unlockPdfWithEnhancedGhostscript($inputFile, $outputFile) {
    // Construct the Ghostscript command with enhanced parameters
    $command = 'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 '
             . '-dPDFSETTINGS=/default -dCompressFonts=true -dDetectDuplicateImages=true '
             . '-dAutoRotatePages=/None -dPrinted=false -dCannotEmbedFontPolicy=/Warning '
             . '-c "<</AllowPrint true /AllowCopy true /AllowChange true /AllowAnnots true '
             . '/AllowFillIn true /AllowScreenReaders true /AllowAssembly true '
             . '/AllowDegradedPrinting true /OwnerPassword () /UserPassword () '
             . '/EncryptMetadata false>> setpdfparams" '
             . '-f "' . $inputFile . '" -sOutputFile="' . $outputFile . '"';

    // Log the command being executed
    logMessage("Executing enhanced Ghostscript command: " . $command);
    
    // Execute the command
    $output = [];
    $returnCode = 0;
    exec($command . " 2>&1", $output, $returnCode);
    
    // Check if the command was successful
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("Successfully unlocked PDF with enhanced Ghostscript parameters");
        return true;
    } else {
        logMessage("Failed to unlock PDF with enhanced Ghostscript parameters. Return code: " . $returnCode . ", Output: " . implode("\n", $output));
        return false;
    }
}

/**
 * Unlock PDF using pdftk
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdfWithPdftk($inputPath, $outputPath) {
    logMessage("Attempting to unlock PDF with pdftk: " . $inputPath);
    
    $command = sprintf(
        'pdftk "%s" output "%s" allow all 2>&1',
        escapeshellarg($inputPath),
        escapeshellarg($outputPath)
    );
    
    logMessage("Executing command: " . $command);
    exec($command, $output, $returnVar);
    
    $success = ($returnVar === 0);
    if (!$success) {
        logMessage("pdftk failed with return code: " . $returnVar . ", Output: " . implode("\n", $output));
    } else {
        logMessage("pdftk successfully unlocked the PDF");
    }
    
    return $success;
}

/**
 * Simple PDF copy as a last resort
 * 
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function simplePdfCopy($inputPath, $outputPath) {
    logMessage("Using simple copy as a last resort: " . $inputPath);
    
    $success = copy($inputPath, $outputPath);
    
    if (!$success) {
        logMessage("Simple copy failed");
    } else {
        logMessage("Simple copy completed");
    }
    
    return $success;
}

/**
 * Unlock PDF using TCPDF and FPDI
 * 
 * @param string $inputFile Path to the input PDF file
 * @param string $outputFile Path to the output PDF file
 * @return bool True if successful, false otherwise
 */
function unlockPdfWithTCPDF($inputFile, $outputFile) {
    try {
        // Check if TCPDF and FPDI-TCPDF are available
        if (!class_exists('\\setasign\\Fpdi\\Tcpdf\\Fpdi')) {
            logMessage("FPDI-TCPDF class not found. Make sure you have installed setasign/fpdi-tcpdf");
            return false;
        }
        
        // Create new FPDI-TCPDF instance
        $pdf = new \setasign\Fpdi\Tcpdf\Fpdi();
        
        // Set document information
        $pdf->SetCreator('PDF Unlock Tool');
        $pdf->SetAuthor('PDF Unlock Tool');
        $pdf->SetTitle('Unlocked PDF');
        
        // Remove protection
        $pdf->SetProtection(array(), '', null, 0, null);
        
        // Get the number of pages from the original PDF
        $pageCount = $pdf->setSourceFile($inputFile);
        
        // Import all pages from the original PDF
        for ($i = 1; $i <= $pageCount; $i++) {
            $template = $pdf->importPage($i);
            $size = $pdf->getTemplateSize($template);
            
            // Add a page with the same orientation as the imported page
            if ($size['width'] > $size['height']) {
                $pdf->AddPage('L', array($size['width'], $size['height']));
            } else {
                $pdf->AddPage('P', array($size['width'], $size['height']));
            }
            
            // Use the imported page
            $pdf->useTemplate($template);
        }
        
        // Save the new PDF to the output file
        $pdf->Output($outputFile, 'F');
        
        // Check if the output file exists
        if (file_exists($outputFile)) {
            logMessage("Successfully unlocked PDF with TCPDF and FPDI");
            return true;
        } else {
            logMessage("Failed to create output file with TCPDF and FPDI");
            return false;
        }
    } catch (Exception $e) {
        logMessage("Error unlocking PDF with TCPDF and FPDI: " . $e->getMessage());
        return false;
    }
} 