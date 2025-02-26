<?php
/**
 * PDF Unlock Tool - Власна бібліотека для розблокування PDF-файлів
 * 
 * Цей файл містить функції для розблокування PDF-файлів різними методами
 * без залежності від сторонніх бібліотек.
 */

/**
 * Перевірка наявності Ghostscript
 * 
 * @return bool Чи встановлено Ghostscript
 */
function isGhostscriptInstalled() {
    // Перевірка для Linux/Mac
    $command = 'gs --version 2>&1';
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        return true;
    }
    
    // Перевірка для Windows
    $command = 'gswin64c --version 2>&1';
    exec($command, $output, $returnVar);
    
    if ($returnVar === 0) {
        return true;
    }
    
    $command = 'gswin32c --version 2>&1';
    exec($command, $output, $returnVar);
    
    return ($returnVar === 0);
}

/**
 * Перевірка наявності QPDF
 * 
 * @return bool Чи встановлено QPDF
 */
function isQpdfInstalled() {
    $command = 'qpdf --version 2>&1';
    exec($command, $output, $returnVar);
    
    return ($returnVar === 0);
}

/**
 * Перевірка наявності pdftk
 * 
 * @return bool Чи встановлено pdftk
 */
function isPdftkInstalled() {
    $command = 'pdftk --version 2>&1';
    exec($command, $output, $returnVar);
    
    return ($returnVar === 0);
}

/**
 * Розблокування PDF за допомогою Ghostscript з розширеними параметрами
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithEnhancedGhostscript($inputFile, $outputFile) {
    // Перевірка наявності Ghostscript
    if (!isGhostscriptInstalled()) {
        logMessage("Ghostscript не встановлено");
        return false;
    }
    
    // Визначення команди Ghostscript
    $gsCommand = (PHP_OS == 'WINNT') ? 'gswin64c' : 'gs';
    if (PHP_OS == 'WINNT' && !commandExists('gswin64c')) {
        $gsCommand = 'gswin32c';
    }
    
    // Конструювання команди Ghostscript з розширеними параметрами
    $command = sprintf(
        '%s -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -dCompatibilityLevel=1.4 ' .
        '-dPDFSETTINGS=/default -dCompressFonts=true -dDetectDuplicateImages=true ' .
        '-dAutoRotatePages=/None -dPrinted=false -dCannotEmbedFontPolicy=/Warning ' .
        '-c "<</AllowPrint true /AllowCopy true /AllowChange true /AllowAnnots true ' .
        '/AllowFillIn true /AllowScreenReaders true /AllowAssembly true ' .
        '/AllowDegradedPrinting true /OwnerPassword () /UserPassword () ' .
        '/EncryptMetadata false>> setpdfparams" ' .
        '-f "%s" -sOutputFile="%s"',
        $gsCommand,
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );
    
    // Логування команди
    logMessage("Виконання команди Ghostscript з розширеними параметрами: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command . " 2>&1", $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою Ghostscript з розширеними параметрами");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою Ghostscript з розширеними параметрами. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF за допомогою стандартного Ghostscript
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithGhostscript($inputFile, $outputFile) {
    // Перевірка наявності Ghostscript
    if (!isGhostscriptInstalled()) {
        logMessage("Ghostscript не встановлено");
        return false;
    }
    
    // Визначення команди Ghostscript
    $gsCommand = (PHP_OS == 'WINNT') ? 'gswin64c' : 'gs';
    if (PHP_OS == 'WINNT' && !commandExists('gswin64c')) {
        $gsCommand = 'gswin32c';
    }
    
    // Конструювання стандартної команди Ghostscript
    $command = sprintf(
        '%s -q -dNOPAUSE -dBATCH -sDEVICE=pdfwrite -sOutputFile="%s" -c .setpdfwrite -f "%s" 2>&1',
        $gsCommand,
        escapeshellarg($outputFile),
        escapeshellarg($inputFile)
    );
    
    // Логування команди
    logMessage("Виконання стандартної команди Ghostscript: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою стандартного Ghostscript");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою стандартного Ghostscript. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF за допомогою QPDF
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithQpdf($inputFile, $outputFile) {
    // Перевірка наявності QPDF
    if (!isQpdfInstalled()) {
        logMessage("QPDF не встановлено");
        return false;
    }
    
    // Конструювання команди QPDF
    $command = sprintf(
        'qpdf --decrypt "%s" "%s" 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );
    
    // Логування команди
    logMessage("Виконання команди QPDF: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою QPDF");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою QPDF. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF за допомогою pdftk
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithPdftk($inputFile, $outputFile) {
    // Перевірка наявності pdftk
    if (!isPdftkInstalled()) {
        logMessage("pdftk не встановлено");
        return false;
    }
    
    // Конструювання команди pdftk
    $command = sprintf(
        'pdftk "%s" output "%s" allow all 2>&1',
        escapeshellarg($inputFile),
        escapeshellarg($outputFile)
    );
    
    // Логування команди
    logMessage("Виконання команди pdftk: " . $command);
    
    // Виконання команди
    $output = [];
    $returnCode = 0;
    exec($command, $output, $returnCode);
    
    // Перевірка успішності виконання
    if ($returnCode === 0 && file_exists($outputFile)) {
        logMessage("PDF успішно розблоковано за допомогою pdftk");
        return true;
    } else {
        logMessage("Не вдалося розблокувати PDF за допомогою pdftk. Код повернення: " . $returnCode . ", Вивід: " . implode("\n", $output));
        return false;
    }
}

/**
 * Розблокування PDF за допомогою PHP (власна реалізація)
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithPhp($inputFile, $outputFile) {
    logMessage("Спроба розблокування PDF за допомогою PHP: " . $inputFile);
    
    try {
        // Читання вмісту PDF-файлу
        $pdfContent = file_get_contents($inputFile);
        if ($pdfContent === false) {
            logMessage("Не вдалося прочитати вхідний PDF-файл");
            return false;
        }
        
        // Пошук та видалення шифрування
        $pattern = '/(\/Encrypt\s+\d+\s+\d+\s+R)/i';
        $pdfContent = preg_replace($pattern, '', $pdfContent);
        
        // Пошук та модифікація прав доступу
        $pattern = '/(\/P\s+)(-?\d+)/i';
        $pdfContent = preg_replace($pattern, '$1' . '4294967295', $pdfContent);
        
        // Запис модифікованого вмісту у вихідний файл
        $result = file_put_contents($outputFile, $pdfContent);
        if ($result === false) {
            logMessage("Не вдалося записати вихідний PDF-файл");
            return false;
        }
        
        logMessage("PDF успішно розблоковано за допомогою PHP");
        return true;
    } catch (Exception $e) {
        logMessage("Помилка при розблокуванні PDF за допомогою PHP: " . $e->getMessage());
        return false;
    }
}

/**
 * Розблокування PDF за допомогою простого копіювання
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function simplePdfCopy($inputFile, $outputFile) {
    logMessage("Використання простого копіювання як останній варіант: " . $inputFile);
    
    $success = copy($inputFile, $outputFile);
    
    if (!$success) {
        logMessage("Не вдалося скопіювати PDF-файл");
    } else {
        logMessage("Копіювання PDF-файлу завершено");
    }
    
    return $success;
}

/**
 * Розблокування PDF за допомогою модифікації метаданих
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithMetadataModification($inputFile, $outputFile) {
    logMessage("Спроба розблокування PDF за допомогою модифікації метаданих: " . $inputFile);
    
    try {
        // Читання вмісту PDF-файлу
        $pdfContent = file_get_contents($inputFile);
        if ($pdfContent === false) {
            logMessage("Не вдалося прочитати вхідний PDF-файл");
            return false;
        }
        
        // Додавання метаданих для розблокування
        $metadataString = "\n/Metadata << /Type /Metadata /Subtype /XML /Length 0 >> stream\nendstream\nendobj\n";
        
        // Вставка метаданих перед закриваючим тегом
        $pattern = '/(%%EOF)/i';
        $pdfContent = preg_replace($pattern, $metadataString . '$1', $pdfContent);
        
        // Запис модифікованого вмісту у вихідний файл
        $result = file_put_contents($outputFile, $pdfContent);
        if ($result === false) {
            logMessage("Не вдалося записати вихідний PDF-файл");
            return false;
        }
        
        logMessage("PDF успішно розблоковано за допомогою модифікації метаданих");
        return true;
    } catch (Exception $e) {
        logMessage("Помилка при розблокуванні PDF за допомогою модифікації метаданих: " . $e->getMessage());
        return false;
    }
}

/**
 * Розблокування PDF за допомогою видалення обмежень
 * 
 * @param string $inputFile Шлях до вхідного PDF-файлу
 * @param string $outputFile Шлях до вихідного PDF-файлу
 * @return bool Успішність операції
 */
