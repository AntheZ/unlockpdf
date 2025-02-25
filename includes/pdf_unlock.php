<?php
/**
 * PDF Unlock Tool - Alternative PDF unlocking method using FPDI
 * 
 * This file provides an alternative method for unlocking PDFs using the FPDI library.
 * It requires the FPDI and TCPDF libraries to be installed via Composer.
 */

// Check if FPDI is available
if (!class_exists('\\setasign\\Fpdi\\Fpdi')) {
    /**
     * Fallback PDF unlocking method using pure PHP
     * 
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @return bool Success status
     */
    function unlockPdfWithFpdi($inputPath, $outputPath) {
        logMessage("FPDI not available. Please install FPDI via Composer.");
        return false;
    }
} else {
    /**
     * Unlock PDF using FPDI
     * 
     * @param string $inputPath Path to input file
     * @param string $outputPath Path to output file
     * @return bool Success status
     */
    function unlockPdfWithFpdi($inputPath, $outputPath) {
        try {
            logMessage("Attempting to unlock PDF with FPDI: " . $inputPath);
            
            // Create new PDF document
            $pdf = new \setasign\Fpdi\Fpdi();
            
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
            
            logMessage("PDF successfully unlocked with FPDI");
            return true;
        } catch (\Exception $e) {
            logMessage("FPDI error: " . $e->getMessage());
            return false;
        }
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
 * @param string $inputPath Path to input file
 * @param string $outputPath Path to output file
 * @return bool Success status
 */
function unlockPdfWithEnhancedGhostscript($inputPath, $outputPath) {
    logMessage("Attempting to unlock PDF with Enhanced Ghostscript: " . $inputPath);
    
    // Enhanced parameters to remove all restrictions
    $command = sprintf(
        'gs -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 -dPDFSETTINGS=/default ' .
        '-dCompressFonts=true -dSubsetFonts=true -dEmbedAllFonts=true ' .
        '-dPermissions=-44 -sOutputFile="%s" "%s" 2>&1',
        escapeshellarg($outputPath),
        escapeshellarg($inputPath)
    );
    
    logMessage("Executing command: " . $command);
    exec($command, $output, $returnVar);
    
    $success = ($returnVar === 0);
    if (!$success) {
        logMessage("Enhanced Ghostscript failed with return code: " . $returnVar . ", Output: " . implode("\n", $output));
    } else {
        logMessage("Enhanced Ghostscript successfully unlocked the PDF");
    }
    
    return $success;
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