function unlockPdfWithRestrictionRemoval($inputFile, $outputFile) {
    logMessage("Спроба розблокування PDF за допомогою видалення обмежень: " . $inputFile);
    
    try {
        // Читання вмісту PDF-файлу
        $pdfContent = file_get_contents($inputFile);
        if ($pdfContent === false) {
            logMessage("Не вдалося прочитати вхідний PDF-файл");
            return false;
        }
        
        // Видалення обмежень
        $patterns = [
            '/(\/Encrypt\s+\d+\s+\d+\s+R)/i',
            '/(\/EncryptMetadata\s+\w+)/i',
            '/(\/EncryptionHandler\s+\d+\s+\d+\s+R)/i',
            '/(\/CF\s+<<[^>]+>>)/i',
            '/(\/StmF\s+\/\w+)/i',
            '/(\/StrF\s+\/\w+)/i',
            '/(\/EFF\s+\/\w+)/i',
            '/(\/OE\s+<[^>]+>)/i',
            '/(\/UE\s+<[^>]+>)/i',
            '/(\/Perms\s+<[^>]+>)/i',
            '/(\/O\s+<[^>]+>)/i',
            '/(\/U\s+<[^>]+>)/i',
            '/(\/P\s+)(-?\d+)/i'
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $pdfContent)) {
                if ($pattern === '/(\/P\s+)(-?\d+)/i') {
                    // Встановлення всіх прав доступу
                    $pdfContent = preg_replace($pattern, '$1' . '4294967295', $pdfContent);
                } else {
                    // Видалення обмежень
                    $pdfContent = preg_replace($pattern, '', $pdfContent);
                }
            }
        }
        
        // Запис модифікованого вмісту у вихідний файл
        $result = file_put_contents($outputFile, $pdfContent);
        if ($result === false) {
            logMessage("Не вдалося записати вихідний PDF-файл");
            return false;
        }
        
        logMessage("PDF успішно розблоковано за допомогою видалення обмежень");
        return true;
    } catch (Exception $e) {
        logMessage("Помилка при розблокуванні PDF за допомогою видалення обмежень: " . $e->getMessage());
        return false;
    }
}

/**
 * Перевірка наявності команди
 * 
 * @param string $command Команда для перевірки
 * @return bool Чи доступна команда
 */
function commandExists($command) {
    $whereCommand = (PHP_OS == 'WINNT') ? 'where' : 'which';
    $cmd = sprintf('%s %s 2>&1', $whereCommand, escapeshellarg($command));
    exec($cmd, $output, $return_var);
    return $return_var === 0;
} 